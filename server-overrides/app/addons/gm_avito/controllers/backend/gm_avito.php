<?php
/**
 * GOODMi: страницы модуля Авито в админке (2026-10-19), для витрины, выбранной вверху админки (GOODMi или Maxmobiles).
 *   gm_avito.manage    – настройки: какие товары выгружать, текст объявлений, общий блок, поля по умолчанию, ссылка на файл
 *   gm_avito.templates – шаблоны категорий: категория Авито и её поля для категории сайта (и вложенных)
 *   POST gm_avito.update / build / fill / update_templates – сохранить, собрать файл, заполнить описания, сохранить шаблоны
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$company_id = (int) fn_get_runtime_company_id();
$allowed = array_key_exists($company_id, GM_AVITO_COMPANIES);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return = $mode === 'update_templates' ? 'gm_avito.templates' : 'gm_avito.manage';
    if (!$allowed) {
        return [CONTROLLER_STATUS_OK, $return];
    }

    if ($mode === 'update') {
        $data = (array) ($_REQUEST['gm_avito'] ?? []);
        $stock_mode = (string) ($data['stock_mode'] ?? 'all');
        $text_mode = (string) ($data['text_mode'] ?? 'old');
        fn_gm_avito_set_setting($company_id, 'stock_mode', in_array($stock_mode, ['in_stock', 'out_of_stock', 'all'], true) ? $stock_mode : 'all');
        fn_gm_avito_set_setting($company_id, 'text_mode', in_array($text_mode, ['old', 'new', 'categories'], true) ? $text_mode : 'old');
        fn_gm_avito_set_setting($company_id, 'new_text_categories', implode(',', array_filter(array_map('intval', (array) ($data['new_text_categories'] ?? [])))));
        fn_gm_avito_set_setting($company_id, 'common_block', fn_gm_avito_db_text(trim((string) ($data['common_block'] ?? ''))));
        fn_gm_avito_set_setting($company_id, 'defaults', json_encode(fn_gm_avito_parse_fields($data['defaults'] ?? ''), JSON_UNESCAPED_UNICODE));
        fn_gm_avito_set_setting($company_id, 'images_limit', (string) max(1, min(10, (int) ($data['images_limit'] ?? 10))));
        fn_gm_avito_set_setting($company_id, 'specs_limit', (string) max(0, min(30, (int) ($data['specs_limit'] ?? 10))));
        fn_set_notification('N', __('notice'), 'Настройки Авито сохранены. Файл пересоберётся при следующей сборке или по кнопке «Собрать файл сейчас».');
    }

    if ($mode === 'build') {
        $result = fn_gm_avito_build_feed($company_id);
        fn_set_notification('N', __('notice'), 'Файл собран: объявлений ' . $result['ads'] . ', не попали ' . count($result['skipped']) . '.');
    }

    if ($mode === 'fill') {
        $stats = fn_gm_avito_fill_items($company_id);
        fn_set_notification('N', __('notice'), 'Описания заполнены: новых товаров ' . $stats['created'] . ', обновлено ' . $stats['updated']
            . ', без изменений ' . $stats['same'] . ', исправленных вручную не тронуто ' . $stats['manual'] . '.');
    }

    if ($mode === 'update_templates') {
        foreach ((array) ($_REQUEST['templates'] ?? []) as $category_id => $template) {
            $category_id = (int) $category_id;
            if (!empty($template['delete'])) {
                db_query('DELETE FROM ?:gm_avito_templates WHERE company_id = ?i AND category_id = ?i', $company_id, $category_id);
                continue;
            }
            $auto = array_values(array_intersect(array_keys(GM_AVITO_FEATURE_FIELDS), (array) ($template['auto_fields'] ?? [])));
            db_query(
                'UPDATE ?:gm_avito_templates SET ?u WHERE company_id = ?i AND category_id = ?i',
                [
                    'fields' => json_encode(fn_gm_avito_parse_fields($template['fields'] ?? ''), JSON_UNESCAPED_UNICODE),
                    'auto_fields' => implode(',', $auto),
                    'updated_at' => TIME,
                ],
                $company_id,
                $category_id
            );
        }
        $new_category_id = (int) ($_REQUEST['new_template_category_id'] ?? 0);
        if ($new_category_id && db_get_field('SELECT category_id FROM ?:categories WHERE category_id = ?i AND company_id = ?i', $new_category_id, $company_id)) {
            db_query('INSERT IGNORE INTO ?:gm_avito_templates ?e', [
                'company_id' => $company_id,
                'category_id' => $new_category_id,
                'fields' => json_encode(fn_gm_avito_parse_fields($_REQUEST['new_template_fields'] ?? ''), JSON_UNESCAPED_UNICODE),
                'auto_fields' => '',
                'note' => 'добавлен вручную',
                'updated_at' => TIME,
            ]);
        }
        fn_set_notification('N', __('notice'), 'Шаблоны категорий сохранены.');
    }

    return [CONTROLLER_STATUS_OK, $return];
}

$view = Tygh::$app['view'];
$view->assign('gm_avito_allowed', $allowed);
if (!$allowed) {
    return;
}

/** Названия категорий витрины с путями: category_id => «Раздел / Подраздел» */
$category_rows = db_get_array(
    'SELECT c.category_id, c.id_path, cd.category FROM ?:categories AS c'
    . ' INNER JOIN ?:category_descriptions AS cd ON cd.category_id = c.category_id AND cd.lang_code = ?s'
    . ' WHERE c.company_id = ?i',
    'ru',
    $company_id
);
$names = array_column($category_rows, 'category', 'category_id');
$category_paths = [];
foreach ($category_rows as $row) {
    $category_paths[(int) $row['category_id']] = implode(' / ', array_map(static function ($id) use ($names) {
        return $names[$id] ?? $id;
    }, explode('/', (string) $row['id_path'])));
}

