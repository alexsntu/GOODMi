<?php
/**
 * Шаблоны категорий из справочника Авито (2026-10-19). Только CLI, из корня сайта:
 *   php apply-templates.php <company_id> <templates-spec.json>
 * Спецификация – make-spec.py: [{category_id, fields{}, auto_fields[], required_fields[], note}]. Шаблоны этих категорий
 * заменяются (REPLACE), остальные не трогаются. Поясные сумки (категория 654, «пояс» в названии) – ApparelType у товара.
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$company_id = (int) ($argv[1] ?? 0);
$spec = json_decode((string) file_get_contents($argv[2] ?? ''), true);
if (!$company_id || !is_array($spec)) {
    exit("Нужно: company_id, templates-spec.json\n");
}

$saved = 0;
foreach ($spec as $template) {
    $category_id = (int) $template['category_id'];
    if (!db_get_field('SELECT category_id FROM ?:categories WHERE category_id = ?i AND company_id = ?i', $category_id, $company_id)) {
        echo 'нет категории ', $category_id, PHP_EOL;
        continue;
    }
    $fields = [];
    foreach ((array) $template['fields'] as $tag => $value) {
        $fields[$tag] = fn_gm_avito_db_text($value);
    }
    db_query('REPLACE INTO ?:gm_avito_templates ?e', [
        'company_id' => $company_id,
        'category_id' => $category_id,
        'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE),
        'auto_fields' => implode(',', array_intersect((array) $template['auto_fields'], array_keys(GM_AVITO_FEATURE_FIELDS))),
        'required_fields' => implode(',', (array) $template['required_fields']),
        'note' => mb_substr((string) $template['note'], 0, 255),
        'updated_at' => TIME,
    ]);
    $saved++;
}

$belt = 0;
$rows = db_get_array(
    'SELECT i.product_id, i.fields FROM ?:gm_avito_items AS i'
    . ' INNER JOIN ?:products_categories AS pc ON pc.product_id = i.product_id AND pc.link_type = ?s AND pc.category_id = ?i'
    . ' INNER JOIN ?:product_descriptions AS pd ON pd.product_id = i.product_id AND pd.lang_code = ?s'
    . ' WHERE i.company_id = ?i AND pd.product LIKE ?l',
    'M',
    654,
    'ru',
    $company_id,
    '%пояс%'
);
foreach ($rows as $row) {
    $fields = (array) json_decode((string) $row['fields'], true);
    if (!empty($fields['Category'])) {
        continue;
    }
    $fields['ApparelType'] = 'Поясная';
    db_query('UPDATE ?:gm_avito_items SET fields = ?s WHERE company_id = ?i AND product_id = ?i', json_encode($fields, JSON_UNESCAPED_UNICODE), $company_id, $row['product_id']);
    $belt++;
}

echo 'шаблонов: ', $saved, ', поясных сумок: ', $belt, PHP_EOL;
