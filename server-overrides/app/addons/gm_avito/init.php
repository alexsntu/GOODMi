<?php
/**
 * GOODMi: выгрузка товаров на Авито (автозагрузка) для витрин GOODMi и Maxmobiles вместо UnitXML в МойСклад.
 * Этап 1 (2026-10-19): таблицы, перенос фида UnitXML, генератор XML, ссылка на файл. Хуков пока нет.
 * Функции – в func.php, ссылка на файл и сборка по cron – controllers/frontend/gm_avito.php.
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}
