<?php
/**
 * Модуль «Мои изменения» (my_changes): подключение хуков (функции – в func.php).
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

fn_register_hooks(
    // Сравнение: вариант группировки «По разделам» в настройках модуля ab__extended_comparison_wishlist
    'ab__extended_comparison_wishlist_group_by_post'
);
