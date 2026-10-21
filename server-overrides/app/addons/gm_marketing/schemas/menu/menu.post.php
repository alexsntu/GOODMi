<?php
/**
 * GOODMi: пункт меню админки «Маркетинг → Маркетинг-портал» (2026-10-21), страницы – controllers/backend/gm_marketing.php
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$schema['central']['marketing']['items']['gm_marketing'] = [
    'title' => 'Маркетинг-портал',
    'href' => 'gm_marketing.drafts',
    'position' => 50,
    'subitems' => [
        'gm_marketing_drafts' => ['title' => 'Черновики', 'href' => 'gm_marketing.drafts', 'position' => 100],
        'gm_marketing_topics' => ['title' => 'Темы статей', 'href' => 'gm_marketing.topics', 'position' => 200],
        'gm_marketing_tasks' => ['title' => 'Задачи', 'href' => 'gm_marketing.tasks', 'position' => 300],
        'gm_marketing_plan' => ['title' => 'План', 'href' => 'gm_marketing.plan', 'position' => 400],
        'gm_marketing_promo_ideas' => ['title' => 'Идеи акций', 'href' => 'gm_marketing.promo_ideas', 'position' => 500],
        'gm_marketing_reposts' => ['title' => 'Предложение постов', 'href' => 'gm_marketing.reposts', 'position' => 600],
        'gm_marketing_help' => ['title' => 'Справка', 'href' => 'gm_marketing.help', 'position' => 800],
    ],
];

return $schema;
