<?php
/**
 * СМС всех витрин (2026-10-18): расчёты для страницы админки «Покупатели → СМС: работа и баланс» (dispatch=sms_stats.manage).
 * Данные оператора (баланс, доставка, цена, отправитель) – cscart_sms_stat / cscart_sms_balance, их раз в час собирает
 * /root/sms-stats/sms-stats-collect.php; путь покупателя (запросил код → отправлено → ввёл) – журналы кабинетов
 * cscart_gm_auth_log (GOODMi) и cscart_mm_auth_log (Maxmobiles), на момент открытия страницы.
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

const SMS_STATS_LOW_BALANCE = 300;

/** Витрины с кабинетом: таблица журнала и название рассылки кодов у оператора (name_delivery в gm/mm_cab_sms_send). */
function sms_stats_stores()
{
    return [
        'gm' => ['name' => 'GOODMi', 'log' => 'gm_auth_log', 'kind' => 'Код подтверждения GOODMi'],
        'mm' => ['name' => 'Maxmobiles', 'log' => 'mm_auth_log', 'kind' => 'Код подтверждения Maxmobiles'],
    ];
}

/** Понятное название вида СМС (name_delivery оператора). */
function sms_stats_kind_title($kind)
{
    $map = [
        'Код подтверждения GOODMi' => 'Коды подтверждения GOODMi (кабинет)',
        'Код подтверждения Maxmobiles' => 'Коды подтверждения Maxmobiles (кабинет)',
        'moysklad_changeorderstatus' => 'Статусы заказов из МойСклад',
        'Шлюз GET' => 'Шлюз GET (отправки других программ по HTTP)',
    ];

    return isset($map[$kind]) ? $map[$kind] : ($kind !== '' ? $kind : 'без названия');
}

function sms_stats_status_title($status)
{
    $map = ['deliver' => 'доставлено', 'not_deliver' => 'не доставлено', 'expired' => 'абонент недоступен', 'partly_deliver' => 'статус не получен', 'send' => 'ждёт статуса'];

    return isset($map[$status]) ? $map[$status] : $status;
}

function sms_stats_mask($phone)
{
    $phone = preg_replace('/\D/', '', (string) $phone);

    return strlen($phone) === 11 ? '+' . $phone[0] . ' ' . substr($phone, 1, 3) . ' ***-**-' . substr($phone, 9, 2) : $phone;
}

function sms_stats_pct($part, $whole)
{
    return $whole > 0 ? (int) round(100 * $part / $whole) : 0;
}

function sms_stats_money($v)
{
    return number_format((float) $v, 2, ',', ' ');
}

