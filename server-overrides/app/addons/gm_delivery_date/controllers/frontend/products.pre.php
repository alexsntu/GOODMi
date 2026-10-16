<?php
/**
 * GOODMi: свой поиск, этап 3 (2026-10-16; копия my_changes/controllers/frontend/products.pre.php Maxmobiles) – штатная страница
 * результатов (products.search) с товарами, найденными Manticore (app/gm_search/lib.php, индекс gm_products), в порядке
 * релевантности. Здесь – поиск и набор найденных товаров в Registry 'runtime.gm_search'; подмена условия и сортировка
 * «По релевантности» – хуки get_products_pre, get_products, get_products_post, products_sorting в func.php модуля (свои ключи
 * gm_search – с поиском Maxmobiles 'runtime.mm_search' не пересекается).
 * Только витрина GOODMi, у всех покупателей с 2026-10-16 (Diginetica отключена).
 * Если индекс недоступен – штатный поиск. Статистика (этап 4): каждый поиск – в cscart_gm_search_log (показанные товары допишет
 * хук get_products_post), метка браузера gm_sid без IP и личных данных.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode === 'search' && (int) Registry::get('runtime.company_id') === 2) {
    // Запрос пришёл в поле hint_q (CS-Cart cm-hint не вернул полю имя q) – считаем его запросом
    if ((!isset($_REQUEST['q']) || trim((string) $_REQUEST['q']) === '') && isset($_REQUEST['hint_q']) && trim((string) $_REQUEST['hint_q']) !== '') {
        $_REQUEST['q'] = (string) $_REQUEST['hint_q'];
        $_GET['q'] = $_REQUEST['q'];
    }
    $gm_lib = Registry::get('config.dir.root') . '/app/gm_search/lib.php';
    if (isset($_REQUEST['q']) && is_string($_REQUEST['q']) && trim($_REQUEST['q']) !== '' && is_file($gm_lib)) {
        try {
            require_once $gm_lib;
            $gm_mc = @new mysqli('127.0.0.1', '', '', '', 9306);
            if (!$gm_mc->connect_errno) {
                $gm_mc->set_charset('utf8mb4');
                $gm_t0 = microtime(true);
                $gm_found = gm_search($gm_mc, (string) $_REQUEST['q'], 1000);
                $gm_ms = (int) round((microtime(true) - $gm_t0) * 1000);
                // Плитка «Найдено в категориях» (gm_cat) – только товары этой категории (по основной категории товара)
                $gm_cat = isset($_REQUEST['gm_cat']) ? (int) $_REQUEST['gm_cat'] : 0;
                $gm_items = $gm_cat ? array_filter($gm_found['items'], function ($x) use ($gm_cat) { return (int) $x['main_cat'] === $gm_cat; }) : $gm_found['items'];
                Registry::set('runtime.gm_search', [
                    'q' => trim((string) $_REQUEST['q']),
                    'ids' => array_values(array_map(function ($x) { return (int) $x['id']; }, $gm_items)),
                    'mode' => $gm_found['mode'],
                    'cat' => $gm_cat,
                    'log_id' => 0,
                ]);
                // Статистика: что искали, как нашли; сбой записи не мешает поиску
                try {
                    // Метка браузера – сразу на сервере, чтобы первый же поиск, клик и корзина связались между собой
                    if (gm_search_sid() === '') {
                        $_COOKIE['gm_sid'] = bin2hex(random_bytes(8));
                        setcookie('gm_sid', $_COOKIE['gm_sid'], ['expires' => time() + 31536000, 'path' => '/', 'secure' => true, 'samesite' => 'Lax']);
                    }
                    $gm_log_id = db_query('INSERT INTO ?:gm_search_log ?e', [
                        'ts' => TIME,
                        'sid' => gm_search_sid(),
                        'source' => 'page',
                        'q' => mb_substr(trim((string) $_REQUEST['q']), 0, 120),
                        'qn' => mb_substr(gm_search_normalize((string) $_REQUEST['q']), 0, 120),
                        'mode' => $gm_found['mode'],
                        'total' => count(Registry::get('runtime.gm_search.ids')),
                        'cat' => $gm_cat,
                        'page' => max(1, isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1),
                        'sort' => !empty($_REQUEST['sort_by']) ? mb_substr((string) $_REQUEST['sort_by'] . '-' . (isset($_REQUEST['sort_order']) ? (string) $_REQUEST['sort_order'] : ''), 0, 24) : 'relevance-asc',
                        'device' => gm_search_device(),
                        'ms' => min(65535, $gm_ms),
                        'shown' => '',
                    ]);
                    Registry::set('runtime.gm_search.log_id', (int) $gm_log_id);
                } catch (\Throwable $e) {
                }
                $_REQUEST['search_performed'] = 'Y';
                if (empty($_REQUEST['sort_by'])) {
                    $_REQUEST['sort_by'] = 'relevance';
                    $_REQUEST['sort_order'] = 'asc';
                }
                // «По релевантности» – в списке доступных сортировок только на этой странице
                Registry::set('settings.Appearance.available_product_list_sortings.relevance-asc', 'Y');
                Tygh::$app['view']->assign('settings', Registry::get('settings'));
                // Подпись пункта в списке сортировки темы (abt__ut2.sort_by_<поле>_<порядок>) – без записи в базу, в кэш переводов страницы
                __('abt__ut2.sort_by_popularity_desc');
                $gm_lang = (array) Registry::get('lang_cache.' . CART_LANGUAGE);
                $gm_lang['abt__ut2.sort_by_relevance_asc'] = 'По релевантности';
                Registry::set('lang_cache.' . CART_LANGUAGE, $gm_lang);
            }
        } catch (\Throwable $e) {
            Registry::del('runtime.gm_search');
        }
    }
}
