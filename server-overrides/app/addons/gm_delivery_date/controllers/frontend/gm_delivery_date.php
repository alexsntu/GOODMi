<?php
/**
 * GOODMi: сроки получения товара – ответ на запрос страницы.
 * GET index.php?dispatch=gm_delivery_date.estimate&ids=1,2,3 (только AJAX).
 * Ответ: { gm_dd: { "<product_id>": { card: html, line: html, cart: html }, ... } }
 * С параметром terms=1,2 (товары карточки) в ответе ещё gm_dd_terms: { "<product_id>": html списка условий покупки }.
 * GET index.php?dispatch=gm_delivery_date.checkout – сроки для корзины по выбранному способу получения.
 * Ответ: { gm_dd_cart: { label, items: { "<product_id>": текст }, total, same, edit_url, groups: [{ title, sub, items: [{ name, url, img, amount }] }] } }
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode === 'estimate') {
    $items = [];

    if (defined('AJAX_REQUEST') && fn_gm_delivery_date_enabled()) {
        $raw = isset($_REQUEST['ids']) ? (string) $_REQUEST['ids'] : '';
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', explode(',', $raw))))), 0, 120);

        if ($ids) {
            try {
                $items = fn_gm_delivery_date_estimate_products($ids);
            } catch (\Throwable $e) {
                // Блок сроков необязательный: при любой ошибке страница просто остаётся без него
                $items = [];
            }
        }
    }

    // Условия покупки (гарантия, возврат, оплата, доставка) – только для карточки товара, по запросу страницы
    $terms = [];
    if (defined('AJAX_REQUEST') && fn_gm_delivery_date_enabled() && !empty($_REQUEST['terms'])) {
        $raw = (string) $_REQUEST['terms'];
        $term_ids = array_slice(array_values(array_unique(array_filter(array_map('intval', explode(',', $raw))))), 0, 10);
        if ($term_ids) {
            try {
                $terms = fn_gm_delivery_date_terms($term_ids);
            } catch (\Throwable $e) {
                $terms = [];
            }
        }
    }

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('gm_dd', $items ?: new \stdClass());
        Tygh::$app['ajax']->assign('gm_dd_terms', $terms ?: new \stdClass());
        // Диагностика: какую зону покупателя определил модуль (видна в ответе запроса)
        if (fn_gm_delivery_date_enabled()) {
            Tygh::$app['ajax']->assign('gm_dd_zone', fn_gm_delivery_date_client_zone());
            if (!empty($_REQUEST['gm_dd_debug'])) {
                try {
                    Tygh::$app['ajax']->assign('gm_dd_location', Tygh::$app['location']->getLocation()->toArray());
                } catch (\Throwable $e) {
                }
            }
        }
    }

    exit;
}

if ($mode === 'checkout') {
    // Сроки по выбранному способу получения для товаров корзины (страница оформления заказа)
    $result = [];

    if (defined('AJAX_REQUEST') && fn_gm_delivery_date_enabled()) {
        try {
            $cart = isset(Tygh::$app['session']['cart']) && is_array(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : [];
            $result = fn_gm_delivery_date_estimate_cart($cart);
            // Товары заказа по срокам – карточки с фотографиями в конце формы (показываем и когда срок не посчитан)
            $order = fn_gm_delivery_date_checkout_groups($cart, $result);
            if ($order) {
                $result['edit_url'] = $order['edit_url'];
                $result['groups'] = $order['groups'];
            }
        } catch (\Throwable $e) {
            $result = [];
        }
    }

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('gm_dd_cart', $result ?: new \stdClass());
    }

    exit;
}

return [CONTROLLER_STATUS_NO_PAGE];
