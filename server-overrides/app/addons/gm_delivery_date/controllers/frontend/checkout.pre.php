<?php
/**
 * GOODMi: покупатель, зарегистрировавшийся по номеру телефона, не может оформить заказ, пока не подтвердит номер
 * (кодом из СМС или через оператора интернет-магазина). Вместо страницы оформления и при отправке заказа он попадает
 * в «Мои заказы», где ждёт блок подтверждения. Корзина и каталог не закрываются. Проверка – fn_gm_cab_verify_required
 * в app/gm_cabinet/store.php: действует только на записи, отмеченные при регистрации (must_verify), то есть созданные
 * формой регистрации с 2026-10-13. Лежит в модуле gm_delivery_date, потому что CS-Cart подключает pre-контроллеры
 * только из модулей. Только витрина GOODMi.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (($mode === 'checkout' || $mode === 'place_order') && !empty($auth['user_id']) && (int) Registry::get('runtime.company_id') === 2) {
    $gm_store = Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';
    $gm_required = false;
    try {
        if (is_file($gm_store)) {
            require_once $gm_store;
            $gm_required = fn_gm_cab_verify_required((int) $auth['user_id']);
        }
    } catch (\Throwable $e) {
        // При сбое оформление заказа работает штатно
        $gm_required = false;
    }
    if ($gm_required) {
        fn_set_notification('W', 'Подтвердите номер телефона', 'Чтобы оформить заказ, подтвердите номер: введите код из СМС или позвоните оператору интернет-магазина по номеру 8 (800) 250-17-00 с этого телефона. Товары в корзине сохранятся.');

        return [CONTROLLER_STATUS_REDIRECT, 'gm_cabinet.orders'];
    }
}
