<?php
/**
 * Перенос данных маркетинг-портала (без бонусов – раздел не переносится по решению пользователя) (JSON из export-portal.js) в таблицы cscart_gm_mkt_* (2026-10-21).
 * Только CLI, из корня сайта: php import-portal.php <portal-export.json> [--dry] [--fresh]
 * Номера записей и время (UTC) сохраняются как в портале; повторный запуск перезаписывает те же номера (REPLACE).
 * --fresh – перед переносом очистить таблицы (окончательный перенос при переключении routines: удалённое в портале не остаётся).
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$data = json_decode((string) file_get_contents($argv[1] ?? ''), true);
if (!is_array($data)) {
    exit("Нужен файл выгрузки портала\n");
}
$dry = in_array('--dry', $argv, true);
$fresh = in_array('--fresh', $argv, true);
$t = 'fn_gm_mkt_db_text';
$map = [
    'drafts' => ['gm_mkt_drafts', static function ($r) use ($t) {
        return ['id' => $r['id'], 'source' => $r['source'], 'title' => $t($r['title']), 'text' => $t($r['text']), 'source_url' => $r['source_url'] !== null ? $t($r['source_url']) : null,
            'category' => $r['category'] !== null ? $t($r['category']) : null, 'status' => $r['status'], 'created_at' => $r['created_at']];
    }],
    'seen_state' => ['gm_mkt_seen', static function ($r) use ($t) {
        $url = $t($r['item_url']);

        return ['state_key' => $r['key'], 'url_hash' => sha1($url), 'item_url' => $url, 'seen_at' => $r['seen_at']];
    }],
    'topics' => ['gm_mkt_topics', static function ($r) use ($t) {
        return ['id' => $r['id'], 'title' => $t($r['title']), 'pitch' => $t($r['pitch']), 'source_url' => $r['source_url'] !== null ? $t($r['source_url']) : null,
            'category' => $r['category'] !== null ? $t($r['category']) : null, 'status' => $r['status'], 'created_at' => $r['created_at']];
    }],
    'tasks' => ['gm_mkt_tasks', static function ($r) use ($t) {
        return ['id' => $r['id'], 'title' => $t($r['title']), 'description' => $r['description'] !== null ? $t($r['description']) : null, 'due_at' => $r['due_at'],
            'remind_at' => $r['remind_at'], 'status' => $r['status'], 'reminded' => (int) $r['reminded'], 'created_at' => $r['created_at']];
    }],
    'plan_items' => ['gm_mkt_plan', static function ($r) use ($t) {
        return ['id' => $r['id'], 'title' => $t($r['title']), 'type' => $r['type'], 'start_date' => $r['start_date'] ?: null, 'end_date' => $r['end_date'] ?: null,
            'channels' => $r['channels'] !== null ? $t($r['channels']) : null, 'description' => $r['description'] !== null ? $t($r['description']) : null,
            'status' => $r['status'], 'created_at' => $r['created_at']];
    }],
    'promo_ideas' => ['gm_mkt_promo_ideas', static function ($r) use ($t) {
        return ['id' => $r['id'], 'title' => $t($r['title']), 'rationale' => $t($r['rationale']), 'mechanic' => $r['mechanic'] !== null ? $t($r['mechanic']) : null,
            'category' => $r['category'] !== null ? $t($r['category']) : null, 'status' => $r['status'], 'created_at' => $r['created_at']];
    }],
    'repost_suggestions' => ['gm_mkt_reposts', static function ($r) use ($t) {
        return ['id' => $r['id'], 'title' => $t($r['title']), 'summary' => $t($r['summary']), 'source_channel' => $r['source_channel'] !== null ? $t($r['source_channel']) : null,
            'source_url' => $r['source_url'] !== null ? $t($r['source_url']) : null, 'status' => $r['status'], 'created_at' => $r['created_at']];
    }],
];
foreach ($map as $src => [$table, $conv]) {
    $rows = (array) ($data[$src] ?? []);
    if ($fresh && !$dry) {
        db_query('DELETE FROM ?:' . $table);
    }
    $n = 0;
    foreach ($rows as $row) {
        $row = $conv($row);
        if (!$dry) {
            db_query('REPLACE INTO ?:' . $table . ' ?e', $row);
        }
        $n++;
    }
    $count = $dry ? '-' : db_get_field('SELECT COUNT(*) FROM ?:' . $table);
    echo str_pad($src, 20), ' → ', str_pad($table, 22), ' строк ', $n, ', в таблице ', $count, "\n";
}
echo $dry ? "DRY RUN\n" : "Готово\n";
