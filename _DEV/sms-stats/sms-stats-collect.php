<?php
/**
 * СМС всех витрин (2026-10-18): раз в час (cron :05, /etc/cron.d/sms-stats) – баланс кабинета Таргет СМС (один на GOODMi и
 * Maxmobiles) и статистика всех отправленных с него СМС за последние 3 дня: статусы доставки приходят с опозданием, строки
 * обновляются. Для страницы админки «Покупатели → СМС: работа и баланс» (app/controllers/backend/sms_stats.php).
 * Текст СМС (в нём коды) и номер целиком не храним: отпечаток номера и последние 2 цифры. Только чтение кабинета оператора.
 * Первый запуск: --days=90 – загрузить всё, что хранит оператор (3 месяца). Таблицы – tables.sql рядом.
 */
error_reporting(E_ERROR);
chdir('/var/www/maxmobiles/data/www/maxmobiles.ru');
define('BOOTSTRAP', 1); define('DIR_ROOT', getcwd()); define('PRODUCT_STATUS', '');
// config.local.php включает E_ALL и ждёт адрес посетителя и $config['dir'] – без них каждый запуск писал Notice в журнал
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$config = ['dir' => ['root' => getcwd(), 'var' => getcwd() . '/var/', 'cache_misc' => getcwd() . '/var/cache/misc/']];
@include 'config.local.php';
error_reporting(E_ERROR);
$secrets = (array) include 'app/gm_cabinet/secrets.php';
mysqli_report(MYSQLI_REPORT_OFF); // как в PHP 7.4: ошибка запроса – false, а не исключение (в PHP 8.1+ по умолчанию исключения)
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);
$db->set_charset('utf8mb4');
$p = $config['table_prefix'];
$days = 3;
foreach ($argv as $a) { if (preg_match('/^--days=(\d+)$/', $a, $m)) { $days = max(1, min(90, (int) $m[1])); } }

function tsms($secrets, array $req)
{
    $req = ['security' => ['login' => (string) $secrets['targetsms_login'], 'password' => (string) $secrets['targetsms_password']]] + $req;
    $c = curl_init('https://sms.targetsms.ru/sendsmsjson.php');
    curl_setopt_array($c, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($req), CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8']]);
    $d = json_decode((string) curl_exec($c), true);
    curl_close($c);
    if (!is_array($d)) { throw new Exception('Таргет СМС не ответил'); }
    if (!empty($d['error'])) { throw new Exception('Таргет СМС: ' . $d['error']); }

    return $d;
}

$money = 0.0; $rows = 0; $error = '';
try {
    if (empty($secrets['targetsms_login']) || empty($secrets['targetsms_password'])) { throw new Exception('нет ключей Таргет СМС в secrets.php'); }
    $b = tsms($secrets, ['type' => 'balance']);
    $money = isset($b['money']['value']) ? (float) $b['money']['value'] : 0.0;

    // По одному дню за запрос – ответ без постраничной выдачи
    // collected_at – когда мы впервые увидели этот статус: тревоги в Telegram считают окно по нему, чтобы каждое недоставленное
    // СМС попало ровно в одну сводку (статус у оператора меняется раньше, чем мы его забираем). Присваивается до status
    $st = $db->prepare("INSERT INTO {$p}sms_stat (id_state, id_sms, ts, changed, kind, originator, operator, status, price, parts, part_no, phone_hash, phone_tail, collected_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())
        ON DUPLICATE KEY UPDATE collected_at = IF(status <> VALUES(status), VALUES(collected_at), collected_at),
            changed = VALUES(changed), status = VALUES(status), price = VALUES(price), operator = VALUES(operator)");
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', time() - $i * 86400);
        $d = tsms($secrets, ['type' => 'list_stats', 'stats' => ['date_start' => $day, 'date_stop' => $day, 'from_hour' => '00', 'from_minute' => '00',
            'to_hour' => '23', 'to_minute' => '59', 'state' => '', 'originator' => '', 'phone' => '', 'operator' => '']]);
        foreach ((array) (isset($d['stats']) ? $d['stats'] : []) as $s) {
            $phone = preg_replace('/\D/', '', (string) $s['phone']);
            $v = [(int) $s['id_state'], (int) $s['id_sms'], (int) strtotime((string) $s['time']), (int) strtotime((string) $s['time_change_state']),
                mb_substr((string) $s['name_delivery'], 0, 60), mb_substr((string) $s['originator'], 0, 20), mb_substr((string) $s['operator'], 0, 40),
                mb_substr((string) $s['status'], 0, 16), (float) $s['price'], (int) $s['num_parts'], (int) $s['part_no'],
                substr(md5('sms|' . $phone), 0, 12), substr($phone, -2)];
            $st->bind_param('iiiissssdiiss', ...$v);
            $st->execute();
            $rows++;
        }
    }
} catch (Exception $e) {
    $error = mb_substr($e->getMessage(), 0, 200);
}

$st = $db->prepare("INSERT INTO {$p}sms_balance (ts, money, stat_rows, error) VALUES (?, ?, ?, ?)");
$now = time();
$st->bind_param('idis', $now, $money, $rows, $error);
$st->execute();
// Почасовые отметки баланса храним год, статистику – как у оператора, без срока (несколько тысяч строк в год)
$db->query("DELETE FROM {$p}sms_balance WHERE ts < " . ($now - 366 * 86400));

echo date('Y-m-d H:i:s'), ($error === '' ? sprintf(' баланс %.2f ₽, строк статистики за %d дн.: %d', $money, $days, $rows) : ' ОШИБКА: ' . $error), "\n";
exit($error === '' ? 0 : 1);
