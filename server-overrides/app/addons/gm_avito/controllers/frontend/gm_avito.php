<?php
/**
 * GOODMi: выгрузка на Авито – ссылка на файл автозагрузки и сборка (этап 1, 2026-10-19).
 *   index.php?dispatch=gm_avito.feed&key=<ключ витрины> – отдаёт собранный файл (витрина – по адресу сайта); без верного ключа – 404.
 * Сборка файла по cron – app/addons/gm_avito/bin/build.php (только CLI).
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$company_id = (int) fn_get_runtime_company_id();

if ($mode === 'feed') {
    $key = (string) fn_gm_avito_get_setting($company_id, 'feed_key', '');
    if ($key === '' || !isset($_REQUEST['key']) || !hash_equals($key, (string) $_REQUEST['key'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    $path = fn_gm_avito_feed_path($company_id);
    if (!is_file($path)) {
        fn_gm_avito_build_feed($company_id);
    }
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    header('Content-Length: ' . filesize($path));
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
    readfile($path);
    exit;
}

return [CONTROLLER_STATUS_NO_PAGE];
