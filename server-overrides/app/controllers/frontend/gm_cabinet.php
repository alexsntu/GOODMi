<?php
/**
 * GOODMi: личный кабинет.
 * index.php?dispatch=gm_cabinet.bonuses – страница «Мои бонусы»: баланс, уровень и история операций из БонусПлюс,
 * последние заказы (сайт и магазины).
 * index.php?dispatch=gm_cabinet.orders[&src=site|store][&page=N] – «Мои заказы»: заказы сайта и покупки в магазинах
 * одним списком, с составом и ссылками на товары. Только витрина GOODMi и только вошедший покупатель; номер телефона берётся
 * из его профиля в базе, из запроса не принимается. Логика – в app/gm_cabinet/lib.php и store.php,
 * шаблон – design/themes/abt__unitheme2/templates/views/gm_cabinet/bonuses.tpl.
 * index.php?dispatch=gm_cabinet.products[&page=N] – «Купленные товары»: товары из выданных заказов сайта и магазинов.
 * POST index.php?dispatch=gm_cabinet.verify (action=send|check, code, back) – подтверждение номера телефона кодом из СМС.
 * Пока номер не подтверждён (и подтверждение включено ключом phone_verify в secrets.php), страницы не показывают данные,
 * найденные по номеру: покупки в магазинах, заказы без входа и бонусы.
 *
 * php index.php --dispatch=gm_cabinet.sync_orders [--minutes=50] – загрузка заказов МойСклад в таблицу ?:gm_ms_orders
 * (только из командной строки; ночное задание в cron). Первый запуск проходит всю историю, дальше – изменённое.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode === 'phone') {
    // Страница «Номер телефона»: состояние подтверждения и смена номера через код из СМС
    if ((int) Registry::get('runtime.company_id') !== 2) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    if (empty($auth['user_id'])) {
        return [CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url'))];
    }
    require_once Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';
    $user_id = (int) $auth['user_id'];
    if (!gm_cab_verify_enabled($user_id)) {
        return [CONTROLLER_STATUS_REDIRECT, 'profiles.update'];
    }
    $phone = fn_gm_cab_user_phone($user_id);
    Tygh::$app['view']->assign('gm_cab', [
        'nav' => fn_gm_cab_nav($user_id, 'profile'),
        'profile_url' => fn_url('profiles.update'),
        'has_phone' => $phone !== '',
        'state' => fn_gm_cab_verify_state($user_id, $phone),
        'url' => fn_url('gm_cabinet.verify'),
    ]);
    fn_add_breadcrumb('Номер телефона');
}

if ($mode === 'bonuses' || $mode === 'orders' || $mode === 'products') {
    if ((int) Registry::get('runtime.company_id') !== 2) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    if (empty($auth['user_id'])) {
        return [
            CONTROLLER_STATUS_REDIRECT,
            'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')),
        ];
    }

    require_once Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';

    $user_id = (int) $auth['user_id'];
    $phone = fn_gm_cab_user_phone($user_id);
    // Данные по номеру показываем только после подтверждения номера кодом из СМС
    $verify = null;
    $trusted_phone = $phone;
    if ($phone !== '' && gm_cab_verify_enabled($user_id)) {
        $state = fn_gm_cab_verify_state($user_id, $phone);
        if (!$state['verified']) {
            $trusted_phone = '';
            $verify = $state + ['url' => fn_url('gm_cabinet.verify'), 'back' => $mode];
        }
    }
    $store_orders = [];
    $site_orders = [];
    if ($trusted_phone !== '') {
        fn_gm_cab_refresh_cached($trusted_phone);
    }
    if ($mode !== 'products') {
        $store_orders = $trusted_phone !== '' ? gm_cab_store_display(fn_gm_cab_store_get($trusted_phone)) : [];
        $site_orders = fn_gm_cab_site_orders($user_id, $trusted_phone);
    }
    $nav = [
        'verify' => $verify,
        'nav' => fn_gm_cab_nav($user_id, $mode === 'products' ? 'purchased' : $mode),
        'bonuses_url' => fn_url('gm_cabinet.bonuses'),
        'orders_url' => fn_url('gm_cabinet.orders'),
        'profile_url' => fn_url('profiles.update'),
        'program_url' => 'https://goodmi.ru/bonusnaya-programma/',
    ];
}

if ($mode === 'verify') {
    if ((int) Registry::get('runtime.company_id') !== 2) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    $back = isset($_REQUEST['back']) && in_array($_REQUEST['back'], ['bonuses', 'phone'], true) ? $_REQUEST['back'] : 'orders';
    if (empty($auth['user_id'])) {
        return [CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode('gm_cabinet.' . $back)];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';
        $user_id = (int) $auth['user_id'];
        $phone = fn_gm_cab_user_phone($user_id);
        if (gm_cab_verify_enabled($user_id)) {
            $action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
            $state = fn_gm_cab_verify_state($user_id, $phone);
            $result = null;
            if ($action === 'change') {
                // Смена номера: код уходит на новый номер, в профиль он попадёт только после ввода кода
                $new_phone = gm_cab_phone(isset($_REQUEST['new_phone']) ? $_REQUEST['new_phone'] : '');
                if ($new_phone === '') {
                    $result = ['ok' => false, 'message' => 'Введите номер полностью, например +7 978 000-00-00.'];
                } elseif ($new_phone === $phone) {
                    $result = ['ok' => false, 'message' => 'Это ваш текущий номер.'];
                } else {
                    $result = fn_gm_cab_verify_send($user_id, $new_phone);
                }
            } elseif ($action === 'check') {
                $target = $state['change_phone'] !== '' ? $state['change_phone'] : $phone;
                $result = fn_gm_cab_verify_check($user_id, $target, isset($_REQUEST['code']) ? $_REQUEST['code'] : '');
                if ($result['ok'] && $target !== $phone) {
                    fn_gm_cab_set_user_phone($user_id, $target);
                    $result['message'] = 'Номер изменён и подтверждён.';
                }
            } elseif (!$state['verified']) {
                $result = fn_gm_cab_verify_send($user_id, $phone);
            }
            if ($result) {
                fn_set_notification($result['ok'] ? 'N' : 'E', $result['ok'] ? 'Готово' : 'Ошибка', $result['message']);
            }
        }
    }

    return [CONTROLLER_STATUS_REDIRECT, 'gm_cabinet.' . $back];
}

if ($mode === 'products') {
    // «Купленные товары»: при первом показе догружаем состав покупок в магазинах из МойСклад
    $complete = true;
    if ($trusted_phone !== '') {
        try {
            $complete = fn_gm_cab_store_load_items($trusted_phone);
        } catch (\Throwable $e) {
            $complete = false;
        }
    }
    $list = fn_gm_cab_purchased($user_id, $trusted_phone);
    $per_page = 24;
    $pages = max(1, (int) ceil(count($list) / $per_page));
    $page = isset($_REQUEST['page']) ? max(1, min($pages, (int) $_REQUEST['page'])) : 1;

    Tygh::$app['view']->assign('gm_cab', $nav + [
        'items' => array_slice($list, ($page - 1) * $per_page, $per_page),
        'total' => count($list),
        'complete' => $complete,
        'page' => $page,
        'pages' => $pages,
        'prev_url' => $page > 1 ? fn_url('gm_cabinet.products' . ($page > 2 ? '?page=' . ($page - 1) : '')) : '',
        'next_url' => $page < $pages ? fn_url('gm_cabinet.products?page=' . ($page + 1)) : '',
        'catalog_url' => fn_url(''),
    ]);

    fn_add_breadcrumb('Купленные товары');
}

if ($mode === 'bonuses') {
    $bonus = null;
    if ($trusted_phone !== '') {
        $cache_dir = rtrim(Registry::get('config.dir.cache_misc'), '/') . '/gm_cabinet';
        try {
            $bonus = gm_cab_cached($cache_dir, 'bp_' . $phone, 300, function () use ($phone) {
                return gm_cab_bonusplus($phone);
            });
        } catch (\Throwable $e) {
            // Внешний сервис необязателен: при сбое страница показывает то, что есть
        }
    }

    Tygh::$app['view']->assign('gm_cab', $nav + [
        'has_phone' => $phone !== '',
        'bonus' => $bonus,
        'orders' => gm_cab_merge_orders($site_orders, $store_orders, 8),
        'orders_total' => count($site_orders) + count($store_orders),
    ]);

    fn_add_breadcrumb('Мои бонусы');
}

if ($mode === 'orders') {
    // Отборы: актуальные или завершённые, источник (сайт или магазин), год – для завершённых
    $all = gm_cab_decorate_orders(gm_cab_merge_orders($site_orders, $store_orders, 0));
    $count = ['active' => 0, 'done' => 0];
    foreach ($all as $order) {
        $count[$order['done'] ? 'done' : 'active']++;
    }
    $tab = isset($_REQUEST['tab']) && in_array($_REQUEST['tab'], ['active', 'done'], true)
        ? $_REQUEST['tab'] : ($count['active'] > 0 ? 'active' : 'done');
    $src = isset($_REQUEST['src']) && in_array($_REQUEST['src'], ['site', 'store'], true) ? $_REQUEST['src'] : 'all';
    $year = isset($_REQUEST['year']) && preg_match('/^20\d\d$/', (string) $_REQUEST['year']) ? (string) $_REQUEST['year'] : '';

    $years = [];
    $src_count = ['all' => 0, 'site' => 0, 'store' => 0];
    $list = [];
    foreach ($all as $order) {
        if ($order['done'] !== ($tab === 'done')) {
            continue;
        }
        $src_count['all']++;
        $src_count[$order['source']]++;
        if ($src !== 'all' && $order['source'] !== $src) {
            continue;
        }
        if ($tab === 'done' && $order['year'] !== '') {
            $years[$order['year']] = true;
        }
        if ($tab === 'done' && $year !== '' && $order['year'] !== $year) {
            continue;
        }
        $list[] = $order;
    }
    krsort($years);

    $per_page = 10;
    $pages = max(1, (int) ceil(count($list) / $per_page));
    $page = isset($_REQUEST['page']) ? max(1, min($pages, (int) $_REQUEST['page'])) : 1;
    $shown = array_slice($list, ($page - 1) * $per_page, $per_page);

    // Состав показанных заказов: заказы сайта – из базы магазина, покупки в магазинах – из таблицы (с дочитыванием)
    $site_ids = [];
    $store_ids = [];
    foreach ($shown as $order) {
        if ($order['source'] === 'site') {
            $site_ids[] = $order['id'];
        } elseif ($order['ms_id'] !== '') {
            $store_ids[$order['ms_id']] = $order['items_at'];
        }
    }
    $site_items = fn_gm_cab_site_items($site_ids);
    $store_items = [];
    try {
        $store_items = fn_gm_cab_store_items($store_ids);
    } catch (\Throwable $e) {
    }
    foreach ($shown as $k => $order) {
        if ($order['source'] === 'site') {
            $items = isset($site_items[$order['id']]) ? $site_items[$order['id']] : [];
        } else {
            $items = isset($store_items[$order['ms_id']]) ? $store_items[$order['ms_id']] : [];
        }
        $shown[$k]['items'] = $items;
        // «Оценить товар» – у полученных заказов, ведёт в отзывы первого товара, который есть на сайте
        $shown[$k]['review_url'] = '';
        if ($order['kind'] === 'received') {
            foreach ($items as $item) {
                if ($item['review_url'] !== '') {
                    $shown[$k]['review_url'] = $item['review_url'];
                    break;
                }
            }
        }
    }

    // Ссылки отборов: каждый параметр меняется отдельно, остальные сохраняются
    $link = function (array $change) use ($tab, $src, $year) {
        $params = array_merge(['tab' => $tab, 'src' => $src, 'year' => $year, 'page' => 1], $change);
        if ($params['tab'] !== 'done') {
            $params['year'] = '';
        }
        $query = [];
        foreach (['tab', 'src', 'year', 'page'] as $name) {
            $value = (string) $params[$name];
            if ($value === '' || ($name === 'src' && $value === 'all') || ($name === 'page' && $value === '1')) {
                continue;
            }
            $query[] = $name . '=' . $value;
        }

        return fn_url('gm_cabinet.orders' . ($query ? '?' . implode('&', $query) : ''));
    };
    $year_links = [['title' => 'Все годы', 'url' => $link(['year' => '']), 'active' => $year === '']];
    foreach (array_keys($years) as $y) {
        $year_links[] = ['title' => (string) $y, 'url' => $link(['year' => (string) $y]), 'active' => $year === (string) $y];
    }

    Tygh::$app['view']->assign('gm_cab', $nav + [
        'has_phone' => $phone !== '',
        'orders' => $shown,
        'total' => count($all),
        'tabs' => [
            ['title' => 'Актуальные', 'count' => $count['active'], 'url' => $link(['tab' => 'active']), 'active' => $tab === 'active'],
            ['title' => 'Завершённые', 'count' => $count['done'], 'url' => $link(['tab' => 'done']), 'active' => $tab === 'done'],
        ],
        'sources' => [
            ['title' => 'Все', 'count' => $src_count['all'], 'url' => $link(['src' => 'all']), 'active' => $src === 'all'],
            ['title' => 'Интернет-магазин', 'count' => $src_count['site'], 'url' => $link(['src' => 'site']), 'active' => $src === 'site'],
            ['title' => 'Магазины', 'count' => $src_count['store'], 'url' => $link(['src' => 'store']), 'active' => $src === 'store'],
        ],
        'years' => $tab === 'done' && count($years) > 1 ? $year_links : [],
        'page' => $page,
        'pages' => $pages,
        'prev_url' => $page > 1 ? $link(['page' => $page - 1]) : '',
        'next_url' => $page < $pages ? $link(['page' => $page + 1]) : '',
    ]);

    fn_add_breadcrumb('Мои заказы');
}

if ($mode === 'sync_orders') {
    if (PHP_SAPI !== 'cli') {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    require_once Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';
    @set_time_limit(0);

    $lock_dir = rtrim(Registry::get('config.dir.cache_misc'), '/') . '/gm_cabinet';
    if (!is_dir($lock_dir)) {
        @mkdir($lock_dir, 0775, true);
    }
    $lock = @fopen($lock_dir . '/sync.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        echo "Загрузка уже идёт\n";
        exit(0);
    }

    $minutes = isset($_REQUEST['minutes']) ? max(1, min(600, (int) $_REQUEST['minutes'])) : 50;
    $stat = fn_gm_cab_store_sync($minutes * 60, function ($s) {
        echo date('H:i:s'), ' страниц ', $s['pages'], ', заказов ', $s['orders'], ', записано ', $s['stored'], ', отметка ', $s['cursor'], "\n";
    });
    echo date('Y-m-d H:i:s'), ' итог: страниц ', $stat['pages'], ', заказов ', $stat['orders'], ', записано ', $stat['stored'],
        ', отметка ', $stat['cursor'], $stat['done'] ? ', дошли до конца' : ', не закончено', $stat['error'] !== '' ? ', ошибка: ' . $stat['error'] : '', "\n";

    flock($lock, LOCK_UN);
    fclose($lock);
    exit($stat['error'] !== '' ? 1 : 0);
}
