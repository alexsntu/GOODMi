<?php
/**
 * GOODMi: вкладка «Авито» в карточке товара (2026-10-19) – только для товаров витрин GOODMi и Maxmobiles.
 * Разметка – design/backend/templates/addons/gm_avito/hooks/products/tabs_content.post.tpl, сохранение – хук
 * update_product_post (func.php).
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $mode === 'update' && !empty($_REQUEST['product_id'])) {
    $product_id = (int) $_REQUEST['product_id'];
    $company_id = (int) db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
    if (array_key_exists($company_id, GM_AVITO_COMPANIES)) {
        Registry::set('navigation.tabs.gm_avito', ['title' => 'Авито', 'js' => true]);
        Tygh::$app['view']->assign('gm_avito', fn_gm_avito_admin_item($company_id, $product_id));
    }
}
