<?php
/**
 * Начальные настройки этапа 2 (2026-10-19). Только CLI, из корня сайта: php setup-stage2.php <common-block-goodmi.html>
 * GOODMi: только товары с остатком, новый текст в пробных категориях «Беспроводные наушники» и «Повербанки», общий блок.
 * Maxmobiles: все включённые товары.
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$common = trim((string) file_get_contents($argv[1] ?? ''));
if ($common === '') {
    exit("Нужен файл общего блока\n");
}

$pilot = db_get_fields(
    'SELECT t.category_id FROM ?:gm_avito_templates AS t INNER JOIN ?:category_descriptions AS cd ON cd.category_id = t.category_id AND cd.lang_code = ?s'
    . ' WHERE t.company_id = ?i AND cd.category IN (?a)',
    'ru',
    2,
    ['Беспроводные наушники', 'Повербанки']
);
fn_gm_avito_set_setting(2, 'stock_mode', 'in_stock');
fn_gm_avito_set_setting(2, 'text_mode', 'categories');
fn_gm_avito_set_setting(2, 'new_text_categories', implode(',', $pilot));
fn_gm_avito_set_setting(2, 'common_block', fn_gm_avito_db_text($common));
fn_gm_avito_set_setting(2, 'specs_limit', '10');
fn_gm_avito_set_setting(1, 'stock_mode', 'all');
fn_gm_avito_set_setting(1, 'text_mode', 'old');

echo 'GOODMi: только с остатком, пробные категории ', implode(',', $pilot), '; Maxmobiles: все включённые', PHP_EOL;
