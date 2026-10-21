<?php
/**
 * GOODMi: модуль «Маркетинг» – маркетинг-портал (черновики постов, темы блога, задачи, план, идеи акций, репосты, списание
 * бонусов БонусПлюс) внутри админки CS-Cart вместо отдельного портала на VPS price.maxmobiles.ru (2026-10-21).
 * Данные – таблицы cscart_gm_mkt_* (_DEV/gm_marketing/tables.sql), страницы – controllers/backend/gm_marketing.php,
 * API для облачных routines – controllers/frontend/gm_marketing.php (те же пути и ответы, что у портала: /api/... → /mkt-api/...).
 * Секреты (токен API routines, ключ БонусПлюс) – <папка пользователя сайта>/private/gm_marketing.json, не в базе и не в репозитории.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

// ключи «уже видели» (state/:key), как ALLOWED_KEYS портала
const GM_MKT_STATE_KEYS = ['novinki', 'aktsii', 'ucenka', 'blog', 'friday', 'competitor', 'repost'];

const GM_MKT_SOURCES = [
    'novinki' => 'Новинки',
    'aktsii' => 'Акции',
    'ucenka' => 'Уценка',
    'blog' => 'Блог',
    'friday' => 'Пятничный пост',
    'competitor' => 'Конкуренты',
];

const GM_MKT_PLAN_TYPES = [
    'promo' => 'Сезонная акция',
    'campaign' => 'Товарная кампания',
    'news_hook' => 'Инфоповод',
    'event' => 'Личное мероприятие',
];

const GM_MKT_PLAN_STATUSES = [
    'planned' => 'Запланировано',
    'in_progress' => 'В работе',
    'done' => 'Готово',
    'cancelled' => 'Отменено',
];

const GM_MKT_TOPIC_STATUSES = ['pending', 'approved', 'rejected', 'generated'];

// облачные routines claude.ai: кнопки «Запустить» на главной странице модуля (как ROUTINES портала)
const GM_MKT_ROUTINES = [
    ['name' => 'Новинки', 'cadence' => 'будни 10:33', 'url' => 'https://claude.ai/code/routines/trig_01J15fDUaLRqpm14TxzLZs5d'],
    ['name' => 'Акции и уценка', 'cadence' => 'будни 10:40', 'url' => 'https://claude.ai/code/routines/trig_011Yk3zJWZKLz1ppg3iPDscE'],
    ['name' => 'Темы блога (предложить)', 'cadence' => 'будни 10:00 и 17:00', 'url' => 'https://claude.ai/code/routines/trig_013xSmLjQS2QE6yjb7gGs1Zp'],
    ['name' => 'Блог (генерация статей)', 'cadence' => 'будни 19:00', 'url' => 'https://claude.ai/code/routines/trig_01S3Goi79TaHVVkYgvYTU7CC'],
    ['name' => 'Пятничный пост', 'cadence' => 'пятница 09:54', 'url' => 'https://claude.ai/code/routines/trig_01VC6LnZDdtvLKEEYQAN1VBu'],
    ['name' => 'Мониторинг конкурентов', 'cadence' => 'будни 11:00', 'url' => 'https://claude.ai/code/routines/trig_0173oTYyg2LY1yaLJQGXfm2c'],
    ['name' => 'Идеи акций', 'cadence' => 'понедельник 11:30', 'url' => 'https://claude.ai/code/routines/trig_016TWCLzCrq4bA9QdUXY1xsB'],
    ['name' => 'Предложение постов', 'cadence' => 'будни, каждые 2 часа 10:30–16:30', 'url' => 'https://claude.ai/code/routines/trig_01A6iRVBPDPaH2Q1UEQ9R11P'],
];

/**
 * Секреты модуля: {"api_token": "...", "bonusplus_api_key": "...", "bonusplus_api_base": "https://bonusplus.pro/api"}
 */
function fn_gm_mkt_config($name = null)
{
    static $config = null;
    if ($config === null) {
        $file = dirname(rtrim(DIR_ROOT, '/'), 2) . '/private/gm_marketing.json';
        $config = is_readable($file) ? (array) json_decode((string) file_get_contents($file), true) : [];
    }

    return $name === null ? $config : (string) ($config[$name] ?? '');
}