/** Товары витрины по шаблонам: сколько всего и сколько включено, у скольких нет шаблона */
$templates = fn_gm_avito_templates($company_id);
$items = db_get_array(
    'SELECT i.product_id, i.enabled FROM ?:gm_avito_items AS i INNER JOIN ?:products AS p ON p.product_id = i.product_id'
    . ' WHERE i.company_id = ?i AND p.status = ?s',
    $company_id,
    'A'
);
$paths = fn_gm_avito_main_paths(array_column($items, 'product_id'));
$counts = [];
$no_template = [];
foreach ($items as $item) {
    $path = $paths[(int) $item['product_id']] ?? '';
    $template = fn_gm_avito_template_for_path($templates, $path);
    if ($template) {
        $key = (int) $template['category_id'];
        $counts[$key]['total'] = ($counts[$key]['total'] ?? 0) + 1;
        $counts[$key]['enabled'] = ($counts[$key]['enabled'] ?? 0) + ($item['enabled'] === 'Y' ? 1 : 0);
    } else {
        $main = (int) substr(strrchr('/' . $path, '/'), 1);
        $no_template[$main] = ($no_template[$main] ?? 0) + 1;
    }
}

if ($mode === 'manage') {
    $last_build = (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'last_build', '{}'), true);
    $template_list = [];
    foreach ($templates as $category_id => $template) {
        $template_list[$category_id] = $category_paths[$category_id] ?? ('категория ' . $category_id);
    }
    asort($template_list);
    $view->assign('gm_avito', [
        'company' => fn_get_company_name($company_id),
        'stock_mode' => fn_gm_avito_get_setting($company_id, 'stock_mode', 'all'),
        'text_mode' => fn_gm_avito_get_setting($company_id, 'text_mode', 'old'),
        'new_text_categories' => array_map('intval', explode(',', (string) fn_gm_avito_get_setting($company_id, 'new_text_categories', ''))),
        'common_block' => fn_gm_avito_xml_text((string) fn_gm_avito_get_setting($company_id, 'common_block', '')),
        'defaults' => fn_gm_avito_fields_text((array) json_decode((string) fn_gm_avito_get_setting($company_id, 'defaults', '{}'), true)),
        'images_limit' => fn_gm_avito_get_setting($company_id, 'images_limit', 10),
        'specs_limit' => fn_gm_avito_get_setting($company_id, 'specs_limit', 10),
        'feed_url' => fn_gm_avito_feed_url($company_id),
        'last_build' => $last_build,
        'items_total' => count($items),
        'items_enabled' => count(array_filter($items, static function ($item) {
            return $item['enabled'] === 'Y';
        })),
        'templates' => $template_list,
        'no_template_products' => array_sum($no_template),
    ]);
}

if ($mode === 'templates') {
    $list = [];
    foreach ($templates as $category_id => $template) {
        $list[] = [
            'category_id' => $category_id,
            'path' => $category_paths[$category_id] ?? ('категория ' . $category_id),
            'fields' => fn_gm_avito_fields_text($template['fields']),
            'auto_fields' => $template['auto_fields'],
            'note' => $template['note'],
            'total' => $counts[$category_id]['total'] ?? 0,
            'enabled' => $counts[$category_id]['enabled'] ?? 0,
        ];
    }
    usort($list, static function ($a, $b) {
        return strcmp($a['path'], $b['path']);
    });
    arsort($no_template);
    $missing = [];
    foreach ($no_template as $category_id => $count) {
        $missing[] = ['category_id' => $category_id, 'path' => $category_paths[$category_id] ?? ('категория ' . $category_id), 'count' => $count];
    }
    $view->assign('gm_avito_templates', $list);
    $view->assign('gm_avito_missing', $missing);
    $view->assign('gm_avito_auto_fields', array_keys(GM_AVITO_FEATURE_FIELDS));
}
