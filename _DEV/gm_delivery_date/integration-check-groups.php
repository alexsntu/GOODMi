<?php
/**
 * Проверка карточек товаров для оформления заказа (fn_gm_delivery_date_checkout_groups) на настоящей базе: только чтение.
 * Запуск на сервере: /opt/php83/bin/php integration-check-groups.php /путь/к/папке/модуля
 * Если в магазине стоит версия модуля без этой функции, она берётся из проверяемой папки.
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
if (!function_exists('fn_gm_delivery_date_checkout_groups')) {
    $src = file_get_contents($addon_dir . '/func.php');
    $pos = strpos($src, '/** «1 товар»');
    if ($pos === false) {
        exit("В проверяемой папке нет новых функций\n");
    }
    eval(substr($src, $pos));
    echo "(функции взяты из проверяемой папки)\n";
}

$ids = db_get_hash_single_array('SELECT product_code, product_id FROM ?:products WHERE product_code IN (?a)', ['product_code', 'product_id'], ['19159', '17376']);
$zero = (int) db_get_field('SELECT product_id FROM ?:products WHERE company_id = 2 AND status = ?s AND amount = 0 LIMIT 1', 'A');
$a = (int) $ids['19159']; // есть в трёх городах
$b = (int) $ids['17376']; // только Севастополь
$now = new DateTimeImmutable('2026-10-07 15:00', new DateTimeZone('Europe/Moscow')); // среда

$cart = function ($shipping, array $pids, array $ud = []) {
    $products = [];
    foreach ($pids as $i => $pid) {
        $products['k' . $i] = ['product_id' => $pid, 'amount' => $i + 1];
    }
    return ['chosen_shipping' => [0 => $shipping], 'products' => $products, 'user_data' => $ud];
};
$show = function ($title, $c) use ($now) {
    $r = fn_gm_delivery_date_checkout_groups($c, fn_gm_delivery_date_estimate_cart($c, $now));
    echo "== $title\n";
    if (!$r) {
        echo "   (ничего)\n";
        return;
    }
    echo '   ссылка в корзину: ', parse_url($r['edit_url'], PHP_URL_PATH), "\n";
    foreach ($r['groups'] as $g) {
        echo '   [', $g['title'], '] ', $g['sub'], "\n";
        foreach ($g['items'] as $it) {
            echo '      × ', $it['amount'], ' | ', mb_substr($it['name'], 0, 40), ' | фото: ', $it['img'] ? basename(parse_url($it['img'], PHP_URL_PATH)) : 'НЕТ', ' | ', parse_url($it['url'], PHP_URL_PATH), "\n";
        }
    }
};

$show('Самовывоз Муссон, оба товара', $cart(16, [$a, $b]));
$show('Самовывоз Меганом (Симф): сроки разные', $cart(17, [$a, $b]));
$show('СДЭК, Керчь', $cart(35, [$a, $b], ['s_country' => 'RU', 's_state' => 'KRY', 's_city' => 'Керчь']));
$show('Самовывоз Муссон, товар + позиция без остатка', $cart(16, [$a, $zero]));
$show('«Обсудить с менеджером» (13) – срока нет', $cart(13, [$a]));
$show('Способ не выбран', ['products' => ['k' => ['product_id' => $a, 'amount' => 1]]]);
$show('Пустая корзина', ['chosen_shipping' => [16], 'products' => []]);
