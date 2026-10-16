<?php
/**
 * GOODMi: пункт меню админки «Покупатели → Подтверждение номера» – страница ручного подтверждения телефона
 * покупателя (gm_cabinet.phones, app/controllers/backend/gm_cabinet.php) – и «Покупатели → Заблокированные номера»
 * (gm_cabinet.blocked): номера, на которые сайт перестал слать СМС после неудачных попыток. Лежит в модуле gm_delivery_date,
 * потому что CS-Cart дополняет меню только из модулей.
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$schema['central']['customers']['items']['gm_phone_verify'] = [
    'title' => 'Подтверждение номера',
    'href' => 'gm_cabinet.phones',
    'position' => 150,
];
$schema['central']['customers']['items']['gm_phone_blocked'] = [
    'title' => 'Заблокированные номера',
    'href' => 'gm_cabinet.blocked',
    'position' => 151,
];

// Свой поиск GOODMi (2026-10-16): статистика, проверка запроса, синонимы, закрепления, индекс – app/controllers/backend/gm_search.php.
// Виден только при выбранной витрине GOODMi (хук backend_menu_generate_before_process_item в func.php)
$schema['central']['products']['items']['gm_search'] = [
    'title' => 'Поиск GOODMi',
    'href' => 'gm_search.manage',
    'position' => 1501,
];

return $schema;
