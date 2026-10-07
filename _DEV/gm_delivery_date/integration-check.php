<?php
/**
 * Проверка модуля на настоящей базе магазина БЕЗ установки: только чтение.
 * Запуск на сервере: /opt/php83/bin/php integration-check.php /путь/к/временной/папке/модуля
 * Подключает ядро CS-Cart как витрину goodmi.ru, затем функции модуля из временной папки.
 */
$addon_dir = rtrim($argv[1], '/');
$root = '/var/www/maxmobiles/data/www/maxmobiles.ru';

$_SERVER['HTTP_HOST'] = 'goodmi.ru';
$_SERVER['SERVER_NAME'] = 'goodmi.ru';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

define('AREA', 'C');
chdir($root);
require $root . '/init.php';

require $addon_dir . '/func.php';
require_once $addon_dir . '/lib/engine.php';

echo 'runtime.company_id = ', Tygh\Registry::get('runtime.company_id'), ' | enabled = ', var_export(fn_gm_delivery_date_enabled(), true), "\n";

$codes = ['19162', '19159', '17376'];
$ids = db_get_hash_single_array('SELECT product_code, product_id FROM ?:products WHERE product_code IN (?a)', ['product_code', 'product_id'], $codes);
// плюс товар без остатков, если такой найдётся
$zero = db_get_field('SELECT p.product_id FROM ?:products p WHERE p.company_id = 2 AND p.status = ?s AND p.amount = 0 LIMIT 1', 'A');
$pids = array_values(array_map('intval', $ids));
if ($zero) {
    $pids[] = (int) $zero;
}
echo 'товары: ', json_encode($ids, JSON_UNESCAPED_UNICODE), ' | без остатка: ', (int) $zero, "\n";

$t0 = microtime(true);
$stock = fn_gm_delivery_date_get_stock($pids);
printf("запрос остатков: %.1f мс\n", (microtime(true) - $t0) * 1000);
foreach ($stock as $pid => $w) {
    $where = $w === null ? 'нет складского учёта (блок не показываем)' : (implode(',', array_keys(array_filter($w))) ?: 'нет нигде');
    echo '  ', $pid, ' => ', $where, "\n";
}

$now = new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow'));
echo 'сейчас (Москва): ', $now->format('Y-m-d H:i D'), "\n";
$first = $pids[0];
foreach (['sev', 'simf', 'yalta', 'crimea', 'ru1', 'ru3'] as $zone) {
    $html = fn_gm_delivery_date_render(gm_dd_estimate($stock[$first], $zone, $now), $now);
    echo str_pad($zone, 7), '| ', trim(preg_replace('/\s+/u', ' ', strip_tags(str_replace('</div>', ' | ', $html['card'])))), "\n";
}
if ($zero) {
    $html = fn_gm_delivery_date_render(gm_dd_estimate($stock[(int) $zero], 'sev', $now), $now);
    echo 'нет в наличии, каталог: ', strip_tags($html['line']), ' | карточка: ', mb_substr(strip_tags($html['card']), 0, 60), "…\n";
}

// Скорость на странице каталога: 60 товаров одним запросом
$many = db_get_fields('SELECT product_id FROM ?:products WHERE company_id = 2 AND status = ?s ORDER BY product_id DESC LIMIT 60', 'A');
$t0 = microtime(true);
$s = fn_gm_delivery_date_get_stock($many);
printf("60 товаров: %.1f мс, с наличием %d, под заказ %d, без учёта %d\n", (microtime(true) - $t0) * 1000,
    count(array_filter($s, function ($w) { return $w && array_filter($w); })),
    count(array_filter($s, function ($w) { return is_array($w) && !array_filter($w); })),
    count(array_filter($s, 'is_null')));

echo 'зона клиента из консоли (локации нет): ', fn_gm_delivery_date_client_zone(), "\n";
