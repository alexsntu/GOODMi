<?php
/**
 * GOODMi: подключение обработчиков модуля.
 * product_reviews_is_user_eligible_to_write_product_review – отзыв на товар может оставить и тот, кто купил его
 * в магазине (заказ МойСклад в личном кабинете), а не только в интернет-магазине.
 * is_order_allowed_post – покупатель может открыть заказ сайта, оформленный без входа на его подтверждённый номер.
 * Сами функции – в func.php.
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

fn_register_hooks(
    'product_reviews_is_user_eligible_to_write_product_review',
    'is_order_allowed_post'
);