/** Всё для страницы за период: $days = 0 – сегодня, иначе последние N дней. */
function sms_stats_overview($days)
{
    $since = $days > 0 ? TIME - $days * 86400 : strtotime('today', TIME);
    $o = ['since' => date('d.m.Y H:i', $since)];

    // Баланс и последний сбор
    $last = db_get_row('SELECT * FROM ?:sms_balance ORDER BY ts DESC LIMIT 1');
    $ok = db_get_row("SELECT * FROM ?:sms_balance WHERE error = '' ORDER BY ts DESC LIMIT 1");
    $o['collected'] = $last ? date('d.m H:i', $last['ts']) : '';
    $o['collect_error'] = $last ? (string) $last['error'] : 'сбор ещё не запускался';
    $o['balance'] = $ok ? (float) $ok['money'] : null;
    $o['balance_fmt'] = $ok ? sms_stats_money($ok['money']) : '–';
    $o['balance_at'] = $ok ? date('d.m H:i', $ok['ts']) : '';
    $o['balance_low'] = $ok && (float) $ok['money'] < SMS_STATS_LOW_BALANCE;
    $o['low_limit'] = SMS_STATS_LOW_BALANCE;
    // Расход и прогноз – по цене отправленных СМС за 7 дней (пополнения баланса не мешают)
    $spent7 = (float) db_get_field('SELECT SUM(price) FROM ?:sms_stat WHERE ts > ?i', TIME - 7 * 86400);
    $o['spent_day_fmt'] = sms_stats_money($spent7 / 7);
    $o['days_left'] = $ok && $spent7 > 0 ? (int) floor((float) $ok['money'] / ($spent7 / 7)) : null;

    // Все СМС кабинета оператора по видам: сообщения (первая часть), доставка, стоимость (все части)
    $o['kinds'] = [];
    $tot = ['msgs' => 0, 'deliver' => 0, 'failed' => 0, 'waiting' => 0, 'cost' => 0.0];
    foreach (db_get_array(
        "SELECT kind, SUM(part_no = 1) AS msgs, SUM(part_no = 1 AND status = 'deliver') AS deliver,
            SUM(part_no = 1 AND status IN ('not_deliver', 'expired', 'partly_deliver')) AS failed, SUM(part_no = 1 AND status = 'send') AS waiting,
            SUM(price) AS cost, COUNT(DISTINCT phone_hash) AS phones
         FROM ?:sms_stat WHERE ts > ?i GROUP BY kind ORDER BY cost DESC", $since
    ) as $r) {
        $r['title'] = sms_stats_kind_title($r['kind']);
        $r['deliver_pct'] = sms_stats_pct($r['deliver'], $r['msgs']);
        $r['cost_fmt'] = sms_stats_money($r['cost']);
        $r['avg_fmt'] = $r['msgs'] > 0 ? sms_stats_money($r['cost'] / $r['msgs']) : '–';
        foreach (['msgs', 'deliver', 'failed', 'waiting'] as $k) { $tot[$k] += (int) $r[$k]; }
        $tot['cost'] += (float) $r['cost'];
        $o['kinds'][] = $r;
    }
    $tot['deliver_pct'] = sms_stats_pct($tot['deliver'], $tot['msgs']);
    $tot['cost_fmt'] = sms_stats_money($tot['cost']);
    $o['total'] = $tot;

    // Путь покупателя по витринам: журнал кабинета + доставка кодов у оператора
    $o['stores'] = [];
    foreach (sms_stats_stores() as $code => $s) {
        $ev = db_get_hash_single_array("SELECT event, COUNT(*) AS n FROM ?:{$s['log']} WHERE ts > ?i GROUP BY event", ['event', 'n'], $since);
        $n = function ($k) use ($ev) { return isset($ev[$k]) ? (int) $ev[$k] : 0; };
        $sent = $n('sms_verify') + $n('sms_recover');
        $requested = $sent + $n('sms_error') + $n('limit');
        $confirmed = $n('code_ok') + $n('recover_ok');
        $op = db_get_row(
            "SELECT SUM(part_no = 1) AS msgs, SUM(part_no = 1 AND status = 'deliver') AS deliver,
                SUM(part_no = 1 AND status IN ('not_deliver', 'expired', 'partly_deliver')) AS failed, SUM(part_no = 1 AND status = 'send') AS waiting, SUM(price) AS cost
             FROM ?:sms_stat WHERE kind = ?s AND ts > ?i", $s['kind'], $since
        );
        $o['stores'][$code] = [
            'name' => $s['name'],
            'customers' => (int) db_get_field("SELECT COUNT(DISTINCT phone) FROM ?:{$s['log']} WHERE ts > ?i AND event IN ('sms_verify', 'sms_recover', 'sms_error', 'limit')", $since),
            'requested' => $requested,
            'sent' => $sent,
            'sent_verify' => $n('sms_verify'),
            'sent_recover' => $n('sms_recover'),
            'errors' => $n('sms_error'),
            'limits' => $n('limit'),
            'delivered' => (int) $op['deliver'],
            'op_failed' => (int) $op['failed'],
            'op_waiting' => (int) $op['waiting'],
            'deliver_pct' => sms_stats_pct((int) $op['deliver'], (int) $op['msgs']),
            'confirmed' => $confirmed,
            'confirmed_verify' => $n('code_ok'),
            'confirmed_recover' => $n('recover_ok'),
            'confirm_pct' => sms_stats_pct($confirmed, $sent),
            'code_bad' => $n('code_bad'),
            'login_bad' => $n('login_bad'),
            'locks' => $n('login_lock') + $n('block'),
            'cost_fmt' => sms_stats_money($op['cost']),
        ];
    }

    // От чьего имени коды приходят покупателям: оператор связи может заменить незарегистрированное имя своим
    $o['originators'] = db_get_array(
        "SELECT kind, originator, operator, COUNT(*) AS n FROM ?:sms_stat WHERE part_no = 1 AND ts > ?i AND kind IN (?a)
         GROUP BY kind, originator, operator ORDER BY kind, n DESC", $since, array_column(sms_stats_stores(), 'kind')
    );
    foreach ($o['originators'] as &$r) {
        $r['title'] = sms_stats_kind_title($r['kind']);
        $r['own'] = in_array($r['originator'], ['GOODMi', 'Maxmobiles'], true);
    }
    unset($r);

    // Операторы связи: доставка
    $o['operators'] = db_get_array(
        "SELECT operator, COUNT(*) AS msgs, SUM(status = 'deliver') AS deliver FROM ?:sms_stat WHERE part_no = 1 AND ts > ?i GROUP BY operator ORDER BY msgs DESC", $since
    );
    foreach ($o['operators'] as &$r) { $r['deliver_pct'] = sms_stats_pct($r['deliver'], $r['msgs']); }
    unset($r);

    // Ошибки: отказы оператора при отправке кода (журнал кабинета) и недоставленные СМС любого вида
    $o['send_errors'] = [];
    foreach (sms_stats_stores() as $code => $s) {
        foreach (db_get_array("SELECT ts, phone, info FROM ?:{$s['log']} WHERE event = 'sms_error' AND ts > ?i ORDER BY ts DESC LIMIT 30", $since) as $r) {
            $o['send_errors'][] = ['ts' => (int) $r['ts'], 'when' => date('d.m H:i', $r['ts']), 'store' => $s['name'], 'phone' => sms_stats_mask($r['phone']), 'info' => $r['info']];
        }
    }
    usort($o['send_errors'], function ($a, $b) { return $b['ts'] - $a['ts']; });
    $o['failed'] = db_get_array(
        "SELECT ts, kind, operator, originator, status, phone_tail FROM ?:sms_stat
         WHERE part_no = 1 AND status IN ('not_deliver', 'expired', 'partly_deliver') AND ts > ?i ORDER BY ts DESC LIMIT 30", $since
    );
    foreach ($o['failed'] as &$r) {
        $r['when'] = date('d.m H:i', $r['ts']);
        $r['title'] = sms_stats_kind_title($r['kind']);
        $r['status_title'] = sms_stats_status_title($r['status']);
    }
    unset($r);

    // По дням: коды по витринам (журнал кабинетов) и все СМС с расходом (оператор)
    $by_day = [];
    $day = function ($ts) { return date('Y-m-d', $ts); };
    foreach (sms_stats_stores() as $code => $s) {
        foreach (db_get_array("SELECT ts, event FROM ?:{$s['log']} WHERE ts > ?i AND event IN ('sms_verify', 'sms_recover', 'code_ok', 'recover_ok', 'sms_error')", $since) as $r) {
            $d = $day($r['ts']);
            if (!isset($by_day[$d])) { $by_day[$d] = ['gm_sent' => 0, 'gm_ok' => 0, 'mm_sent' => 0, 'mm_ok' => 0, 'errors' => 0, 'msgs' => 0, 'cost' => 0.0]; }
            if ($r['event'] === 'sms_error') { $by_day[$d]['errors']++; }
            elseif (in_array($r['event'], ['code_ok', 'recover_ok'], true)) { $by_day[$d][$code . '_ok']++; }
            else { $by_day[$d][$code . '_sent']++; }
        }
    }
    foreach (db_get_array('SELECT ts, part_no, price FROM ?:sms_stat WHERE ts > ?i', $since) as $r) {
        $d = $day($r['ts']);
        if (!isset($by_day[$d])) { $by_day[$d] = ['gm_sent' => 0, 'gm_ok' => 0, 'mm_sent' => 0, 'mm_ok' => 0, 'errors' => 0, 'msgs' => 0, 'cost' => 0.0]; }
        if ((int) $r['part_no'] === 1) { $by_day[$d]['msgs']++; }
        $by_day[$d]['cost'] += (float) $r['price'];
    }
    krsort($by_day);
    $o['days'] = [];
    foreach ($by_day as $d => $r) {
        $r['d'] = date('d.m', strtotime($d));
        $r['cost_fmt'] = sms_stats_money($r['cost']);
        $o['days'][] = $r;
    }

    return $o;
}
