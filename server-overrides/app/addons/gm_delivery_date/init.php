<?php
/**
 * GOODMi: подключение обработчиков модуля.
 * product_reviews_is_user_eligible_to_write_product_review – отзыв на товар может оставить и тот, кто купил его
 * в магазине (заказ МойСклад в личном кабинете), а не только в интернет-магазине.
 * is_order_allowed_post – покупатель может открыть заказ сайта, оформленный без входа на его подтверждённый номер.
 * auth_routines – вход по подтверждённому номеру телефона вместо e-mail (пароль тот же).
 * update_user_pre – на e-mail-заглушку (запись без своей почты) письмо о регистрации не шлём.
 * update_profile – отправка кода подтверждения номера сразу после регистрации.
 * before_dispatch – пока зарегистрировавшийся по номеру не подтвердил номер, ему открыта только страница подтверждения.
 * top_menu_form_post – подменю, построенное из категорий, не открывается в новом окне (ошибка темы на PHP 7.4).
 * Сами функции – в func.php.
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

fn_register_hooks(
    'product_reviews_is_user_eligible_to_write_product_review',
    'is_order_allowed_post',
    'auth_routines',
    'update_user_pre',
    'update_profile',
    'before_dispatch',
    'top_menu_form_post',
    // Меню админки: пункты кабинета GOODMi – только при выбранной витрине GOODMi
    'backend_menu_generate_before_process_item',
    // Свой поиск GOODMi (этап 3, 2026-10-16): страница результатов products.search – товары из Manticore
    'get_products_pre',
    'get_products',
    'get_products_post',
    'products_sorting',
    // Статистика поиска GOODMi (этап 4): корзина и заказ после поиска
    'add_to_cart',
    'place_order'
);
