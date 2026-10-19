<?php
/**
 * GOODMi: выгрузка товаров на Авито (автозагрузка) для витрин GOODMi и Maxmobiles вместо UnitXML в МойСклад.
 * Этап 1 (2026-10-19): таблицы, перенос фида UnitXML, генератор XML, ссылка на файл.
 * Этап 2 (2026-10-19): вкладка «Авито» в карточке товара (сохраняется хуком update_product_post), страницы
 * «Товары → Авито: настройки» и «Авито: шаблоны категорий» (controllers/backend/gm_avito.php).
 * Функции – в func.php, ссылка на файл – controllers/frontend/gm_avito.php, сборка и заполнение по cron – bin/.
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

fn_register_hooks('update_product_post');
