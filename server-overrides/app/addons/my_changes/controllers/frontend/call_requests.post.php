<?php
/**
 * Maxmobiles (2026-10-20): окно «Быстрый заказ» (call_requests.request) показывает цену товара. Штатный контроллер берёт
 * товар через fn_get_product_data без каталожных акций – здесь цена досчитывается так же, как в каталоге
 * (fn_gather_additional_product_data со скидками), чтобы совпадала с плиткой и карточкой. Вывод цены – в
 * addons/call_requests/views/call_requests/components/call_requests_content.tpl темы (только company_id = 1).
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode === 'request' && (int) Registry::get('runtime.company_id') === 1) {
    $view = Tygh::$app['view'];
    $product = $view->getTemplateVars('product');
    if (!empty($product['product_id'])) {
        fn_gather_additional_product_data($product, false, false, false, true, false);
        $view->assign('product', $product);
    }
}
