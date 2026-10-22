<?php
/**
 * GOODMi: страницы модуля Авито в админке (2026-10-19), для витрины, выбранной вверху админки (GOODMi или Maxmobiles).
 *   gm_avito.dashboard – обзор: файл, последняя загрузка Авито, ошибки, баланс кабинета, статистика и подсказки
 *   gm_avito.manage    – настройки: какие товары выгружать, текст объявлений, общий блок, поля по умолчанию, ссылка на файл
 *   gm_avito.templates – шаблоны категорий: категория Авито и её поля для категории сайта (и вложенных)
 *   gm_avito.problems  – что мешает выгрузке: включённые товары не в файле, незаполненные поля, категории без шаблона
 *   gm_avito.help      – инструкция: как работает модуль и что заполнять (только шаблон, данных не нужно)
 *   POST gm_avito.update / build / fill / update_templates / check / sync – сохранить, собрать файл, заполнить описания,
 *   сохранить шаблоны, проверить, получить отчёт Авито (API)
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$company_id = (int) fn_get_runtime_company_id();
$allowed = array_key_exists($company_id, GM_AVITO_COMPANIES);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return = $mode === 'update_templates' ? 'gm_avito.templates' : (in_array($mode, ['check', 'sync'], true) ? 'gm_avito.problems' : 'gm_avito.manage');
    if (!empty($_REQUEST['return_to']) && in_array($_REQUEST['return_to'], ['dashboard', 'problems', 'manage', 'templates'], true)) {
        $return = 'gm_avito.' . $_REQUEST['return_to'];
    }
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
        fn_gm_avito_set_setting($company_id, 'skip_categories', implode(',', array_filter(array_map('intval', (array) ($data['skip_categories'] ?? [])))));
        fn_gm_avito_set_setting($company_id, 'in_stock_categories', implode(',', array_filter(array_map('intval', (array) ($data['in_stock_categories'] ?? [])))));
        fn_gm_avito_set_setting($company_id, 'common_block', fn_gm_avito_db_text(trim((string) ($data['common_block'] ?? ''))));
        fn_gm_avito_set_setting($company_id, 'defaults', json_encode(fn_gm_avito_parse_fields($data['defaults'] ?? ''), JSON_UNESCAPED_UNICODE));
        fn_gm_avito_set_setting($company_id, 'images_limit', (string) max(1, min(10, (int) ($data['images_limit'] ?? 10))));
        fn_gm_avito_set_setting($company_id, 'specs_limit', (string) max(0, min(30, (int) ($data['specs_limit'] ?? 10))));
        fn_set_notification('N', __('notice'), 'Настройки Авито сохранены. Файл пересоберётся при следующей сборке или по кнопке «Собрать файл сейчас».');
    }

    if ($mode === 'stats') {
        try {
            $balance = fn_gm_avito_sync_balance($company_id);
            $stats = fn_gm_avito_sync_stats($company_id, 3);
            $promos = fn_gm_avito_sync_promotions($company_id);
            fn_set_notification('N', __('notice'), 'Баланс и статистика за последние дни обновлены (строк ' . $stats['rows'] . '), ставок на объявлениях: ' . count($promos['items']) . '.');
        } catch (Exception $e) {
            fn_set_notification('E', __('error'), 'Статистика Авито не получена: ' . $e->getMessage());
        }
    }

    if ($mode === 'sync') {
        try {
            $report = fn_gm_avito_sync_report($company_id);
            fn_set_notification('N', __('notice'), 'Отчёт Авито получен: объявлений ' . $report['ads'] . '.');
        } catch (Exception $e) {
            fn_set_notification('E', __('error'), 'Отчёт Авито не получен: ' . $e->getMessage());
        }
    }

    if ($mode === 'check') {
        $check = fn_gm_avito_check($company_id);
        fn_set_notification('N', __('notice'), 'Проверено: включено ' . $check['enabled'] . ', в файле ' . $check['in_file'] . '.');
    }

    if ($mode === 'build') {
        $result = fn_gm_avito_build_feed($company_id);
        fn_gm_avito_check($company_id);
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
                    'required_fields' => implode(',', array_filter(array_map('trim', explode(',', (string) ($template['required_fields'] ?? ''))), static function ($tag) {
                        return preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $tag);
                    })),
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
if ($mode === 'help') {
    $view->assign('gm_avito_company_id', $company_id);
    $view->assign('gm_avito_companies', db_get_hash_single_array(
        'SELECT company_id, company FROM ?:companies WHERE company_id IN (?n) ORDER BY company_id DESC',
        ['company_id', 'company'],
        array_keys(GM_AVITO_COMPANIES)
    ));

    return;
}
$view->assign('gm_avito_company_id', $company_id);
$view->assign('gm_avito_companies', db_get_hash_single_array(
    'SELECT company_id, company FROM ?:companies WHERE company_id IN (?n) ORDER BY company_id DESC',
    ['company_id', 'company'],
    array_keys(GM_AVITO_COMPANIES)
));
if (!$allowed) {
    return;
}

if ($mode === 'dashboard') {
    $setting = static function ($name) use ($company_id) {
        return (array) json_decode(fn_gm_avito_xml_text((string) fn_gm_avito_get_setting($company_id, $name, '{}')), true);
    };
    $report = $setting('report');
    $profile = $setting('profile');
    $hours = [];
    foreach ((array) ($profile['schedule'] ?? []) as $slot) {
        $hours = array_merge($hours, array_map('intval', (array) ($slot['time_slots'] ?? [])));
    }
    sort($hours);
    $feed = implode(', ', array_map(static function ($url) {
        return strpos($url, 'gm_avito') !== false ? 'наш модуль' : (strpos($url, 'unitxml') !== false ? 'UnitXML' : $url);
    }, (array) ($profile['feeds'] ?? [])));
    $statuses = db_get_hash_single_array(
        'SELECT status, COUNT(*) AS n FROM ?:gm_avito_items WHERE company_id = ?i AND status <> ?s GROUP BY status',
        ['status', 'n'],
        $company_id,
        ''
    );
    $summary = fn_gm_avito_stats_summary($company_id, 30);
    $metrics = [];
    foreach (['impressions' => 'Показы в поиске', 'views' => 'Просмотры', 'contacts' => 'Контакты', 'favorites' => 'В избранном'] as $key => $label) {
        $now = (int) ($summary['total'][$key] ?? 0);
        $was = (int) ($summary['prev'][$key] ?? 0);
        $metrics[$key] = ['label' => $label, 'value' => number_format($now, 0, ',', ' '), 'delta' => $was ? round(($now - $was) * 100 / $was) : null];
    }
    $summary['metrics'] = $metrics;
    $t = $summary['total'];
    $summary['ctr'] = !empty($t['impressions']) ? round($t['views'] * 100 / $t['impressions'], 1) : 0;
    $summary['cr'] = !empty($t['views']) ? round($t['contacts'] * 100 / $t['views'], 1) : 0;
    // Расходы (копейки): за период, к прошлому периоду, в среднем за день, цена контакта
    $spent = (int) ($t['spending'] ?? 0);
    $spent_was = (int) ($summary['prev']['spending'] ?? 0);
    $summary['spend_card'] = [
        'total' => fn_gm_avito_rub($spent, 0),
        'delta' => $spent_was ? round(($spent - $spent_was) * 100 / $spent_was) : null,
        'per_day' => $summary['by_day'] ? fn_gm_avito_rub($spent / count($summary['by_day']), 0) : '0',
        'promo' => fn_gm_avito_rub($t['promo_spending'] ?? 0, 0),
        'cpc' => (string) ($t['cpc_rub'] ?? ''),
        'cpc_was' => (string) ($summary['prev']['cpc_rub'] ?? ''),
    ];
    $max_day = $max_spend = 0;
    foreach ($summary['by_day'] as $day) {
        $max_day = max($max_day, (int) $day['views']);
        $max_spend = max($max_spend, (int) $day['spending']);
    }
    foreach ($summary['by_day'] as &$day) {
        $day['h'] = $max_day ? max(2, (int) round($day['views'] * 115 / $max_day)) : 2;
        $day['hs'] = $max_spend ? max(2, (int) round($day['spending'] * 115 / $max_spend)) : 2;
    }
    unset($day);
    $summary['by_day_desc'] = array_reverse($summary['by_day']);
    $summary['first_day'] = $summary['by_day'] ? $summary['by_day'][0]['date'] : '';
    $summary['last_day'] = $summary['by_day'] ? end($summary['by_day'])['date'] : '';
    $balance = (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'balance', '{}'), true);
    foreach (['wallet', 'bonus', 'cpa'] as $key) {
        if (isset($balance[$key])) {
            $balance[$key . '_fmt'] = number_format((float) $balance[$key], 2, ',', ' ');
        }
    }
    // Стратегия продвижения: текст – настройка strategy (правится при пересмотре), что работает сейчас – по ставкам
    // из promotions (Авито, вместе со статистикой) и фактическим расходам за 30 дней
    $strategy = $setting('strategy');
    $promos = $setting('promotions');
    $titles = [];
    $promo_ids = array_keys((array) ($promos['items'] ?? []));
    if ($promo_ids) {
        $titles = db_get_hash_array(
            'SELECT i.avito_id, i.product_id, d.product FROM ?:gm_avito_items i'
            . ' LEFT JOIN ?:product_descriptions d ON d.product_id = i.product_id AND d.lang_code = ?s'
            . ' WHERE i.company_id = ?i AND i.avito_id IN (?n)',
            'avito_id',
            CART_LANGUAGE,
            $company_id,
            $promo_ids
        );
    }
    $actions = [1 => 'звонок', 5 => 'просмотр', 7 => 'чат'];
    $budget_types = ['1d' => 'в день', '7d' => 'в неделю', '30d' => 'в месяц'];
    $promo_rows = [];
    $manual = $auto = $limits = 0;
    foreach ((array) ($promos['items'] ?? []) as $avito_id => $p) {
        $p['type'] === 'auto' ? $auto++ : $manual++;
        $limits += (int) $p['limit'];
        $promo_rows[] = [
            'avito_id' => $avito_id,
            'product_id' => $titles[$avito_id]['product_id'] ?? 0,
            'product' => $titles[$avito_id]['product'] ?? '',
            'type' => $p['type'] === 'auto' ? 'авто' : 'ручная',
            'action' => $actions[$p['action']] ?? (string) $p['action'],
            'value' => $p['type'] === 'auto'
                ? ($p['budget'] ? 'бюджет ' . fn_gm_avito_rub($p['budget'], 0) . ' ₽ ' . ($budget_types[$p['budget_type']] ?? $p['budget_type']) : 'без бюджета')
                : fn_gm_avito_rub($p['bid'], 0) . ' ₽' . ($p['limit'] ? ', лимит ' . fn_gm_avito_rub($p['limit'], 0) . ' ₽ в день' : ''),
        ];
    }
    // цена просмотра и расход в день – только по дням со списаниями (платить могли начать посреди периода)
    $paid_days = $paid_views = 0;
    foreach ($summary['by_day'] as $day) {
        if ((int) $day['spending'] > 0) {
            $paid_days++;
            $paid_views += (int) $day['views'];
        }
    }
    if ($promo_rows) {
        $now_text = 'Ставки выше минимальной: ручные – ' . $manual . ', автонастройка – ' . $auto
            . ($limits ? ', дневные лимиты в сумме ' . fn_gm_avito_rub($limits, 0) . ' ₽' : '') . '.';
    } elseif (!empty($promos['time'])) {
        $now_text = 'Ставок выше минимальной нет ни на одном объявлении.';
    } else {
        $now_text = 'Ставки у Авито ещё не запрашивались – нажмите «Обновить сейчас» в блоке баланса.';
    }
    $now_text .= $spent
        ? ' Авито списывает за просмотры: в среднем ' . ($paid_views ? fn_gm_avito_rub($spent / $paid_views) : '–') . ' ₽ за просмотр, '
            . fn_gm_avito_rub($spent / max(1, $paid_days), 0) . ' ₽ в день (дней со списаниями за 30 дней: ' . $paid_days . ').'
        : ' Платных списаний за 30 дней нет – размещение бесплатное.';
    $strategy['now'] = $now_text;
    $strategy['promos'] = $promo_rows;
    $strategy['promos_time'] = (int) ($promos['time'] ?? 0);
    $strategy['checked'] = (int) ($promos['checked'] ?? 0);

    $view->assign('gm_avito_dash', [
        'company' => fn_get_company_name($company_id),
        'strategy' => $strategy,
        'account_name' => fn_gm_avito_xml_text((string) fn_gm_avito_get_setting($company_id, 'account_name', '')),
        'build' => $setting('last_build'),
        'check' => $setting('check'),
        'report' => $report,
        'report_started' => !empty($report['started_at']) ? date('d.m.Y H:i', strtotime($report['started_at'])) : '',
        'profile' => $profile,
        'hours' => implode(', ', array_map(static function ($h) {
            return sprintf('%02d', $h);
        }, array_unique($hours))),
        'feed' => $feed,
        'statuses' => $statuses,
        'problems' => array_sum(array_diff_key($statuses, array_flip(['successful']))),
        'published' => (int) ($statuses['successful'] ?? 0),
        'balance' => $balance,
        'stats_sync' => $setting('stats_sync'),
        'summary' => $summary,
        'max_day' => $max_day,
        'cron' => GM_AVITO_CRON[$company_id] ?? [],
    ]);

    return;
}

if ($mode === 'problems') {
    $view->assign('gm_avito_check', (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'check', '{}'), true));
    $report = (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'report', '{}'), true);
    // В отчёте Авито – адрес его копии файла (api.avito.ru/…/feed/content/…), какой файл указан в кабинете – в профиле
    $labels = [];
    $profile = (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'profile', '{}'), true);
    foreach ((array) ($profile['feeds'] ?? []) as $url) {
        $url = (string) $url;
        $labels[] = strpos($url, 'gm_avito') !== false ? 'наш модуль' : (strpos($url, 'unitxml') !== false ? 'UnitXML' : $url);
    }
    $report['feed_label'] = implode(', ', array_unique($labels));
    $view->assign('gm_avito_report', $report);
    $view->assign('gm_avito_report_problems', fn_gm_avito_report_problems($company_id));
    $view->assign('gm_avito_company', fn_get_company_name($company_id));

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
        'new_text_categories' => $new_text_categories = array_map('intval', explode(',', (string) fn_gm_avito_get_setting($company_id, 'new_text_categories', ''))),
        'new_text_names' => implode(', ', $new_text_names = array_values(array_filter(array_map(static function ($id) use ($names) {
            return $names[$id] ?? '';
        }, $new_text_categories)))),
        'new_text_count' => count($new_text_names),
        'skip_categories' => $skip_categories = array_values(array_filter(array_map('intval', explode(',', (string) fn_gm_avito_get_setting($company_id, 'skip_categories', ''))))),
        'skip_names' => implode(', ', array_map(static function ($id) use ($names) {
            return $names[$id] ?? $id;
        }, $skip_categories)),
        'in_stock_categories' => $in_stock_categories = array_values(array_filter(array_map('intval', explode(',', (string) fn_gm_avito_get_setting($company_id, 'in_stock_categories', ''))))),
        'in_stock_names' => implode(', ', array_map(static function ($id) use ($names) {
            return $names[$id] ?? $id;
        }, $in_stock_categories)),
        'all_categories' => (static function () use ($category_paths) {
            asort($category_paths);
            return $category_paths;
        })(),
        'common_block' => fn_gm_avito_xml_text((string) fn_gm_avito_get_setting($company_id, 'common_block', '')),
        'defaults' => fn_gm_avito_fields_text((array) json_decode((string) fn_gm_avito_get_setting($company_id, 'defaults', '{}'), true)),
        'images_limit' => fn_gm_avito_get_setting($company_id, 'images_limit', 10),
        'specs_limit' => fn_gm_avito_get_setting($company_id, 'specs_limit', 10),
        'feed_url' => fn_gm_avito_feed_url($company_id),
        'cron' => GM_AVITO_CRON[$company_id] ?? [],
        'profile' => $profile = (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'profile', '{}'), true),
        'profile_hours' => implode(', ', array_unique(array_merge(...array_map(static function ($slot) {
            $hours = array_map('intval', (array) ($slot['time_slots'] ?? []));
            sort($hours);
            return array_map(static function ($hour) {
                return sprintf('%02d', $hour);
            }, $hours);
        }, (array) ($profile['schedule'] ?? []) ?: [[]])))),
        'profile_feed' => implode(', ', array_map(static function ($url) {
            return strpos($url, 'gm_avito') !== false ? 'наш модуль' : (strpos($url, 'unitxml') !== false ? 'UnitXML' : $url);
        }, (array) ($profile['feeds'] ?? []))),
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
            'required_fields' => implode(', ', $template['required_fields']),
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
