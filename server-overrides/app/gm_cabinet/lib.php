<?php
/**
 * GOODMi: личный кабинет – данные для страницы «Мои бонусы».
 *
 * Источники: API БонусПлюс (баланс, уровень, история операций), заказы сайта из базы магазина,
 * заказы покупателя из МойСклад (покупки в магазинах). Покупатель определяется по номеру телефона
 * из его профиля на сайте; номер из браузера не принимается.
 *
 * Файл не зависит от CS-Cart (кроме функций, куда передаются готовые строки заказов сайта) – его можно
 * проверять из командной строки. Ключи доступа лежат в secrets.php рядом и в репозиторий не попадают.
 * Совместимо с PHP 7.4.
 */

if (!defined('GM_CABINET_LIB')) {
    define('GM_CABINET_LIB', 1);
}

/** Уровни программы: порядок, признак в названии карты БонусПлюс, подписи и картинки со страницы программы. */
function gm_cab_levels()
{
    $img = 'https://goodmi.ru/images/ab__webp/abt__ut2/banners/all/112/';

    return [
        [
            'key' => 'start', 'name' => 'Start', 'pct' => 'до 4%', 'from' => 0, 'range' => 'покупки до 100 000 ₽',
            'url' => 'https://goodmi.ru/uroven-privilegiy-start/',
            'img' => $img . '%D0%A4%D0%BE%D0%BD%D0%BE%D0%B2%D0%BE%D0%B5_%D0%B8%D0%B7%D0%BE%D0%B1%D1%80%D0%B0%D0%B6%D0%B5%D0%BD%D0%B8%D0%B5_%D0%B7%D0%BE%D0%BD%D1%8B_2-%D1%81%D0%B8%D0%BD%D0%B8%D0%B9-%D1%83%D0%B6%D0%B0%D1%82%D1%8B%D0%B9_43jn-x0_png.webp',
        ],
        [
            'key' => 'user', 'name' => 'User', 'pct' => 'до 5%', 'from' => 100000, 'range' => 'от 100 000 ₽',
            'url' => 'https://goodmi.ru/uroven-privilegiy-user/',
            'img' => $img . '%D0%BC%D0%B8_%D1%8E%D0%B7%D0%B5_1125_-_%D1%83%D0%B6%D0%B0%D1%82%D0%B0%D1%8F_lwjz-4b_png.webp',
        ],
        [
            'key' => 'geek', 'name' => 'Mi Geek', 'pct' => 'до 7%', 'from' => 250000, 'range' => 'от 250 000 ₽',
            'url' => 'https://goodmi.ru/uroven-privilegiy-mi-geek/',
            'img' => $img . '%D0%BC%D0%B8_%D0%B3%D0%B8%D0%BA_1125-%D1%83%D0%B6%D0%B0%D1%82%D0%B0%D1%8F_png.webp',
        ],
        [
            'key' => 'fan', 'name' => 'Mi Fan', 'pct' => 'до 10%', 'from' => 350000, 'range' => 'от 350 000 ₽',
            'url' => 'https://goodmi.ru/uroven-privilegiy-mi-fan/',
            'img' => $img . '%D0%BC%D0%B8_%D1%84%D0%B0%D0%BD_1125_432__2_-%D1%83%D0%B6%D0%B0%D1%82%D0%B0%D1%8F_png.webp',
        ],
    ];
}

/** Каналы продаж МойСклад, которые в кабинете не показываем: заказы сайта (берём из магазина) и чужие площадки. */
function gm_cab_hidden_channels()
{
    return [
        'интернет-магазин онлайн', 'сбермегамаркет', 'магазин на wildberries', 'яндекс маркет',
        'авито', 'xione', 'максмобайлс',
    ];
}

function gm_cab_secrets()
{
    static $secrets = null;
    if ($secrets === null) {
        $file = __DIR__ . '/secrets.php';
        $secrets = is_file($file) ? (array) include $file : [];
    }

    return $secrets;
}

/** Только цифры номера; российский номер приводится к виду 7XXXXXXXXXX. Пустая строка, если номер непригоден. */
function gm_cab_phone($raw)
{
    $digits = preg_replace('/\D+/', '', (string) $raw);
    if (strlen($digits) === 11 && ($digits[0] === '8' || $digits[0] === '7')) {
        return '7' . substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        return '7' . $digits;
    }

    return '';
}

