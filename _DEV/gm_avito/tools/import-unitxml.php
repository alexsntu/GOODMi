<?php
/**
 * Перенос фида UnitXML в таблицы модуля gm_avito (2026-10-19). Только CLI, из корня сайта:
 *   php import-unitxml.php <company_id> <фид UnitXML.xml> <сопоставление Id→product_id.json> <адрес сайта для фото>
 * Для каждого <Ad>: product_id по сопоставлению (Id объявления = UUID товара МойСклад → externalCode → товар CS-Cart),
 * заголовок, описание и все остальные поля в исходном порядке → cscart_gm_avito_items (REPLACE). Ключ ссылки на файл
 * создаётся, если его ещё нет. Объявления без товара на сайте – в отчёт, не переносятся.
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

list(, $company_id, $feed_path, $map_path, $site_url) = $argv + [null, null, null, null, null];
$company_id = (int) $company_id;
$map = json_decode((string) file_get_contents($map_path), true);
$xml = simplexml_load_file($feed_path, 'SimpleXMLElement', LIBXML_NOCDATA);
if (!$company_id || !$map || !$xml) {
    exit("Нужно: company_id, фид, сопоставление, адрес сайта\n");
}

$own = ['Id', 'Title', 'Description', 'Price', 'Images'];
$done = 0;
$missing = [];
foreach ($xml->Ad as $ad) {
    $ad_id = (string) $ad->Id;
    if (empty($map[$ad_id])) {
        $missing[] = $ad_id;
        continue;
    }
    $fields = [];
    foreach ($ad->children() as $child) {
        $tag = $child->getName();
        if (!in_array($tag, $own, true)) {
            $fields[$tag] = fn_gm_avito_db_text(trim((string) $child));
        }
    }
    db_query('REPLACE INTO ?:gm_avito_items ?e', [
        'company_id' => $company_id,
        'product_id' => (int) $map[$ad_id],
        'ad_id' => $ad_id,
        'enabled' => 'Y',
        'title' => fn_gm_avito_db_text(trim((string) $ad->Title)),
        'description' => fn_gm_avito_db_text((string) $ad->Description),
        'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE),
        'updated_at' => TIME,
    ]);
    $done++;
}

if (!fn_gm_avito_get_setting($company_id, 'feed_key')) {
    fn_gm_avito_set_setting($company_id, 'feed_key', bin2hex(random_bytes(16)));
}
fn_gm_avito_set_setting($company_id, 'site_url', rtrim($site_url, '/'));
fn_gm_avito_set_setting($company_id, 'images_limit', '10');
fn_gm_avito_set_setting($company_id, 'source', 'UnitXML ' . date('Y-m-d H:i') . ': ' . basename($feed_path));

echo 'перенесено объявлений: ', $done, ', без товара на сайте: ', count($missing), PHP_EOL;
