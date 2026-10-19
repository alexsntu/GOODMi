<?php
/**
 * GOODMi: заполнение «описания для Авито» у всех товаров витрины (cron, только CLI, 2026-10-19).
 *   php app/addons/gm_avito/bin/fill.php <company_id> [<company_id> …]
 * Новым товарам создаёт строки (выключенными) с заголовком и «о товаре», у остальных обновляет автоматическое «о товаре»
 * (правленое вручную не трогает).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
chdir(dirname(__DIR__, 4));
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$companies = array_filter(array_map('intval', array_slice($argv, 1)));
if (!$companies) {
    exit("Нужно: company_id (1 – Maxmobiles, 2 – GOODMi)\n");
}
foreach ($companies as $company_id) {
    $stats = fn_gm_avito_fill_items($company_id);
    echo 'витрина ', $company_id, ': новых ', $stats['created'], ', обновлено ', $stats['updated'], ', без изменений ', $stats['same'],
        ', правленых вручную ', $stats['manual'], PHP_EOL;
}
