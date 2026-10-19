<?php
/**
 * Поля из каталогов Авито в товары (2026-10-19). Только CLI, из корня сайта:
 *   php apply-catalog-fields.php <company_id> <catalog-fields.json>
 * fields – {product_id: {Vendor, Model, MemorySize …}} (телефоны, планшеты, телевизоры – match-catalog.py и сверка
 * вручную), brands – категории, где Brand должен быть из каталога брендов Авито: известный бренд – как в каталоге,
 * остальные – «Другой» (у шаблонов этих категорий Brand убирается из «из характеристик»). Перенесённые из UnitXML
 * объявления (свои поля с Category) не трогаются; остальные поля товара сохраняются.
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
    exit("Нужно: company_id, catalog-fields.json\n");
}

$merge = static function ($product_id, array $add) use ($company_id) {
    $item = fn_gm_avito_get_item($company_id, (int) $product_id, true);
    if (!$item) {
        return 'нет товара';
    }
    $fields = (array) json_decode((string) $item['fields'], true);
    if (!empty($fields['Category'])) {
        return 'перенесён из UnitXML';
    }
    foreach ($add as $tag => $value) {
        $fields[$tag] = fn_gm_avito_db_text($value);
    }
    db_query('UPDATE ?:gm_avito_items SET fields = ?s WHERE company_id = ?i AND product_id = ?i', json_encode($fields, JSON_UNESCAPED_UNICODE), $company_id, $product_id);

    return 'ok';
};

$stats = [];
foreach ((array) $spec['fields'] as $product_id => $add) {
    $result = $merge($product_id, (array) $add);
    $stats['каталог: ' . $result] = ($stats['каталог: ' . $result] ?? 0) + 1;
}

foreach ((array) $spec['brands'] as $group => $rule) {
    $known = [];
    foreach ((array) $rule['known'] as $name) {
        $known[mb_strtolower($name)] = $name;
    }
    $products = db_get_fields(
        'SELECT p.product_id FROM ?:products AS p INNER JOIN ?:products_categories AS pc ON pc.product_id = p.product_id AND pc.link_type = ?s'
        . ' WHERE p.company_id = ?i AND p.status = ?s AND pc.category_id IN (?n)',
        'M',
        $company_id,
        'A',
        (array) $rule['categories']
    );
    foreach ($products as $product_id) {
        $brand = fn_gm_avito_feature_value(fn_gm_avito_product_features((int) $product_id), ['Бренд', 'Производитель']);
        $value = $known[mb_strtolower(trim($brand))] ?? (string) $spec['other'];
        $result = $merge($product_id, ['Brand' => $value]);
        $stats['бренд ' . $group . ': ' . ($value === $spec['other'] ? 'Другой' : 'из каталога') . ' – ' . $result] = ($stats['бренд ' . $group . ': ' . ($value === $spec['other'] ? 'Другой' : 'из каталога') . ' – ' . $result] ?? 0) + 1;
    }
    foreach ((array) $rule['categories'] as $category_id) {
        $auto = (string) db_get_field('SELECT auto_fields FROM ?:gm_avito_templates WHERE company_id = ?i AND category_id = ?i', $company_id, $category_id);
        $auto = implode(',', array_diff(array_filter(explode(',', $auto)), ['Brand']));
        db_query('UPDATE ?:gm_avito_templates SET auto_fields = ?s WHERE company_id = ?i AND category_id = ?i', $auto, $company_id, $category_id);
    }
}

foreach ($stats as $name => $count) {
    echo $name, ': ', $count, PHP_EOL;
}
