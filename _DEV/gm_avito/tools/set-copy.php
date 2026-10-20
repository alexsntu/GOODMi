<?php
/**
 * Запись текстов объявлений Авито в модуль (скил avito-listing-copy, 2026-10-20). Только CLI, из корня сайта:
 *   php set-copy.php <company_id> <copy.json> [--dry]
 * copy.json – [{"product_id": 123, "title": "…" (необязательно, ≤50 знаков), "intro": "…" (строки – абзацы)}].
 * Пишет title / intro с пометкой «исправлено вручную» (title_auto / intro_auto = N – автоматическое заполнение их не
 * перезапишет). --dry – только показать, что изменится. Печатает было → стало (первые знаки).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$company_id = (int) ($argv[1] ?? 0);
$copy = json_decode((string) file_get_contents($argv[2] ?? ''), true);
$dry = in_array('--dry', $argv, true);
if (!$company_id || !is_array($copy)) {
    exit("Нужно: company_id, copy.json [--dry]\n");
}

$done = 0;
foreach ($copy as $row) {
    $product_id = (int) ($row['product_id'] ?? 0);
    $item = fn_gm_avito_get_item($company_id, $product_id, true);
    if (!$item) {
        echo $product_id, ': нет строки товара', PHP_EOL;
        continue;
    }
    $update = ['updated_at' => TIME];
    if (isset($row['title']) && trim($row['title']) !== '') {
        $title = trim(preg_replace('/\s+/u', ' ', $row['title']));
        if (mb_strlen($title) > 50) {
            echo $product_id, ': заголовок длиннее 50 знаков (', mb_strlen($title), ') – пропущен', PHP_EOL;
        } else {
            $update += ['title' => fn_gm_avito_db_text($title), 'title_auto' => 'N'];
        }
    }
    if (isset($row['intro']) && trim($row['intro']) !== '') {
        $update += ['intro' => fn_gm_avito_db_text(trim(str_replace("\r", '', strip_tags($row['intro'])))), 'intro_auto' => 'N'];
    }
    echo $product_id, ': ', isset($update['title']) ? '«' . fn_gm_avito_xml_text($item['title']) . '» → «' . fn_gm_avito_xml_text($update['title']) . '»; ' : '',
        isset($update['intro']) ? 'о товаре: ' . mb_substr(str_replace("\n", ' / ', fn_gm_avito_xml_text($update['intro'])), 0, 90) . '…' : '', PHP_EOL;
    if (!$dry && count($update) > 1) {
        db_query('UPDATE ?:gm_avito_items SET ?u WHERE company_id = ?i AND product_id = ?i', $update, $company_id, $product_id);
        $done++;
    }
}
echo $dry ? 'проверка, ничего не записано' : 'записано: ' . $done, PHP_EOL;
