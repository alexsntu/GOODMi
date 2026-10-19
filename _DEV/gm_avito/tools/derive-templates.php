<?php
/**
 * Шаблоны категорий Авито из перенесённых объявлений UnitXML (2026-10-19). Только CLI, из корня сайта:
 *   php derive-templates.php <company_id>
 * По главной категории сайта каждого перенесённого объявления: берётся самый частый набор «категория Авито / тип / подтип»,
 * в шаблон идут поля, одинаковые у всех объявлений этого набора (кроме адреса, телефона и т.п. – это поля по умолчанию
 * витрины, и кроме бренда/модели/цвета – их шаблон берёт из характеристик, если они были у большинства). Поля по умолчанию
 * витрины – самые частые значения. Существующие шаблоны заменяются (REPLACE).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$company_id = (int) ($argv[1] ?? 0);
if (!$company_id) {
    exit("Нужно: company_id\n");
}

$default_tags = ['Address', 'ContactPhone', 'ContactMethod', 'Condition', 'AdType'];
$signature_tags = ['Category', 'GoodsType', 'GoodsSubType', 'ProductType'];
$auto_tags = array_keys(GM_AVITO_FEATURE_FIELDS);

$items = db_get_array('SELECT product_id, fields FROM ?:gm_avito_items WHERE company_id = ?i AND fields <> ?s', $company_id, '{}');
$paths = fn_gm_avito_main_paths(array_column($items, 'product_id'));

$groups = [];
$defaults = [];
foreach ($items as $item) {
    $fields = (array) json_decode((string) $item['fields'], true);
    $path = $paths[(int) $item['product_id']] ?? '';
    if ($path === '' || empty($fields['Category'])) {
        continue;
    }
    $category_id = (int) substr(strrchr('/' . $path, '/'), 1);
    $signature = implode(' / ', array_filter(array_map(static function ($tag) use ($fields) {
        return $fields[$tag] ?? '';
    }, $signature_tags)));
    $groups[$category_id][$signature][] = $fields;
    foreach ($default_tags as $tag) {
        if (isset($fields[$tag])) {
            $defaults[$tag][$fields[$tag]] = ($defaults[$tag][$fields[$tag]] ?? 0) + 1;
        }
    }
}

$saved = 0;
foreach ($groups as $category_id => $signatures) {
    uasort($signatures, static function ($a, $b) {
        return count($b) - count($a);
    });
    $all = array_sum(array_map('count', $signatures));
    $ads = reset($signatures);
    $template = $ads[0];
    foreach ($ads as $fields) {
        foreach ($template as $tag => $value) {
            if (!isset($fields[$tag]) || $fields[$tag] !== $value) {
                unset($template[$tag]);
            }
        }
    }
    foreach (array_merge($default_tags, $auto_tags) as $tag) {
        unset($template[$tag]);
    }
    $auto = [];
    foreach ($auto_tags as $tag) {
        $with = count(array_filter($ads, static function ($fields) use ($tag) {
            return !empty($fields[$tag]);
        }));
        if ($with * 2 >= count($ads)) {
            $auto[] = $tag;
        }
    }
    db_query('REPLACE INTO ?:gm_avito_templates ?e', [
        'company_id' => $company_id,
        'category_id' => $category_id,
        'fields' => json_encode($template, JSON_UNESCAPED_UNICODE),
        'auto_fields' => implode(',', $auto),
        'note' => count($signatures) > 1
            ? 'из UnitXML; объявления в ' . count($signatures) . ' разделах Авито, взят самый частый (' . count($ads) . ' из ' . $all . ')'
            : 'из UnitXML, ' . $all . ' объявл.',
        'updated_at' => TIME,
    ]);
    $saved++;
}

$default_fields = [];
foreach ($defaults as $tag => $values) {
    arsort($values);
    $default_fields[$tag] = key($values);
}
fn_gm_avito_set_setting($company_id, 'defaults', json_encode($default_fields, JSON_UNESCAPED_UNICODE));

echo 'шаблонов: ', $saved, ', поля по умолчанию: ', json_encode($default_fields, JSON_UNESCAPED_UNICODE), PHP_EOL;
