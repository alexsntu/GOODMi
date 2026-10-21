<?php
/**
 * GOODMi: вход API маркетинг-портала для облачных routines (2026-10-21), модуль gm_marketing.
 * nginx (vhosts-resources/<сайт>/gm-mkt-api.conf): /mkt-api/<путь>?<параметры> → /mkt-api.php?gm_path=<путь>&<параметры>.
 * Подменяем адрес запроса на index.php?dispatch=gm_marketing.api – иначе SEO-модуль CS-Cart принимает /mkt-api/... за адрес
 * страницы (добавляет «/» и отдаёт 404). Сам ответ – controllers/frontend/gm_marketing.php (токен, JSON).
 */
$_GET['dispatch'] = $_REQUEST['dispatch'] = 'gm_marketing.api';
$_SERVER['QUERY_STRING'] = http_build_query($_GET);
$_SERVER['REQUEST_URI'] = '/index.php?' . $_SERVER['QUERY_STRING'];
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
require __DIR__ . '/index.php';
