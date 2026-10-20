<?php
/**
 * Данные товаров для написания текстов объявлений Авито (скил avito-listing-copy, 2026-10-20). Только CLI, из корня сайта:
 *   php export-for-copy.php <company_id> <выбор> [лимит]
 * выбор: «123,456» – товары; «category:539» – главная категория (с вложенными); «enabled» – все, что продаются на Авито;
 *        «enabled-category:539» – продаются на Авито в категории.
 * Выводит JSON: товар, название, цена, остаток, раздел Авито, гарантия, текущие заголовок и «о товаре», описание с сайта
 * (текст, до 2500 знаков), характеристики (видимые в карточке, главные первыми).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$company_id = (int) ($argv[1] ?? 0);
$select = (string) ($argv[2] ?? '');
$limit = (int) ($argv[3] ?? 200);
if (!$company_id || $select === '') {
    exit("Нужно: company_id, выбор (123,456 | category:ID | enabled | enabled-category:ID) [лимит]\n");
}

$where = db_quote('p.company_id = ?i AND p.status = ?s', $company_id, 'A');
if (preg_match('/^(enabled-)?category:(\d+)$/', $select, $m)) {
    $ids = db_get_fields('SELECT category_id FROM ?:categories WHERE CONCAT(\'/\', id_path, \'/\') LIKE ?l', '%/' . (int) $m[2] . '/%');
    $where .= db_quote(' AND p.product_id IN (SELECT product_id FROM ?:products_categories WHERE link_type = ?s AND category_id IN (?n))', 'M', $ids);
    if ($m[1]) {
        $where .= db_quote(' AND i.enabled = ?s', 'Y');
    }
} elseif ($select === 'enabled') {
    $where .= db_quote(' AND i.enabled = ?s', 'Y');
} else {
    $where .= db_quote(' AND p.product_id IN (?n)', array_map('intval', explode(',', $select)));
}

$rows = db_get_array(
    'SELECT p.product_id, p.amount, pd.product, pd.full_description, i.title, i.intro, i.intro_auto, i.enabled'
    . ' FROM ?:products AS p INNER JOIN ?:product_descriptions AS pd ON pd.product_id = p.product_id AND pd.lang_code = ?s'
    . ' LEFT JOIN ?:gm_avito_items AS i ON i.product_id = p.product_id AND i.company_id = ?i'
    . ' WHERE ' . $where . ' ORDER BY p.amount > 0 DESC, pd.product LIMIT ?i',
    'ru',
    $company_id,
    $limit
);

$context = fn_gm_avito_context($company_id, array_column($rows, 'product_id'));
$out = [];
foreach ($rows as $row) {
    $product_id = (int) $row['product_id'];
    $item = fn_gm_avito_get_item($company_id, $product_id, true);
    $ad = fn_gm_avito_prepare_ad($company_id, $item, $context);
    $features = [];
    foreach (fn_gm_avito_product_features($product_id) as $feature) {
        if (!in_array($feature['name'], GM_AVITO_SPECS_SKIP, true)) {
            $features[] = ($feature['header'] ? '★ ' : '') . $feature['name'] . ': ' . fn_gm_avito_feature_text($feature);
        }
    }
    $html = preg_replace('~<(br|/p|/div|/li|/h[1-6])\b[^>]*>~iu', "\n", (string) $row['full_description']);
    $text = trim(preg_replace("/\n{2,}/", "\n", preg_replace('/[ \t]+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    $category = [];
    foreach (['Category', 'GoodsType', 'GoodsSubType', 'ProductType', 'ProductsType', 'ProductSubType'] as $tag) {
        if (!empty($ad['fields'][$tag])) {
            $category[] = fn_gm_avito_xml_text($ad['fields'][$tag]);
        }
    }
    $out[] = [
        'product_id' => $product_id,
        'name' => $row['product'],
        'price' => $ad['price'],
        'in_stock' => (int) $row['amount'] > 0,
        'on_avito' => $item['enabled'] === 'Y',
        'avito_category' => implode(' / ', $category),
        'warranty' => fn_gm_avito_warranty($company_id, $product_id),
        'title_now' => fn_gm_avito_xml_text($item['title']),
        'intro_now' => fn_gm_avito_xml_text($item['intro']),
        'intro_written_by_hand' => $item['intro_auto'] === 'N',
        'site_description' => mb_substr($text, 0, 2500),
        'features' => array_slice($features, 0, 25),
    ];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
