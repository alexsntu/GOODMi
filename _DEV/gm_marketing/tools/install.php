<?php
/**
 * Установка модуля gm_marketing (2026-10-21). Только CLI, из корня сайта, от пользователя сайта:
 *   php <путь>/install.php   – таблицы уже созданы tables.sql; регистрирует модуль и чистит кэш (меню админки)
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$ok = fn_install_addon('gm_marketing', false);
fn_clear_cache();
echo 'gm_marketing: ', $ok ? 'установлен' : 'НЕ установлен', ', статус ', db_get_field('SELECT status FROM ?:addons WHERE addon = ?s', 'gm_marketing'), "\n";
