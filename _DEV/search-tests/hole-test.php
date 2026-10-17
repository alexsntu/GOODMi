<?php
// Временный тест 2026-10-17 (удаляется сразу после проверки): подключение к поиску ровно как в gm-search.php, но через порт 9399,
// где посредник «проглатывает» запрос со словом hangtest. Показывает, отпускает ли обработчик PHP сайта зависший поиск.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$t0 = microtime(true);
require __DIR__ . '/app/gm_search/lib.php';
$mc = mysqli_init();
$mc->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1);
$mc->options(MYSQLI_OPT_READ_TIMEOUT, 3);
@$mc->real_connect('127.0.0.1', '', '', '', 9399);
$out = ['sapi' => PHP_SAPI, 'php' => PHP_VERSION, 'connect_errno' => $mc->connect_errno];
if (!$mc->connect_errno) {
    $mc->set_charset('utf8mb4');
    $q = isset($_GET['q']) ? (string) $_GET['q'] : 'xiaomi';
    $f = @gm_search($mc, $q, 20);
    $out += ['q' => $q, 'mode' => $f['mode'], 'found' => count($f['items']), 'errno' => $mc->errno,
        'fallback' => in_array($mc->errno, [2006, 2013], true)];
}
$out['seconds'] = round(microtime(true) - $t0, 2);
echo json_encode($out, JSON_UNESCAPED_UNICODE);
