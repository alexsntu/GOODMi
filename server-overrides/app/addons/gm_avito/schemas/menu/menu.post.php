<?php
/**
 * GOODMi: пункт меню админки «Товары → Авито» с подпунктами «Настройки», «Проблемы и отчёт Авито», «Шаблоны категорий»,
 * «Инструкция»
 * (2026-10-19), страницы – app/addons/gm_avito/controllers/backend/gm_avito.php
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$schema['central']['products']['items']['gm_avito'] = [
    'title' => 'Авито',
    'href' => 'gm_avito.manage',
    'position' => 900,
    'subitems' => [
        'gm_avito_manage' => [
            'title' => 'Настройки',
            'href' => 'gm_avito.manage',
            'position' => 100,
        ],
        'gm_avito_problems' => [
            'title' => 'Проблемы и отчёт Авито',
            'href' => 'gm_avito.problems',
            'position' => 200,
        ],
        'gm_avito_templates' => [
            'title' => 'Шаблоны категорий',
            'href' => 'gm_avito.templates',
            'position' => 300,
        ],
        'gm_avito_help' => [
            'title' => 'Инструкция',
            'href' => 'gm_avito.help',
            'position' => 400,
        ],
    ],
];

return $schema;
