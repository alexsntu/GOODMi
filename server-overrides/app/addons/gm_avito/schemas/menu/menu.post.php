<?php
/**
 * GOODMi: пункты меню админки «Товары → Авито: настройки», «Авито: шаблоны категорий» и «Авито: проблемы» (2026-10-19),
 * страницы – app/addons/gm_avito/controllers/backend/gm_avito.php
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$schema['central']['products']['items']['gm_avito'] = [
    'title' => 'Авито: настройки',
    'href' => 'gm_avito.manage',
    'position' => 900,
];
$schema['central']['products']['items']['gm_avito_problems'] = [
    'title' => 'Авито: проблемы',
    'href' => 'gm_avito.problems',
    'position' => 902,
];
$schema['central']['products']['items']['gm_avito_templates'] = [
    'title' => 'Авито: шаблоны категорий',
    'href' => 'gm_avito.templates',
    'position' => 901,
];

return $schema;