/**
 * Текст для базы: соединение CS-Cart в utf8 (3 байта), символы вне BMP (эмодзи) – как &#N;
 */
function fn_gm_mkt_db_text($text)
{
    $text = mb_convert_encoding((string) $text, 'UTF-8', 'UTF-8');

    return (string) preg_replace_callback('/[\x{10000}-\x{10FFFF}]/u', static function ($m) {
        return '&#' . mb_ord($m[0], 'UTF-8') . ';';
    }, $text);
}

/**
 * Обратно: &#N; для символов вне BMP – в сами символы (для API и показа)
 */
function fn_gm_mkt_out_text($text)
{
    if ($text === null) {
        return null;
    }

    return (string) preg_replace_callback('/&#(\d{5,7});/', static function ($m) {
        $code = (int) $m[1];

        return $code >= 0x10000 && $code <= 0x10FFFF ? mb_chr($code, 'UTF-8') : $m[0];
    }, (string) $text);
}

function fn_gm_mkt_now()
{
    return gmdate('Y-m-d H:i:s');
}

/**
 * UTC из базы → «ГГГГ-ММ-ДД ЧЧ:ММ» по Москве
 */
function fn_gm_mkt_msk($utc)
{
    if (empty($utc) || strpos((string) $utc, '0000-00-00') === 0) {
        return '';
    }
    try {
        $d = new DateTime((string) $utc, new DateTimeZone('UTC'));
        $d->setTimezone(new DateTimeZone('Europe/Moscow'));

        return $d->format('Y-m-d H:i');
    } catch (Exception $e) {
        return (string) $utc;
    }
}

/**
 * Поле datetime-local («ГГГГ-ММ-ДДTЧЧ:ММ», время московское) → UTC для базы
 */
