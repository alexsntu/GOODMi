<?php
/**
 * Проверка расчёта для оформления заказа на настоящей базе БЕЗ установки: только чтение.
 * Запуск на сервере: /opt/php83/bin/php integration-check-cart.php /путь/к/папке/модуля
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
if (!function_exists('fn_gm_delivery_date_estimate_cart')) {
    // модуль не установлен или установлена старая версия – берём функции из проверяемой папки
    if (!function_exists('fn_gm_delivery_date_enabled')) {
        require $addon_dir . '/func.php';
    } else {
        echo "ВНИМАНИЕ: в магазине уже загружена версия модуля без функций оформления; проверяю только синтаксис новой.\n";
        exit(2);
    }
}

$ids = db_get_hash_single_array('SELECT product_code, product_id FROM ?:products WHERE product_code IN (?a)', ['product_code', 'product_id'], ['19159', '17376']);
$zero = (int) db_get_field('SELECT product_id FROM ?:products WHERE company_id = 2 AND status = ?s AND amount = 0 LIMIT 1', 'A');
$a = (int) $ids['19159']; // есть в трёх городах
$b = (int) $ids['17376']; // только Севастополь
$now = new DateTimeImmutable('2026-10-07 15:00', new DateTimeZone('Europe/Moscow')); // среда

$cart = function ($shipping, array $pids, array $ud = []) {
    $products = [];
    foreach ($pids as $i => $pid) {
        $products['k' . $i] = ['product_id' => $pid, 'amount' => 1];
    }
    return ['chosen_shipping' => [0 => $shipping], 'products' => $products, 'user_data' => $ud];
};
$show = function ($title, $r) {
    echo str_pad($title, 62), '| ', $r ? ($r['label'] . ($r['same'] ? '' : ', весь заказ') . ': ' . $r['total'] . ' || ' . json_encode($r['items'], JSON_UNESCAPED_UNICODE)) : '(ничего)', "\n";
};

$show('Самовывоз Муссон, оба товара', fn_gm_delivery_date_estimate_cart($cart(16, [$a, $b]), $now));
$show('Самовывоз Меганом (Симф), оба: первый есть, второй из Сев', fn_gm_delivery_date_estimate_cart($cart(17, [$a, $b]), $now));
$show('Самовывоз Ялта, оба', fn_gm_delivery_date_estimate_cart($cart(18, [$a, $b]), $now));
$show('Курьер, адрес Севастополь', fn_gm_delivery_date_estimate_cart($cart(21, [$a, $b], ['s_country' => 'RU', 's_state' => 'SEV', 's_city' => 'Севастополь']), $now));
$show('Курьер, адрес Симферополь', fn_gm_delivery_date_estimate_cart($cart(22, [$a, $b], ['s_country' => 'RU', 's_state' => 'KRY', 's_city' => 'Симферополь']), $now));
$show('Курьер, адрес Ялта (курьера нет)', fn_gm_delivery_date_estimate_cart($cart(21, [$a], ['s_country' => 'RU', 's_state' => 'KRY', 's_city' => 'Ялта']), $now));
$show('СДЭК, Керчь', fn_gm_delivery_date_estimate_cart($cart(35, [$a, $b], ['s_country' => 'RU', 's_state' => 'KRY', 's_city' => 'Керчь']), $now));
$show('СДЭК, Новосибирск', fn_gm_delivery_date_estimate_cart($cart(14, [$a], ['s_country' => 'RU', 's_state' => 'NVS', 's_city' => 'Новосибирск']), $now));
$show('Самовывоз Муссон, товар + позиция без остатка', fn_gm_delivery_date_estimate_cart($cart(16, [$a, $zero]), $now));
$show('«Обсудить с менеджером» (13)', fn_gm_delivery_date_estimate_cart($cart(13, [$a]), $now));
$show('Пустая корзина', fn_gm_delivery_date_estimate_cart(['chosen_shipping' => [16], 'products' => []], $now));