/** Запрос к внешнему сервису. Возвращает разобранный JSON или null при любой ошибке. */
function gm_cab_http($url, array $headers, $post = null, $timeout = 8)
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_ENCODING => '',
    ]);
    if ($post !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
    }
    $body = curl_exec($curl);
    $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($body === false || $code < 200 || $code >= 300) {
        return null;
    }
    $data = json_decode($body, true);

    return is_array($data) ? $data : null;
}

/**
 * Кэш в файлах: ответ внешнего сервиса хранится $ttl секунд. Неудачный ответ (null) не кэшируется,
 * но если есть прежний, отдаётся он – страница не должна пустеть из-за сбоя сервиса.
 */
function gm_cab_cached($dir, $key, $ttl, $producer)
{
    $file = rtrim($dir, '/') . '/' . md5($key) . '.json';
    $old = null;
    if (is_file($file)) {
        $old = json_decode((string) @file_get_contents($file), true);
        if (is_array($old) && isset($old['t'], $old['d']) && (time() - (int) $old['t']) < $ttl) {
            return $old['d'];
        }
    }
    $fresh = call_user_func($producer);
    if ($fresh === null) {
        return is_array($old) && isset($old['d']) ? $old['d'] : null;
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($file, json_encode(['t' => time(), 'd' => $fresh]), LOCK_EX);

    return $fresh;
}

/** Дата БонусПлюс и МойСклад приходят без часового пояса: БонусПлюс – в UTC, МойСклад – по Москве. */
function gm_cab_date($value, $source_tz, $format = 'd.m.Y')
{
    if (!$value) {
        return '';
    }
    try {
        $dt = new DateTime($value, new DateTimeZone($source_tz));
        $dt->setTimezone(new DateTimeZone('Europe/Moscow'));

        return $dt->format($format);
    } catch (Exception $e) {
        return '';
    }
}

function gm_cab_ts($value, $source_tz)
{
    if (!$value) {
        return 0;
    }
    try {
        $dt = new DateTime($value, new DateTimeZone($source_tz));

        return $dt->getTimestamp();
    } catch (Exception $e) {
        return 0;
    }
}

function gm_cab_money($value)
{
    return number_format((float) $value, 0, ',', ' ');
}

/** «1 бонус», «2 бонуса», «5 бонусов». */
function gm_cab_plural($n, $one, $few, $many)
{
    $n = abs((int) $n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    if ($n1 === 1) {
        return $one;
    }

    return $many;
}

/**
 * Баланс, уровень и история операций из БонусПлюс.
 * Возвращает null, если сервис недоступен; ['found' => false], если номера в программе нет.
 */
function gm_cab_bonusplus($phone, $history_limit = 60)
{
    $secrets = gm_cab_secrets();
    if (empty($secrets['bonusplus_auth']) || $phone === '') {
        return null;
    }
    $headers = ['Authorization: ' . $secrets['bonusplus_auth'], 'Content-Type: application/json', 'Accept: application/json'];
    $base = 'https://bonusplus.pro/api';

    $c = gm_cab_http($base . '/customer?phone=' . rawurlencode($phone), $headers);
    if ($c === null) {
        return null;
    }
    if (!isset($c['availableBonuses'])) {
        return ['found' => false];
    }

    $levels = gm_cab_levels();
    $card = mb_strtolower((string) (isset($c['discountCardName']) ? $c['discountCardName'] : ''));
    $current = 0;
    foreach ($levels as $i => $level) {
        if ($card !== '' && mb_strpos($card, $level['key']) !== false) {
            $current = $i;
        }
    }

    $spent = (float) (isset($c['purchasesTotalSum']) ? $c['purchasesTotalSum'] : 0);
    $to_next = (float) (isset($c['purchasesSumToNextCard']) ? $c['purchasesSumToNextCard'] : 0);
    $has_next = isset($levels[$current + 1]) && $to_next > 0;
    $percent = 100;
    if ($has_next) {
        $lower = (float) $levels[$current]['from'];
        $span = max(1, $spent + $to_next - $lower);
        $percent = (int) max(1, min(99, round(100 * max(0, $spent - $lower) / $span)));
    }

    $a = gm_cab_http($base . '/retail/bonusActivities', $headers, [
        'phone' => $phone, 'rowCount' => (int) $history_limit, 'startRow' => 0, 'sort' => 'desc',
    ]);
    $ops = [];
    $now = time();
    foreach ((array) (isset($a['bonusActivities']) ? $a['bonusActivities'] : []) as $row) {
        $amount = (float) (isset($row['amount']) ? $row['amount'] : 0);
        if (abs($amount) < 0.005) {
            continue;
        }
        $until = '';
        if ($amount > 0 && !empty($row['validUntil'])) {
            $until_ts = gm_cab_ts($row['validUntil'], 'UTC');
            $until = ($until_ts > $now ? 'действуют до ' : 'действовали до ') . gm_cab_date($row['validUntil'], 'UTC');
        }
        $name = trim((string) (isset($row['transactionName']) ? $row['transactionName'] : ''));
        $ops[] = [
            'name' => $name !== '' ? str_replace(' - ', ' – ', $name) : ($amount > 0 ? 'Начисление' : 'Списание'),
            'date' => gm_cab_date(isset($row['receiptDate']) ? $row['receiptDate'] : '', 'UTC'),
            'until' => $until,
            'plus' => $amount > 0,
            'amount' => gm_cab_money(abs($amount)),
            'purchase' => !empty($row['purchaseAmount']) ? gm_cab_money($row['purchaseAmount']) : '',
        ];
    }

    $balance = (float) $c['availableBonuses'];
    $expire = (float) (isset($c['nearestBonusesExpirationAmount']) ? $c['nearestBonusesExpirationAmount'] : 0);
    $debit_pct = (float) (isset($c['baseBonusDebitPercent']) ? $c['baseBonusDebitPercent'] : 0);

    return [
        'found' => true,
        'balance' => gm_cab_money($balance),
        'balance_word' => gm_cab_plural($balance, 'бонус', 'бонуса', 'бонусов'),
        'pending' => gm_cab_money(isset($c['notActiveBonuses']) ? $c['notActiveBonuses'] : 0),
        'credited' => gm_cab_money(isset($c['totalBonusCredit']) ? $c['totalBonusCredit'] : 0),
        'expire_amount' => $expire > 0 ? gm_cab_money($expire) : '',
        'expire_date' => $expire > 0 && !empty($c['nearestBonusesExpirationDate']) ? (string) $c['nearestBonusesExpirationDate'] : '',
        'debit_pct' => $debit_pct > 0 ? (int) round($debit_pct) : 25,
        'level' => $levels[$current],
        'level_index' => $current,
        'next' => $has_next ? $levels[$current + 1] : null,
        'to_next' => gm_cab_money($to_next),
        'spent' => gm_cab_money($spent),
        'percent' => $percent,
        'levels' => $levels,
        'ops' => $ops,
    ];
}

/**
 * Покупки в магазинах: заказы покупателя из МойСклад по номеру телефона, без заказов сайта и чужих площадок.
 * Возвращает null, если сервис недоступен.
 */
function gm_cab_moysklad_orders($phone, $limit = 100)
{
    $secrets = gm_cab_secrets();
    if (empty($secrets['moysklad_auth']) || strlen($phone) !== 11) {
        return null;
    }
    $headers = ['Authorization: ' . $secrets['moysklad_auth'], 'Accept-Encoding: gzip'];
    $base = 'https://api.moysklad.ru/api/remap/1.2';
    $tail = substr($phone, 1);

    $agents = gm_cab_http($base . '/entity/counterparty?search=' . rawurlencode($tail) . '&limit=20', $headers);
    if ($agents === null) {
        return null;
    }

    $hidden = gm_cab_hidden_channels();
    $orders = [];
    $checked = 0;
    foreach ((array) (isset($agents['rows']) ? $agents['rows'] : []) as $agent) {
        // Поиск МойСклад ищет и по другим полям – берём только карточки, где совпал именно телефон
        $agent_phone = gm_cab_phone(isset($agent['phone']) ? $agent['phone'] : '');
        if ($agent_phone !== $phone || empty($agent['meta']['href'])) {
            continue;
        }
        if (++$checked > 6) {
            break;
        }
        $url = $base . '/entity/customerorder?filter=' . rawurlencode('agent=' . $agent['meta']['href'])
            . '&order=' . rawurlencode('moment,desc') . '&limit=' . (int) $limit . '&expand=salesChannel,state';
        $list = gm_cab_http($url, $headers, null, 12);
        if ($list === null) {
            continue;
        }
        foreach ((array) (isset($list['rows']) ? $list['rows'] : []) as $row) {
            $channel = isset($row['salesChannel']['name']) ? (string) $row['salesChannel']['name'] : '';
            if ($channel !== '' && in_array(mb_strtolower($channel), $hidden, true)) {
                continue;
            }
            $id = isset($row['id']) ? (string) $row['id'] : '';
            $orders[$id] = [
                'source' => 'store',
                'label' => 'Магазин',
                'number' => isset($row['name']) ? (string) $row['name'] : '',
                'ts' => gm_cab_ts(isset($row['moment']) ? $row['moment'] : '', 'Europe/Moscow'),
                'date' => gm_cab_date(isset($row['moment']) ? $row['moment'] : '', 'Europe/Moscow'),
                'sum' => gm_cab_money((isset($row['sum']) ? (float) $row['sum'] : 0) / 100),
                'status' => isset($row['state']['name']) ? (string) $row['state']['name'] : '',
                'url' => '',
            ];
        }
    }

    return array_values($orders);
}

/** Заказы сайта (готовые строки из базы магазина) приводятся к тому же виду, что и покупки из МойСклад. */
function gm_cab_site_orders(array $rows, array $own_ids)
{
    $orders = [];
    foreach ($rows as $row) {
        $id = (int) $row['order_id'];
        $orders[] = [
            'key' => 'w' . $id,
            'source' => 'site',
            'label' => 'Интернет-магазин',
            'number' => (string) $id,
            'ts' => (int) $row['timestamp'],
            'date' => date('d.m.Y', (int) $row['timestamp']),
            'sum' => gm_cab_money($row['total']),
            'status' => isset($row['status_name']) ? (string) $row['status_name'] : '',
            'own' => in_array($id, $own_ids, true),
            'id' => $id,
            'url' => '',
        ];
    }

    return $orders;
}

/** Общий список заказов: сайт и магазины вместе, новые сверху. */
function gm_cab_merge_orders(array $site, array $store, $limit)
{
    $all = array_merge($site, $store);
    usort($all, function ($a, $b) {
        if ($a['ts'] === $b['ts']) {
            return 0;
        }

        return $a['ts'] > $b['ts'] ? -1 : 1;
    });

    return $limit > 0 ? array_slice($all, 0, $limit) : $all;
}

/* ---------------------------------------------------------------------------------------------------------
 * Заказы МойСклад в своей таблице (?:gm_ms_orders). Сюда – только обращение к МойСклад и приведение строк;
 * запись в базу и чтение – в store.php. Таблицу наполняет ночное задание (gm_cabinet.sync_orders),
 * а при открытии страницы покупателя дозагружаются его свежие заказы.
 * ------------------------------------------------------------------------------------------------------- */

function gm_cab_cut($value, $length)
{
    return mb_substr(trim((string) $value), 0, $length);
}

/** Заказ МойСклад → строка таблицы. null, если у заказа нет покупателя с пригодным номером телефона. */
function gm_cab_ms_row(array $order, $known_phone = '')
{
    $phone = $known_phone !== '' ? $known_phone : gm_cab_phone(isset($order['agent']['phone']) ? $order['agent']['phone'] : '');
    if ($phone === '' || empty($order['id'])) {
        return null;
    }
    $agent_id = '';
    if (!empty($order['agent']['id'])) {
        $agent_id = (string) $order['agent']['id'];
    } elseif (!empty($order['agent']['meta']['href'])) {
        $agent_id = basename((string) $order['agent']['meta']['href']);
    }

    return [
        'id' => (string) $order['id'],
        'agent_id' => gm_cab_cut($agent_id, 36),
        'phone' => $phone,
        'number' => gm_cab_cut(isset($order['name']) ? $order['name'] : '', 32),
        'moment' => substr((string) (isset($order['moment']) ? $order['moment'] : ''), 0, 19),
        'total' => round((isset($order['sum']) ? (float) $order['sum'] : 0) / 100, 2),
        'state' => gm_cab_cut(isset($order['state']['name']) ? $order['state']['name'] : '', 64),
        'channel' => gm_cab_cut(isset($order['salesChannel']['name']) ? $order['salesChannel']['name'] : '', 64),
        'external_code' => gm_cab_cut(isset($order['externalCode']) ? $order['externalCode'] : '', 64),
        'updated' => substr((string) (isset($order['updated']) ? $order['updated'] : ''), 0, 19),
    ];
}

function gm_cab_ms_headers()
{
    $secrets = gm_cab_secrets();

    return empty($secrets['moysklad_auth']) ? null : ['Authorization: ' . $secrets['moysklad_auth'], 'Accept-Encoding: gzip'];
}

/**
 * Страница общего списка заказов, изменённых начиная с $from (московское время), по возрастанию даты изменения.
 * Возвращает ['rows' => строки таблицы, 'count' => сколько заказов пришло, 'last' => дата изменения последнего] или null.
 */
function gm_cab_ms_page($from, $offset, $limit = 100)
{
    $headers = gm_cab_ms_headers();
    if ($headers === null) {
        return null;
    }
    $url = 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder?filter=' . rawurlencode('updated>=' . $from)
        . '&order=' . rawurlencode('updated,asc') . '&limit=' . (int) $limit . '&offset=' . (int) $offset
        . '&expand=agent,salesChannel,state';
    $data = gm_cab_http($url, $headers, null, 40);
    if ($data === null || !isset($data['rows'])) {
        return null;
    }
    $rows = [];
    $last = '';
    foreach ($data['rows'] as $order) {
        $row = gm_cab_ms_row($order);
        if ($row !== null) {
            $rows[] = $row;
        }
        if (!empty($order['updated'])) {
            $last = substr((string) $order['updated'], 0, 19);
        }
    }

    return ['rows' => $rows, 'count' => count($data['rows']), 'last' => $last];
}

/**
 * Свежие заказы одного покупателя. Если его карточки в МойСклад уже известны ($agent_ids), берутся только заказы,
 * изменённые с $since; иначе карточки ищутся по номеру телефона и заказы берутся целиком (до 500 на карточку).
 * Возвращает строки таблицы или null, если МойСклад недоступен.
 */
function gm_cab_ms_customer_rows($phone, array $agent_ids = [], $since = '')
{
    $headers = gm_cab_ms_headers();
    if ($headers === null || strlen($phone) !== 11) {
        return null;
    }
    $base = 'https://api.moysklad.ru/api/remap/1.2';

    if (!$agent_ids) {
        $since = '';
        $agents = gm_cab_http($base . '/entity/counterparty?search=' . rawurlencode(substr($phone, 1)) . '&limit=20', $headers);
        if ($agents === null) {
            return null;
        }
        foreach ((array) (isset($agents['rows']) ? $agents['rows'] : []) as $agent) {
            if (!empty($agent['id']) && gm_cab_phone(isset($agent['phone']) ? $agent['phone'] : '') === $phone) {
                $agent_ids[] = (string) $agent['id'];
            }
        }
    }

    $rows = [];
    foreach (array_slice(array_values(array_unique($agent_ids)), 0, 6) as $agent_id) {
        $filter = 'agent=' . $base . '/entity/counterparty/' . $agent_id . ($since !== '' ? ';updated>=' . $since : '');
        for ($offset = 0; $offset < 500; $offset += 100) {
            $url = $base . '/entity/customerorder?filter=' . rawurlencode($filter) . '&order=' . rawurlencode('moment,desc')
                . '&limit=100&offset=' . $offset . '&expand=salesChannel,state';
            $list = gm_cab_http($url, $headers, null, 12);
            if ($list === null) {
                return $rows ? $rows : null;
            }
            $page = (array) (isset($list['rows']) ? $list['rows'] : []);
            foreach ($page as $order) {
                $order['agent'] = ['id' => $agent_id];
                $row = gm_cab_ms_row($order, $phone);
                if ($row !== null) {
                    $rows[$row['id']] = $row;
                }
            }
            if (count($page) < 100) {
                break;
            }
        }
    }

    return array_values($rows);
}

/** Строки таблицы → вид для страницы (покупки в магазинах). */
function gm_cab_store_display(array $db_rows)
{
    $orders = [];
    foreach ($db_rows as $row) {
        $orders[] = [
            'key' => isset($row['id']) ? 's' . $row['id'] : '',
            'ms_id' => isset($row['id']) ? (string) $row['id'] : '',
            'items_at' => isset($row['items_at']) ? (string) $row['items_at'] : '',
            'source' => 'store',
            'label' => 'Магазин',
            'number' => (string) $row['number'],
            'ts' => gm_cab_ts($row['moment'], 'Europe/Moscow'),
            'date' => gm_cab_date($row['moment'], 'Europe/Moscow'),
            'sum' => gm_cab_money($row['total']),
            'status' => (string) $row['state'],
            'url' => '',
        ];
    }

    return $orders;
}

/**
 * Состав заказа МойСклад: строки для таблицы ?:gm_ms_order_items. null, если МойСклад недоступен.
 * Товар сайта потом находится по внешнему коду (карта обмена commerceml) или по коду товара.
 */
function gm_cab_ms_positions($order_id)
{
    $headers = gm_cab_ms_headers();
    if ($headers === null || !preg_match('/^[0-9a-f-]{36}$/', (string) $order_id)) {
        return null;
    }
    $url = 'https://api.moysklad.ru/api/remap/1.2/entity/customerorder/' . $order_id . '/positions?limit=100&expand=assortment';
    $data = gm_cab_http($url, $headers, null, 10);
    if ($data === null || !isset($data['rows'])) {
        return null;
    }
    $rows = [];
    foreach ($data['rows'] as $i => $position) {
        $item = isset($position['assortment']) && is_array($position['assortment']) ? $position['assortment'] : [];
        $rows[] = [
            'order_id' => (string) $order_id,
            'pos' => (int) $i,
            'ms_product_id' => gm_cab_cut(isset($item['id']) ? $item['id'] : '', 36),
            'ext_code' => gm_cab_cut(isset($item['externalCode']) ? $item['externalCode'] : '', 64),
            'code' => gm_cab_cut(isset($item['code']) ? $item['code'] : '', 64),
            'name' => gm_cab_cut(isset($item['name']) ? $item['name'] : '', 255),
            'qty' => round(isset($position['quantity']) ? (float) $position['quantity'] : 0, 3),
            'price' => round((isset($position['price']) ? (float) $position['price'] : 0) / 100, 2),
        ];
    }

    return $rows;
}

/** Количество без лишних нулей: 1, 2, 0,5. */
function gm_cab_qty($value)
{
    $value = (float) $value;

    return abs($value - round($value)) < 0.0005 ? (string) (int) round($value) : rtrim(rtrim(number_format($value, 3, ',', ''), '0'), ',');
}

/** «14 сентября», для прошлых лет – «14 сентября 2025». */
function gm_cab_date_ru($ts)
{
    $ts = (int) $ts;
    if ($ts <= 0) {
        return '';
    }
    $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    $dt = new DateTime('@' . $ts);
    $dt->setTimezone(new DateTimeZone('Europe/Moscow'));
    $text = (int) $dt->format('j') . ' ' . $months[(int) $dt->format('n') - 1];

    return $dt->format('Y') === date('Y') ? $text : $text . ' ' . $dt->format('Y');
}

/** Заказ завершён (выдан, отменён, отказ) или ещё в работе – по тексту статуса сайта или МойСклад. */
function gm_cab_status_kind($status)
{
    $status = mb_strtolower(trim((string) $status));
    if ($status === '') {
        return 'active';
    }
    if (mb_strpos($status, 'выдан') === 0 || mb_strpos($status, 'выполнен') === 0 || mb_strpos($status, 'получен') === 0) {
        return 'received';
    }
    foreach (['отмен', 'аннулир', 'отказ', 'отклон', 'неудач', 'возврат'] as $word) {
        if (mb_strpos($status, $word) === 0) {
            return 'cancelled';
        }
    }

    return 'active';
}

/** Дополняет заказы полями для страницы «Мои заказы»: вид статуса, год, дата словами. */
function gm_cab_decorate_orders(array $orders)
{
    foreach ($orders as $k => $order) {
        $kind = gm_cab_status_kind($order['status']);
        $orders[$k]['kind'] = $kind;
        $orders[$k]['done'] = $kind !== 'active';
        $orders[$k]['year'] = $order['ts'] > 0 ? gm_cab_date(date('Y-m-d H:i:s', $order['ts']), date_default_timezone_get(), 'Y') : '';
        $orders[$k]['when'] = gm_cab_date_ru($order['ts']);
        $orders[$k]['title'] = $order['status'] !== '' ? $order['status'] : 'Заказ принят';
    }

    return $orders;
}