function fn_gm_mkt_msk_to_utc($local)
{
    $local = trim((string) $local);
    if ($local === '') {
        return null;
    }
    try {
        $d = new DateTime(str_replace('T', ' ', $local), new DateTimeZone('Europe/Moscow'));
        $d->setTimezone(new DateTimeZone('UTC'));

        return $d->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

function fn_gm_mkt_date_or_null($value)
{
    $value = trim((string) $value);

    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}

/**
 * Строки из базы для выдачи: эмодзи обратно, числа – числами (как в ответах портала)
 */
function fn_gm_mkt_rows(array $rows, array $int_fields = ['id'])
{
    foreach ($rows as &$row) {
        foreach ($row as $key => $value) {
            if (in_array($key, $int_fields, true)) {
                $row[$key] = $value === null ? null : (int) $value;
            } elseif (is_string($value)) {
                $row[$key] = fn_gm_mkt_out_text($value);
            }
        }
    }
    unset($row);

    return $rows;
}

// ---------- «Уже видели» ----------

function fn_gm_mkt_get_seen($key)
{
    return array_map('fn_gm_mkt_out_text', db_get_fields('SELECT item_url FROM ?:gm_mkt_seen WHERE state_key = ?s ORDER BY seen_at, item_url', $key));
}

function fn_gm_mkt_add_seen($key, array $urls)
{
    $now = fn_gm_mkt_now();
    $added = 0;
    foreach ($urls as $url) {
        $url = fn_gm_mkt_db_text(trim((string) $url));
        if ($url === '') {
            continue;
        }
        db_query('INSERT IGNORE INTO ?:gm_mkt_seen ?e', ['state_key' => $key, 'url_hash' => sha1($url), 'item_url' => $url, 'seen_at' => $now]);
        $added++;
    }

    return $added;
}

// ---------- Черновики ----------

function fn_gm_mkt_create_draft(array $d)
{
    return (int) db_query('INSERT INTO ?:gm_mkt_drafts ?e', [
        'source' => (string) $d['source'],
        'title' => fn_gm_mkt_db_text($d['title']),
        'text' => fn_gm_mkt_db_text($d['text']),
        'source_url' => !empty($d['source_url']) ? fn_gm_mkt_db_text($d['source_url']) : null,
        'category' => !empty($d['category']) ? fn_gm_mkt_db_text($d['category']) : null,
        'status' => 'new',
        'created_at' => fn_gm_mkt_now(),
    ]);
}

function fn_gm_mkt_list(string $table, array $filters = [], string $order = 'created_at DESC, id DESC', int $limit = 0)
{
    $where = '1';
    foreach ($filters as $field => $value) {
        if ($value !== null && $value !== '') {
            $where .= db_quote(' AND ?f = ?s', $field, $value);
        }
    }

    return db_get_array('SELECT * FROM ?:' . $table . ' WHERE ' . $where . ' ORDER BY ' . $order . ($limit > 0 ? db_quote(' LIMIT ?i', $limit) : ''));
}

function fn_gm_mkt_set_status(string $table, $id, string $status)
{
    db_query('UPDATE ?:' . $table . ' SET status = ?s WHERE id = ?i', $status, $id);
}

function fn_gm_mkt_draft_stats()
{
    $rows = db_get_hash_array(
        "SELECT source, COUNT(*) AS total, SUM(status = 'new') AS new_count, SUM(status = 'posted') AS posted_count,"
        . " SUM(status = 'dismissed') AS dismissed_count, MAX(created_at) AS last_at FROM ?:gm_mkt_drafts GROUP BY source",
        'source'
    );
    $stats = [];
    foreach (GM_MKT_SOURCES as $key => $label) {
        $row = $rows[$key] ?? [];
        $stats[] = [
            'key' => $key,
            'label' => $label,
            'total' => (int) ($row['total'] ?? 0),
            'new_count' => (int) ($row['new_count'] ?? 0),
            'posted_count' => (int) ($row['posted_count'] ?? 0),
            'dismissed_count' => (int) ($row['dismissed_count'] ?? 0),
            'last_at' => fn_gm_mkt_msk($row['last_at'] ?? ''),
        ];
    }

    return $stats;
}

function fn_gm_mkt_topic_stats()
{
    $stats = array_fill_keys(GM_MKT_TOPIC_STATUSES, 0);
    foreach (db_get_hash_single_array('SELECT status, COUNT(*) AS n FROM ?:gm_mkt_topics GROUP BY status', ['status', 'n']) as $status => $n) {
        $stats[$status] = (int) $n;
    }

    return $stats;
}

/**
 * Счётчики «ждут решения» для вкладок и блока «Требует внимания»
 */
function fn_gm_mkt_nav_counts()
{
    return [
        'drafts' => (int) db_get_field("SELECT COUNT(*) FROM ?:gm_mkt_drafts WHERE status = 'new'"),
        'topics' => (int) db_get_field("SELECT COUNT(*) FROM ?:gm_mkt_topics WHERE status = 'pending'"),
        'promo_ideas' => (int) db_get_field("SELECT COUNT(*) FROM ?:gm_mkt_promo_ideas WHERE status = 'pending'"),
        'reposts' => (int) db_get_field("SELECT COUNT(*) FROM ?:gm_mkt_reposts WHERE status = 'pending'"),
        'bonuses' => (int) db_get_field("SELECT COUNT(*) FROM ?:gm_mkt_bonus_batches WHERE status = 'executed'"),
    ];
}

// ---------- Темы, идеи, репосты ----------

function fn_gm_mkt_create_topic(array $d)
{
    return (int) db_query('INSERT INTO ?:gm_mkt_topics ?e', [
        'title' => fn_gm_mkt_db_text($d['title']),
        'pitch' => fn_gm_mkt_db_text($d['pitch']),
        'source_url' => !empty($d['source_url']) ? fn_gm_mkt_db_text($d['source_url']) : null,
        'category' => !empty($d['category']) ? fn_gm_mkt_db_text($d['category']) : null,
        'status' => 'pending',
        'created_at' => fn_gm_mkt_now(),
    ]);
}

function fn_gm_mkt_create_promo_idea(array $d)
{
    return (int) db_query('INSERT INTO ?:gm_mkt_promo_ideas ?e', [
        'title' => fn_gm_mkt_db_text($d['title']),
        'rationale' => fn_gm_mkt_db_text($d['rationale']),
        'mechanic' => !empty($d['mechanic']) ? fn_gm_mkt_db_text($d['mechanic']) : null,
        'category' => !empty($d['category']) ? fn_gm_mkt_db_text($d['category']) : null,
        'status' => 'pending',
        'created_at' => fn_gm_mkt_now(),
    ]);
}

function fn_gm_mkt_create_repost(array $d)
{
    return (int) db_query('INSERT INTO ?:gm_mkt_reposts ?e', [
        'title' => fn_gm_mkt_db_text($d['title']),
        'summary' => fn_gm_mkt_db_text($d['summary']),
        'source_channel' => !empty($d['source_channel']) ? fn_gm_mkt_db_text($d['source_channel']) : null,
        'source_url' => !empty($d['source_url']) ? fn_gm_mkt_db_text($d['source_url']) : null,
        'status' => 'pending',
        'created_at' => fn_gm_mkt_now(),
    ]);
}

// ---------- Задачи ----------

function fn_gm_mkt_create_task(array $d)
{
    return (int) db_query('INSERT INTO ?:gm_mkt_tasks ?e', [
        'title' => fn_gm_mkt_db_text($d['title']),
        'description' => !empty($d['description']) ? fn_gm_mkt_db_text($d['description']) : null,
        'due_at' => $d['due_at'] ?? null,
        'remind_at' => $d['remind_at'] ?? null,
        'status' => 'pending',
        'reminded' => 0,
        'created_at' => fn_gm_mkt_now(),
    ]);
}

function fn_gm_mkt_list_tasks($status = null)
{
    return db_get_array(
        'SELECT * FROM ?:gm_mkt_tasks WHERE 1' . ($status ? db_quote(' AND status = ?s', $status) : '')
        . ' ORDER BY (due_at IS NULL), due_at ASC, created_at DESC'
    );
}

/**
 * Задачи, по которым пора напомнить (routine «Напоминания» шлёт их в Telegram и отмечает reminded)
 */
function fn_gm_mkt_due_tasks()
{
    return db_get_array(
        "SELECT * FROM ?:gm_mkt_tasks WHERE status = 'pending' AND reminded = 0 AND remind_at IS NOT NULL AND remind_at <= ?s ORDER BY remind_at ASC",
        fn_gm_mkt_now()
    );
}

// ---------- План ----------

function fn_gm_mkt_plan_data(array $d)
{
    $type = (string) ($d['type'] ?? '');

    return [
        'title' => fn_gm_mkt_db_text(trim((string) ($d['title'] ?? ''))),
        'type' => isset(GM_MKT_PLAN_TYPES[$type]) ? $type : null,
        'start_date' => fn_gm_mkt_date_or_null($d['start_date'] ?? ''),
        'end_date' => fn_gm_mkt_date_or_null($d['end_date'] ?? ''),
        'channels' => trim((string) ($d['channels'] ?? '')) !== '' ? fn_gm_mkt_db_text(trim((string) $d['channels'])) : null,
        'description' => trim((string) ($d['description'] ?? '')) !== '' ? fn_gm_mkt_db_text(trim((string) $d['description'])) : null,
    ];
}

function fn_gm_mkt_list_plan($status = null)
{
    return db_get_array(
        'SELECT * FROM ?:gm_mkt_plan WHERE 1' . ($status ? db_quote(' AND status = ?s', $status) : '')
        . ' ORDER BY (start_date IS NULL), start_date ASC, created_at DESC'
    );
}

/**
 * Календарь месяца «ГГГГ-ММ»: недели с понедельника, в каждом дне – мероприятия, которые на него приходятся
 */
function fn_gm_mkt_calendar($month, array $items)
{
    [$year, $mon] = array_map('intval', explode('-', $month));
    $days = (int) date('t', mktime(0, 0, 0, $mon, 1, $year));
    $first_weekday = ((int) date('N', mktime(0, 0, 0, $mon, 1, $year))) - 1;
    $cells = array_fill(0, $first_weekday, null);
    for ($day = 1; $day <= $days; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $mon, $day);
        $day_items = array_values(array_filter($items, static function ($it) use ($date) {
            return !empty($it['start_date']) && $date >= $it['start_date'] && $date <= ($it['end_date'] ?: $it['start_date']);
        }));
        $cells[] = ['day' => $day, 'date' => $date, 'items' => $day_items];
    }
    while (count($cells) % 7 !== 0) {
        $cells[] = null;
    }

    return array_chunk($cells, 7);
}

// ---------- БонусПлюс ----------

function fn_gm_mkt_bp_configured()
{
    return fn_gm_mkt_config('bonusplus_api_key') !== '';
}

/**
 * Запрос к API БонусПлюс; ошибка – исключение с кодом и телом ответа
 */
function fn_gm_mkt_bp_request($method, $path, $body = null)
{
    $base = fn_gm_mkt_config('bonusplus_api_base') ?: 'https://bonusplus.pro/api';
    $ch = curl_init(rtrim($base, '/') . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: ApiKey ' . base64_encode(fn_gm_mkt_config('bonusplus_api_key')),
            'Content-Type: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($response === false || $code < 200 || $code >= 300) {
        throw new Exception('БонусПлюс ' . $method . ' ' . $path . ' -> ' . ($code ?: $error) . ': ' . mb_substr((string) $response, 0, 300));
    }

    return (array) json_decode((string) $response, true);
}

// активные (availableBonuses) и ожидающие (notActiveBonuses) бонусы клиента
function fn_gm_mkt_bp_customer($phone)
{
    return fn_gm_mkt_bp_request('GET', '/customer?phone=' . rawurlencode($phone));
}

// amount < 0 – списание. НЕ использовать transactionType 30 для «активации» ожидающих бонусов: это отдельное начисление,
// а не перевод – задваивает баланс (проверено в портале 2026-09-02)
function fn_gm_mkt_bp_patch_balance($phone, $amount)
{
    return fn_gm_mkt_bp_request('PATCH', '/customer/' . rawurlencode($phone) . '/balance', ['amount' => $amount]);
}

function fn_gm_mkt_normalize_phone($raw)
{
    $digits = preg_replace('/\D/', '', (string) $raw);
    if (strlen($digits) === 11 && $digits[0] === '8') {
        return '7' . substr($digits, 1);
    }
    if (strlen($digits) === 10) {
        return '7' . $digits;
    }

    return $digits;
}

/**
 * Строки «телефон сумма» → [телефон => сумма] (дубли телефонов складываются) и [телефон => сколько строк]
 */
function fn_gm_mkt_parse_bonus_list($text)
{
    $totals = [];
    $dups = [];
    foreach (preg_split('/\r?\n/', (string) $text) as $line) {
        $parts = preg_split('/[\s,;]+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) < 2) {
            continue;
        }
        $phone = fn_gm_mkt_normalize_phone($parts[0]);
        $amount = (float) str_replace(',', '.', $parts[1]);
        if (!preg_match('/^7\d{10}$/', $phone) || !is_numeric(str_replace(',', '.', $parts[1])) || $amount <= 0) {
            continue;
        }
        $totals[$phone] = ($totals[$phone] ?? 0) + $amount;
        $dups[$phone] = ($dups[$phone] ?? 0) + 1;
    }

    return [$totals, $dups];
}

function fn_gm_mkt_bonus_log($batch_id, $action, $phone = null, $amount = null, $details = null)
{
    db_query('INSERT INTO ?:gm_mkt_bonus_log ?e', [
        'batch_id' => (int) $batch_id,
        'phone' => $phone,
        'action' => $action,
        'amount' => $amount,
        'details' => $details !== null ? fn_gm_mkt_db_text($details) : null,
        'user_id' => (int) (Tygh::$app['session']['auth']['user_id'] ?? 0),
        'created_at' => fn_gm_mkt_now(),
    ]);
}

/**
 * Превью партии списания: по каждому телефону – текущий баланс и ожидающие бонусы в БонусПлюс
 */
function fn_gm_mkt_bonus_preview($list)
{
    [$totals, $dups] = fn_gm_mkt_parse_bonus_list($list);
    if (!$totals) {
        return 0;
    }
    $batch_id = (int) db_query('INSERT INTO ?:gm_mkt_bonus_batches ?e', [
        'status' => 'preview',
        'user_id' => (int) (Tygh::$app['session']['auth']['user_id'] ?? 0),
        'created_at' => fn_gm_mkt_now(),
    ]);
    fn_gm_mkt_bonus_log($batch_id, 'batch_created', null, null, 'строк во входном списке: ' . array_sum($dups) . ', уникальных телефонов: ' . count($totals));
    foreach ($totals as $phone => $requested) {
        $phone = (string) $phone;
        $name = null;
        $balance = 0;
        $pending = 0;
        try {
            $customer = fn_gm_mkt_bp_customer($phone);
            $balance = (float) ($customer['availableBonuses'] ?? 0);
            $pending = (float) ($customer['notActiveBonuses'] ?? 0);
            $name = $customer['person']['fn'] ?? null;
        } catch (Exception $e) {
            fn_gm_mkt_bonus_log($batch_id, 'preview_error', $phone, null, $e->getMessage());
        }
        db_query('INSERT INTO ?:gm_mkt_bonus_items ?e', [
            'batch_id' => $batch_id,
            'phone' => $phone,
            'name' => $name !== null ? fn_gm_mkt_db_text($name) : null,
            'dup_count' => $dups[$phone] ?? 1,
            'requested_deduct' => $requested,
            'balance_at_preview' => $balance,
            'pending_at_preview' => $pending,
        ]);
        if (($dups[$phone] ?? 1) > 1) {
            fn_gm_mkt_bonus_log($batch_id, 'duplicate_summed', $phone, $requested, $dups[$phone] . ' строк в исходном списке сложены в одну сумму');
        }
    }

    return $batch_id;
}

/**
 * Шаг 1: списать по превью (не больше активного баланса); где не хватило, но есть ожидающие бонусы – шаг 2 после ручной
 * активации в кабинете БонусПлюс (API активировать не умеет)
 */
function fn_gm_mkt_bonus_execute($batch_id)
{
    $any_step2 = false;
    foreach (db_get_array('SELECT * FROM ?:gm_mkt_bonus_items WHERE batch_id = ?i ORDER BY id', $batch_id) as $it) {
        $actual = min((float) $it['requested_deduct'], (float) $it['balance_at_preview']);
        $error = null;
        if ($actual > 0) {
            try {
                fn_gm_mkt_bp_patch_balance($it['phone'], -$actual);
                fn_gm_mkt_bonus_log($batch_id, 'deduct_step1', $it['phone'], $actual, 'баланс до списания: ' . (float) $it['balance_at_preview']);
            } catch (Exception $e) {
                $error = $e->getMessage();
                fn_gm_mkt_bonus_log($batch_id, 'error', $it['phone'], null, $error);
            }
        }
        $shortfall = (float) $it['requested_deduct'] - ($error ? 0 : $actual);
        $step2 = !$error && $shortfall > 0.001 && (float) $it['pending_at_preview'] > 0;
        $any_step2 = $any_step2 || $step2;
        db_query('UPDATE ?:gm_mkt_bonus_items SET ?u WHERE id = ?i', [
            'deducted_step1' => $error ? 0 : $actual,
            'step2_needed' => $step2 ? 1 : 0,
            'error' => $error !== null ? fn_gm_mkt_db_text($error) : null,
        ], $it['id']);
    }
    fn_gm_mkt_set_status('gm_mkt_bonus_batches', $batch_id, $any_step2 ? 'executed' : 'done');
    fn_gm_mkt_bonus_log($batch_id, $any_step2 ? 'step1_finished_awaiting_activation' : 'step1_finished_done');
}

/**
 * Шаг 2: после ручной активации – свежий баланс и доудержание недостающего
 */
function fn_gm_mkt_bonus_followup($batch_id)
{
    $still = 0;
    foreach (db_get_array('SELECT * FROM ?:gm_mkt_bonus_items WHERE batch_id = ?i AND step2_needed = 1 AND step2_done = 0 ORDER BY id', $batch_id) as $it) {
        $shortfall = (float) $it['requested_deduct'] - (float) $it['deducted_step1'];
        $error = null;
        try {
            $customer = fn_gm_mkt_bp_customer($it['phone']);
        } catch (Exception $e) {
            $error = $e->getMessage();
            fn_gm_mkt_bonus_log($batch_id, 'error', $it['phone'], null, $error);
            db_query('UPDATE ?:gm_mkt_bonus_items SET error = ?s WHERE id = ?i', fn_gm_mkt_db_text($error), $it['id']);
            $still++;
            continue;
        }
        $balance = (float) ($customer['availableBonuses'] ?? 0);
        $pending = (float) ($customer['notActiveBonuses'] ?? 0);
        $now = min($shortfall, $balance);
        if ($now > 0) {
            try {
                fn_gm_mkt_bp_patch_balance($it['phone'], -$now);
                fn_gm_mkt_bonus_log($batch_id, 'deduct_step2', $it['phone'], $now, 'баланс после активации: ' . $balance);
            } catch (Exception $e) {
                $error = $e->getMessage();
                fn_gm_mkt_bonus_log($batch_id, 'error', $it['phone'], null, $error);
            }
        }
        $more = !$error && $pending > 0 && $now < $shortfall;
        if ($more) {
            $still++;
            fn_gm_mkt_bonus_log($batch_id, 'still_not_activated', $it['phone'], null, 'ожидает: ' . $pending . ', недосписано: ' . ($shortfall - $now));
        }
        db_query(
            'UPDATE ?:gm_mkt_bonus_items SET deducted_step2 = deducted_step2 + ?d, step2_needed = ?i, pending_at_preview = ?d, step2_done = ?i, error = ?s WHERE id = ?i',
            $error ? 0 : $now,
            $more ? 1 : 0,
            $pending,
            $more ? 0 : 1,
            $error !== null ? fn_gm_mkt_db_text($error) : null,
            $it['id']
        );
    }
    fn_gm_mkt_set_status('gm_mkt_bonus_batches', $batch_id, $still ? 'executed' : 'done');
    fn_gm_mkt_bonus_log($batch_id, $still ? 'step2_finished_partial' : 'step2_finished_done');
}

/**
 * Партия для страницы: строки с итогами и списки «нужна активация» / «нечего активировать»
 */
function fn_gm_mkt_bonus_batch_view($batch_id)
{
    $batch = db_get_row('SELECT * FROM ?:gm_mkt_bonus_batches WHERE id = ?i', $batch_id);
    if (!$batch) {
        return [];
    }
    $items = fn_gm_mkt_rows(db_get_array('SELECT * FROM ?:gm_mkt_bonus_items WHERE batch_id = ?i ORDER BY id', $batch_id), ['id', 'batch_id', 'dup_count', 'step2_needed', 'step2_done']);
    $totals = ['requested' => 0, 'deducted' => 0, 'remaining' => 0];
    foreach ($items as &$it) {
        $it['total_deducted'] = (float) $it['deducted_step1'] + (float) $it['deducted_step2'];
        $it['shortfall'] = (float) $it['requested_deduct'] - $it['total_deducted'];
        $it['still_pending'] = $it['step2_needed'] === 1 && $it['step2_done'] === 0;
        $totals['requested'] += (float) $it['requested_deduct'];
        $totals['deducted'] += $it['total_deducted'];
        $totals['remaining'] += max(0, $it['shortfall']);
        // целые для показа (в Smarty 5 нет модификатора round)
        $it['requested_r'] = (int) round((float) $it['requested_deduct']);
        $it['balance_r'] = (int) round((float) $it['balance_at_preview']);
        $it['pending_r'] = (int) round((float) $it['pending_at_preview']);
        $it['deducted_r'] = (int) round($it['total_deducted']);
        $it['shortfall_r'] = (int) round(max(0, $it['shortfall']));
    }
    unset($it);
    foreach (['requested', 'deducted', 'remaining'] as $key) {
        $totals[$key . '_r'] = (int) round($totals[$key]);
    }
    $batch['created_at_msk'] = fn_gm_mkt_msk($batch['created_at']);

    return [
        'batch' => $batch,
        'items' => $items,
        'totals' => $totals,
        'partial' => array_values(array_filter($items, static function ($it) { return $it['still_pending'] && (float) $it['deducted_step1'] > 0; })),
        'zero' => array_values(array_filter($items, static function ($it) { return $it['still_pending'] && (float) $it['deducted_step1'] == 0; })),
        'dups' => array_values(array_filter($items, static function ($it) { return $it['dup_count'] > 1; })),
        'uncollectible' => array_values(array_filter($items, static function ($it) use ($batch) {
            return $it['shortfall'] > 0.001 && !$it['still_pending'] && $batch['status'] !== 'preview';
        })),
    ];
}

// ---------- API для routines ----------

/**
 * Запрос routine: метод, путь после /mkt-api/ (как после /api/ у портала), параметры строки и тело JSON.
 * Возвращает [HTTP-код, ответ]; ответы – те же ключи, что у портала (seen, drafts, topics, tasks, items, ideas, suggestions).
 */
function fn_gm_mkt_api($method, $path, array $query, array $body)
{
    $parts = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
    $route = $method . ' ' . implode('/', array_map(static function ($p) { return ctype_digit($p) ? ':id' : $p; }, $parts));
    $id = 0;
    foreach ($parts as $p) {
        if (ctype_digit($p)) {
            $id = (int) $p;
        }
    }
    $limit = isset($query['limit']) ? max(1, (int) $query['limit']) : 20;

    if (($parts[0] ?? '') === 'state' && isset($parts[1])) {
        if (!in_array($parts[1], GM_MKT_STATE_KEYS, true)) {
            return [400, ['error' => 'unknown key']];
        }
        if ($method === 'GET' && count($parts) === 2) {
            return [200, ['seen' => fn_gm_mkt_get_seen($parts[1])]];
        }
        if ($method === 'POST' && count($parts) === 2) {
            $urls = isset($body['urls']) && is_array($body['urls']) ? $body['urls'] : [];
            fn_gm_mkt_add_seen($parts[1], $urls);

            return [200, ['ok' => true, 'added' => count($urls)]];
        }
    }

    switch ($route) {
        case 'POST drafts':
            if (empty($body['source']) || empty($body['title']) || empty($body['text'])) {
                return [400, ['error' => 'source, title and text are required']];
            }

            return [200, ['ok' => true, 'id' => fn_gm_mkt_create_draft($body)]];

        case 'GET drafts':
            return [200, ['drafts' => fn_gm_mkt_rows(fn_gm_mkt_list('gm_mkt_drafts', [
                'source' => $query['source'] ?? null,
                'category' => $query['category'] ?? null,
                'status' => $query['status'] ?? null,
            ], 'created_at DESC, id DESC', $limit))]];

        case 'POST topics':
            if (empty($body['title']) || empty($body['pitch'])) {
                return [400, ['error' => 'title and pitch are required']];
            }

            return [200, ['ok' => true, 'id' => fn_gm_mkt_create_topic($body)]];

        case 'GET topics':
            $status = $query['status'] ?? null;
            if ($status && !in_array($status, GM_MKT_TOPIC_STATUSES, true)) {
                return [400, ['error' => 'unknown status']];
            }

            return [200, ['topics' => fn_gm_mkt_rows(fn_gm_mkt_list('gm_mkt_topics', ['status' => $status]))]];

        case 'POST topics/:id/generated':
            fn_gm_mkt_set_status('gm_mkt_topics', $id, 'generated');

            return [200, ['ok' => true]];

        case 'GET tasks/due':
            return [200, ['tasks' => fn_gm_mkt_rows(fn_gm_mkt_due_tasks(), ['id', 'reminded'])]];

        case 'POST tasks/:id/reminded':
            db_query('UPDATE ?:gm_mkt_tasks SET reminded = 1 WHERE id = ?i', $id);

            return [200, ['ok' => true]];

        case 'GET plan':
            return [200, ['items' => fn_gm_mkt_rows(fn_gm_mkt_list_plan($query['status'] ?? null))]];

        case 'POST promo-ideas':
            if (empty($body['title']) || empty($body['rationale'])) {
                return [400, ['error' => 'title and rationale are required']];
            }

            return [200, ['ok' => true, 'id' => fn_gm_mkt_create_promo_idea($body)]];

        case 'GET promo-ideas':
            return [200, ['ideas' => fn_gm_mkt_rows(fn_gm_mkt_list('gm_mkt_promo_ideas', ['status' => $query['status'] ?? null], 'created_at DESC, id DESC', $limit))]];

        case 'POST repost-suggestions':
            if (empty($body['title']) || empty($body['summary'])) {
                return [400, ['error' => 'title and summary are required']];
            }

            return [200, ['ok' => true, 'id' => fn_gm_mkt_create_repost($body)]];

        case 'GET repost-suggestions':
            return [200, ['suggestions' => fn_gm_mkt_rows(fn_gm_mkt_list('gm_mkt_reposts', ['status' => $query['status'] ?? null], 'created_at DESC, id DESC', $limit))]];
    }

    return [404, ['error' => 'not found']];
}
