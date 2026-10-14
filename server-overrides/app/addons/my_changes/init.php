<?php
/**
 * Модуль «Мои изменения» (my_changes): подключение хуков (функции – в func.php).
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

fn_register_hooks(
    // Сравнение: вариант группировки «По разделам» в настройках модуля ab__extended_comparison_wishlist
    'ab__extended_comparison_wishlist_group_by_post',
    // Личный кабинет Maxmobiles (app/mm_cabinet), только витрина company_id = 1 – копии обработчиков GOODMi
    // из модуля gm_delivery_date: отзыв от купившего в магазине, доступ к заказу по подтверждённому номеру,
    // вход по номеру телефона, без письма на e-mail-заглушку, код после регистрации, до подтверждения – только
    // страница подтверждения
    'product_reviews_is_user_eligible_to_write_product_review',
    'is_order_allowed_post',
    'auth_routines',
    'update_user_pre',
    'update_profile',
    'before_dispatch'
);
