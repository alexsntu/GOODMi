<?php
/**
 * GOODMi: пункт меню админки «Покупатели → Подтверждение номера» – страница ручного подтверждения телефона
 * покупателя (gm_cabinet.phones, app/controllers/backend/gm_cabinet.php). Лежит в модуле gm_delivery_date,
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

return $schema;
