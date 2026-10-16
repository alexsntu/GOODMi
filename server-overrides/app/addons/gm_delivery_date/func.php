<?php
/**
 * GOODMi: сроки получения товара. Данные и вывод; сами правила – lib/engine.php.
 * Модуль работает только на витрине GOODMi (company_id = 2) и ничего не меняет в ядре:
 * страница запрашивает сроки отдельным AJAX-запросом (controllers/frontend/gm_delivery_date.php),
 * поэтому кэш страниц и блоков на расчёт не влияет.
 * Исключение – карточка товара на шаблоне goodmi_card: там блок выводится сразу в странице
 * (fn_gm_delivery_date_card_html), а запрос нужен только для пересчёта при смене города.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/** Витрина, для которой работает модуль. */
function fn_gm_delivery_date_company_id()
{
    return 2;
}

/** Склад «Интернет-магазин» (Севастополь): товар только на нём – порог самовывоза 17:00. */
function fn_gm_delivery_date_internet_warehouse_ids()
{
    return [50];
}

function fn_gm_delivery_date_enabled()
{
    return (int) Registry::get('runtime.company_id') === fn_gm_delivery_date_company_id();
}

/** Город склада → код города в правилах. */
function fn_gm_delivery_date_city_code($city)
{
    $city = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $city), 'UTF-8') : strtolower(trim((string) $city));
    $map = ['севастополь' => 'sev', 'симферополь' => 'simf', 'ялта' => 'yalta'];

    return isset($map[$city]) ? $map[$city] : null;
}

/**
 * Наличие товаров по городам.
 *
 * @param int[] $product_ids
 *
 * @return array product_id => ['sev' => bool, 'sev_shop' => bool, 'simf' => bool, 'yalta' => bool]
 *               или product_id => null, если у товара нет складского учёта, а общий остаток есть
 *               (про такой товар мы ничего не обещаем и блок не показываем).
 */
function fn_gm_delivery_date_get_stock(array $product_ids)
{
    $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
    if (!$product_ids) {
        return [];
    }

    $rows = db_get_array(
        'SELECT a.product_id, a.warehouse_id, d.city'
        . ' FROM ?:warehouses_products_amount AS a'
        . ' INNER JOIN ?:store_locations AS l ON l.store_location_id = a.warehouse_id'
        . ' LEFT JOIN ?:store_location_descriptions AS d ON d.store_location_id = l.store_location_id AND d.lang_code = ?s'
        . ' WHERE a.product_id IN (?n) AND a.amount > 0 AND l.status = ?s AND l.company_id = ?i',
        'ru',
        $product_ids,
        'A',
        fn_gm_delivery_date_company_id()
    );

    $internet = fn_gm_delivery_date_internet_warehouse_ids();
    $stock = [];
    foreach ($rows as $row) {
        $code = fn_gm_delivery_date_city_code($row['city']);
        if ($code === null) {
            continue;
        }
        $pid = (int) $row['product_id'];
        if (!isset($stock[$pid])) {
            $stock[$pid] = ['sev' => false, 'sev_shop' => false, 'simf' => false, 'yalta' => false];
        }
        $stock[$pid][$code] = true;
        if ($code === 'sev' && !in_array((int) $row['warehouse_id'], $internet, true)) {
            $stock[$pid]['sev_shop'] = true;
        }
    }

    // Товары без остатков на наших складах: «под заказ», если и общий остаток нулевой;
    // если общий остаток есть (товар ведётся без складов) – ничего не показываем.
    $missing = array_values(array_diff($product_ids, array_keys($stock)));
    if ($missing) {
        $amounts = db_get_hash_single_array(
            'SELECT product_id, amount FROM ?:products WHERE product_id IN (?n)',
            ['product_id', 'amount'],
            $missing
        );
        foreach ($missing as $pid) {
            $has_plain_amount = isset($amounts[$pid]) && (int) $amounts[$pid] > 0;
            $stock[$pid] = $has_plain_amount ? null : ['sev' => false, 'sev_shop' => false, 'simf' => false, 'yalta' => false];
        }
    }

    return $stock;
}

/** Пояса СДЭК по коду региона (правило 9). Всё, чего нет в списках, – пояс 1. */
function fn_gm_delivery_date_ru_belt($state)
{
    static $belt2 = ['SVE', 'CHE', 'KGN', 'TYU', 'KHM', 'YAN', 'OMS', 'NVS', 'TOM', 'KEM', 'ALT', 'AL'];
    static $belt3 = ['KYA', 'KK', 'TY', 'IRK', 'BU', 'ZAB', 'SA', 'AMU', 'KHA', 'PRI', 'YEV', 'SAK', 'MAG', 'KAM', 'CHU'];

    if (in_array($state, $belt3, true)) {
        return 'ru3';
    }
    if (in_array($state, $belt2, true)) {
        return 'ru2';
    }

    return 'ru1';
}

/**
 * Где находится покупатель: 'sev' | 'simf' | 'yalta' | 'crimea' | 'ru1' | 'ru2' | 'ru3' | 'other'.
 * Берётся город, выбранный покупателем в шапке (модуль geo_maps).
 */
function fn_gm_delivery_date_client_zone()
{
    try {
        $location = Tygh::$app['location']->getLocation()->toArray();
    } catch (\Throwable $e) {
        return 'other';
    }

    return fn_gm_delivery_date_zone_from_location($location);
}

/**
 * Зона по адресу вида ['country' => 'RU', 'state' => 'KRY', 'city' => 'Ялта'].
 */
function fn_gm_delivery_date_zone_from_location(array $location)
{
    $country = isset($location['country']) ? strtoupper((string) $location['country']) : '';
    $state = isset($location['state']) ? strtoupper(trim((string) $location['state'])) : '';
    $city = fn_gm_delivery_date_city_code(isset($location['city']) ? $location['city'] : '');

    if ($country !== '' && $country !== 'RU') {
        return 'other';
    }
    if ($state === 'SEV' || $city === 'sev') {
        return 'sev';
    }
    if ($city === 'simf' || $city === 'yalta') {
        return $city;
    }
    if ($state === 'KRY') {
        return 'crimea';
    }
    if ($state === '') {
        return 'other';
    }

    return fn_gm_delivery_date_ru_belt($state);
}

function fn_gm_delivery_date_icon($name)
{
    static $paths = [
        'pin' => '<path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'truck' => '<rect x="1.5" y="6" width="12.5" height="10" rx="1.5"/><path d="M14 9h4l3.5 3.5V16H14z"/><circle cx="6" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/>',
        'box' => '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>',
    ];

    return '<svg class="gm-dd__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

/**
 * Готовые подписи по результату расчёта.
 *
 * @return array список строк ['icon' => ..., 'label' => ..., 'short' => ..., 'date' => ...]
 */
function fn_gm_delivery_date_lines(array $estimate, \DateTimeInterface $now)
{
    // date – полная подпись («10 октября, после 18:00»), date_short – с сокращённым месяцем для узких карточек
    $tz = 'Europe/Moscow';
    $lines = [];
    if (!empty($estimate['pickup'])) {
        $tail = !empty($estimate['pickup']['after']) ? ', после ' . $estimate['pickup']['after'] : '';
        $lines[] = [
            'icon' => 'pin',
            'label' => 'Пункт выдачи',
            'short' => 'Пункт выдачи',
            'date' => gm_dd_human_date($estimate['pickup']['date'], $now) . $tail,
            'date_short' => gm_dd_human_date($estimate['pickup']['date'], $now, $tz, true) . $tail,
        ];
    }
    if (!empty($estimate['courier'])) {
        $tail = !empty($estimate['courier']['until']) ? ', до ' . $estimate['courier']['until'] : '';
        $lines[] = [
            'icon' => 'truck',
            'label' => 'Доставим на дом',
            'short' => 'На дом',
            'date' => gm_dd_human_date($estimate['courier']['date'], $now) . $tail,
            'date_short' => gm_dd_human_date($estimate['courier']['date'], $now, $tz, true) . $tail,
        ];
    }
    if (!empty($estimate['cdek'])) {
        $lines[] = [
            'icon' => 'box',
            'label' => 'Доставка СДЭК',
            'short' => 'СДЭК',
            'date' => gm_dd_human_range($estimate['cdek']['from'], $estimate['cdek']['to']),
            'date_short' => gm_dd_human_range($estimate['cdek']['from'], $estimate['cdek']['to'], $tz, true),
        ];
    }

    return $lines;
}

/**
 * HTML блока для одного товара в трёх видах: card (карточка товара), line (каталог), cart (корзина).
 * Пустая строка – блок не показываем.
 */
function fn_gm_delivery_date_render(array $estimate, \DateTimeInterface $now)
{
    $e = function ($s) {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };

    // Заголовок блока в карточке товара (оформление – секция 35 «Своего CSS»)
    $title = '<div class="gm-dd__title">Доставка и возврат</div>';

    if (!empty($estimate['preorder'])) {
        $full = 'Сейчас данного товара нет в наличии, но мы можем его привезти под заказ.'
            . ' Уточнить сроки доставки и актуальную цену вы можете после оформления заказа.';

        return [
            'card' => '<div class="gm-dd gm-dd--card gm-dd--preorder">' . $title . $e($full) . '</div>',
            // В каталоге про «под заказ» не пишем: на карточке уже есть штатная метка «Предзаказ»
            'line' => '',
            'cart' => '<div class="gm-dd gm-dd--cart gm-dd--preorder"><span>Под заказ</span></div>',
        ];
    }

    $lines = fn_gm_delivery_date_lines($estimate, $now);
    if (!$lines) {
        return ['card' => '', 'line' => '', 'cart' => ''];
    }

    $card = $line = $cart = '';
    foreach ($lines as $l) {
        $icon = fn_gm_delivery_date_icon($l['icon']);
        $card .= '<div class="gm-dd__row">' . $icon . '<span class="gm-dd__label">' . $e($l['label']) . '</span>'
            . '<b class="gm-dd__date">' . $e($l['date']) . '</b></div>';
        $cart .= '<span>' . $icon . $e($l['short']) . ' &#8211; <b>' . $e($l['date']) . '</b></span>';
        // Каталог: на телефоне (узкая карточка) стили показывают дату с сокращённым месяцем
        $date = $l['date'] === $l['date_short']
            ? $e($l['date'])
            : '<i class="gm-dd__full">' . $e($l['date']) . '</i><i class="gm-dd__short" hidden>' . $e($l['date_short']) . '</i>';
        $line .= '<span>' . $icon . $e($l['short']) . ' &#8211; <b>' . $date . '</b></span>';
    }

    return [
        'card' => '<div class="gm-dd gm-dd--card">' . $title . $card . '</div>',
        'line' => '<div class="gm-dd gm-dd--line">' . $line . '</div>',
        'cart' => '<div class="gm-dd gm-dd--cart">' . $cart . '</div>',
    ];
}

/**
 * Главная функция: сроки для списка товаров при текущей локации покупателя.
 *
 * @return array product_id => ['card' => html, 'line' => html, 'cart' => html]
 */
function fn_gm_delivery_date_estimate_products(array $product_ids, $now = null)
{
    require_once __DIR__ . '/lib/engine.php';

    $now = $now instanceof \DateTimeInterface ? $now : new \DateTimeImmutable('now');
    $zone = fn_gm_delivery_date_client_zone();
    if ($zone === 'other') {
        // Локация не определена или не Россия – ничего не обещаем
        return [];
    }
    $stock = fn_gm_delivery_date_get_stock($product_ids);

    $result = [];
    foreach ($stock as $pid => $where) {
        if ($where === null) {
            continue;
        }
        $html = fn_gm_delivery_date_render(gm_dd_estimate($where, $zone, $now), $now);
        if ($html['card'] !== '') {
            $result[$pid] = $html;
        }
    }

    return $result;
}

/**
 * Способы доставки магазина: что это за способ и к какому городу относится пункт выдачи.
 * Номера – из Администрирование → Способы доставки (витрина GOODMi).
 */
function fn_gm_delivery_date_shipping_map()
{
    return [
        16 => ['type' => 'pickup', 'zone' => 'sev'],   // ТЦ «Муссон»
        33 => ['type' => 'pickup', 'zone' => 'sev'],   // ТЦ «Мандарин»
        20 => ['type' => 'pickup', 'zone' => 'sev'],   // ул. Адм. Октябрьского, 9
        17 => ['type' => 'pickup', 'zone' => 'simf'],  // ТРК «Меганом»
        18 => ['type' => 'pickup', 'zone' => 'yalta'], // ТЦ «Дом Торговли»
        21 => ['type' => 'courier'],
        22 => ['type' => 'courier'],
        35 => ['type' => 'cdek'],                      // СДЭК в Крыму
        14 => ['type' => 'cdek'],                      // СДЭК
    ];
}

/**
 * Сроки для оформления заказа: по выбранному способу получения, для каждого товара корзины.
 *
 * @param array $cart корзина из сессии
 *
 * @return array ['label' => 'Самовывоз'|..., 'items' => [product_id => текст срока], 'total' => текст для всего заказа]
 *               или пустой массив, если сказать нечего
 */
function fn_gm_delivery_date_estimate_cart(array $cart, $now = null)
{
    require_once __DIR__ . '/lib/engine.php';

    $now = $now instanceof \DateTimeInterface ? $now : new \DateTimeImmutable('now');
    $map = fn_gm_delivery_date_shipping_map();

    $shipping_id = 0;
    if (!empty($cart['chosen_shipping']) && is_array($cart['chosen_shipping'])) {
        $shipping_id = (int) reset($cart['chosen_shipping']);
    }
    if (!isset($map[$shipping_id]) || empty($cart['products'])) {
        return [];
    }
    $method = $map[$shipping_id];
    $type = $method['type'];

    if ($type === 'pickup') {
        $zone = $method['zone'];
    } else {
        $ud = isset($cart['user_data']) && is_array($cart['user_data']) ? $cart['user_data'] : [];
        $zone = fn_gm_delivery_date_zone_from_location([
            'country' => isset($ud['s_country']) ? $ud['s_country'] : '',
            'state' => isset($ud['s_state']) ? $ud['s_state'] : '',
            'city' => isset($ud['s_city']) ? $ud['s_city'] : '',
        ]);
        if ($zone === 'other') {
            $zone = fn_gm_delivery_date_client_zone();
        }
    }

    $pids = [];
    foreach ($cart['products'] as $item) {
        if (!empty($item['product_id'])) {
            $pids[] = (int) $item['product_id'];
        }
    }
    $stock = fn_gm_delivery_date_get_stock($pids);

    $items = [];
    $latest = null; // самый поздний срок – когда готов весь заказ
    foreach ($stock as $pid => $where) {
        if ($where === null) {
            continue;
        }
        $est = gm_dd_estimate($where, $zone, $now);
        if (!empty($est['preorder'])) {
            $items[$pid] = 'под заказ';
            $latest = ['sort' => '9999', 'text' => 'срок уточним после оформления'];
            continue;
        }
        if (empty($est[$type])) {
            continue;
        }
        $lines = fn_gm_delivery_date_lines([$type => $est[$type]], $now);
        $text = $lines[0]['date'];
        $items[$pid] = $text;
        $sort = $type === 'cdek' ? $est['cdek']['to'] : $est[$type]['date'] . (isset($est[$type]['after']) ? $est[$type]['after'] : '');
        if ($latest === null || $sort > $latest['sort']) {
            $latest = ['sort' => $sort, 'text' => $text];
        }
    }
    if (!$items) {
        return [];
    }

    $labels = ['pickup' => 'Самовывоз', 'courier' => 'Доставка курьером', 'cdek' => 'Доставка СДЭК'];

    return ['label' => $labels[$type], 'items' => $items, 'total' => $latest['text'], 'same' => count(array_unique($items)) === 1];
}

/** «1 товар», «2 товара», «5 товаров». */
function fn_gm_delivery_date_plural_items($n)
{
    $n = abs((int) $n);
    $n10 = $n % 10;
    $n100 = $n % 100;
    if ($n10 === 1 && $n100 !== 11) {
        return $n . ' товар';
    }
    if ($n10 >= 2 && $n10 <= 4 && ($n100 < 12 || $n100 > 14)) {
        return $n . ' товара';
    }

    return $n . ' товаров';
}

/**
 * Товары корзины для страницы оформления, сгруппированные по сроку получения
 * (карточки «Можно забрать сегодня» / «Привезём 10 октября» с фотографиями товаров).
 *
 * @param array $cart     корзина из сессии
 * @param array $estimate результат fn_gm_delivery_date_estimate_cart() для этой же корзины (может быть пустым)
 *
 * @return array ['edit_url', 'groups' => [['title', 'sub', 'items' => [['name','url','img','amount']]]]]
 */
function fn_gm_delivery_date_checkout_groups(array $cart, array $estimate)
{
    if (empty($cart['products']) || !is_array($cart['products'])) {
        return [];
    }

    // Количество по каждому товару (один товар может лежать в корзине несколькими строками)
    $amounts = [];
    foreach ($cart['products'] as $item) {
        if (empty($item['product_id'])) {
            continue;
        }
        $pid = (int) $item['product_id'];
        $amounts[$pid] = (isset($amounts[$pid]) ? $amounts[$pid] : 0) + (isset($item['amount']) ? (int) $item['amount'] : 1);
    }
    if (!$amounts) {
        return [];
    }
    $pids = array_keys($amounts);
    $lang = defined('CART_LANGUAGE') ? CART_LANGUAGE : 'ru';

    $names = db_get_hash_single_array(
        'SELECT product_id, product FROM ?:product_descriptions WHERE product_id IN (?n) AND lang_code = ?s',
        ['product_id', 'product'],
        $pids,
        $lang
    );

    // Фотографии: ошибка в них не должна ломать блок – тогда карточка выйдет без фото
    $images = [];
    try {
        $pairs = fn_get_image_pairs($pids, 'product', 'M', true, true, $lang);
        foreach ($pids as $pid) {
            if (empty($pairs[$pid]) || !is_array($pairs[$pid])) {
                continue;
            }
            $pair = reset($pairs[$pid]);
            $shown = fn_image_to_display($pair, 144, 144);
            if (!empty($shown['image_path'])) {
                $images[$pid] = $shown['image_path'];
            }
        }
    } catch (\Throwable $e) {
        $images = [];
    }

    $shipping_id = 0;
    if (!empty($cart['chosen_shipping']) && is_array($cart['chosen_shipping'])) {
        $shipping_id = (int) reset($cart['chosen_shipping']);
    }
    $method = $shipping_id
        ? trim((string) db_get_field('SELECT shipping FROM ?:shipping_descriptions WHERE shipping_id = ?i AND lang_code = ?s', $shipping_id, $lang))
        : '';

    $map = fn_gm_delivery_date_shipping_map();
    $type = isset($map[$shipping_id]) ? $map[$shipping_id]['type'] : '';
    $prefix = ['pickup' => 'Можно забрать ', 'courier' => 'Привезём ', 'cdek' => 'Доставка СДЭК '];
    $dates = !empty($estimate['items']) ? $estimate['items'] : [];

    $groups = [];
    foreach ($pids as $pid) {
        $text = isset($dates[$pid]) ? $dates[$pid] : '';
        if ($text === 'под заказ') {
            $key = 'preorder';
            $rank = 3;
            $title = 'Под заказ';
            $sub = 'срок уточним после оформления заказа';
        } elseif ($text !== '' && isset($prefix[$type])) {
            $key = 'date-' . $text;
            $rank = strpos($text, 'сегодня') === 0 ? 0 : (strpos($text, 'завтра') === 0 ? 1 : 2);
            $title = $prefix[$type] . $text;
            $sub = $method;
        } else {
            $key = 'none';
            $rank = 4;
            $title = 'Товары в заказе';
            $sub = $method;
        }
        if (!isset($groups[$key])) {
            $groups[$key] = ['rank' => $rank, 'pos' => count($groups), 'title' => $title, 'sub' => $sub, 'count' => 0, 'items' => []];
        }
        $groups[$key]['count'] += $amounts[$pid];
        $groups[$key]['items'][] = [
            'name' => isset($names[$pid]) ? (string) $names[$pid] : '',
            'url' => fn_url('products.view?product_id=' . $pid),
            'img' => isset($images[$pid]) ? $images[$pid] : '',
            'amount' => $amounts[$pid],
        ];
    }
    // «Сегодня», «завтра», остальные даты (в порядке корзины), «под заказ», без срока
    usort($groups, function ($a, $b) {
        return $a['rank'] === $b['rank'] ? $a['pos'] - $b['pos'] : $a['rank'] - $b['rank'];
    });

    $out = [];
    foreach ($groups as $g) {
        $count = fn_gm_delivery_date_plural_items($g['count']);
        $out[] = ['title' => $g['title'], 'sub' => $g['sub'] !== '' ? $count . ' · ' . $g['sub'] : $count, 'items' => $g['items']];
    }

    return ['edit_url' => fn_url('checkout.cart'), 'groups' => $out];
}

/**
 * Срок гарантии по категориям каталога GOODMi (сроки продиктованы 2026-10-08).
 * Ключ – номер категории, значение – код срока: y1, m6, m3, m1, w2; пустая строка – срок не показываем.
 * Срок наследуется вниз по дереву: у товара берётся главная категория и ищется ближайшая вверх
 * категория из списка, поэтому новые подкатегории (модели смартфонов и т. п.) дописывать не нужно.
 * Категорий, которых здесь нет, строка «Гарантия» не касается – она просто не выводится.
 */
function fn_gm_delivery_date_warranty_map()
{
    return [
        // 1 год
        412 => 'y1',  // Смартфоны (все подкатегории)
        2172 => 'y1', // Ноутбуки
        455 => 'y1',  // Телевизоры
        526 => 'y1',  // Электросамокаты
        527 => 'y1',  // Гироскутеры, моноколеса
        529 => 'y1',  // Электроскутеры и электровелосипеды
        615 => 'y1',  // Умный свет (освещение, все подкатегории)
        530 => 'y1',  // Смарт-часы
        587 => 'y1',  // WiFi-роутеры (сетевое оборудование)
        534 => 'y1',  // Экшн-камеры
        533 => 'y1',  // Видеорегистраторы
        448 => 'y1',  // Планшеты
        457 => 'y1',  // Мониторы
        459 => 'y1',  // Проекторы
        2556 => 'y1', // Игровые консоли
        553 => 'y1',  // Пылесосы: роботы, вертикальные, ручные, мойщики окон
        557 => '',    // Комплектующие для пылесосов (мешки, фильтры, щётки) – срок не показываем
        // 6 месяцев
        622 => 'm6',  // Умный дом (все подкатегории)
        531 => 'm6',  // Фитнес-браслеты
        2208 => 'm6', // Электроинструмент
        2548 => 'm6', // Портативные электростанции
        2164 => 'm6', // Приготовление блюд: блендеры, грили, плиты, мясорубки, тостеры (мелкая бытовая техника)
        589 => 'm6',  // Электрические чайники
        2162 => 'm6', // Уход за одеждой и глажка: утюги, отпариватели
        641 => 'm6',  // Фены
        633 => 'm6',  // Стайлеры, выпрямители и плойки
        635 => 'm6',  // Машинки для стрижки волос
        713 => 'm6',  // Триммеры
        643 => 'm6',  // Электробритвы и фотоэпиляторы
        2161 => 'm6', // Климатическое оборудование (кроме двух подкатегорий ниже)
        716 => 'w2',  // Аксессуары для очистителей воздуха – как аксессуары
        565 => '',    // Ароматизаторы воздуха – срок не показываем
        456 => 'm6',  // ТВ-приставки
        637 => 'm6',  // Ирригаторы
        644 => 'm6',  // Массажёры
        604 => 'm6',  // Всё для кофе
        577 => 'm6',  // Напольные весы
        596 => 'm6',  // Кухонные весы
        472 => 'm6',  // Компрессоры и насосы
        473 => 'm6',  // Пуско-зарядные устройства
        2189 => 'm6', // Мойки высокого давления
        // 3 месяца
        548 => 'm3',  // Внешние аккумуляторы
        499 => 'm3',  // Клавиатуры
        502 => 'm3',  // Мыши
        720 => 'm3',  // Клавиатура + мышь
        542 => 'm3',  // Акустика и умные колонки
        645 => 'm3',  // Термометры и градусники (мед. приборы)
        640 => 'm3',  // Зубные щётки
        576 => 'm3',  // Инструменты (кроме электроинструмента – он выше)
        // 1 месяц
        476 => 'm1',  // Зарядки в авто (АЗУ)
        517 => 'm1',  // Аксессуары для экшн-камер
        539 => 'm1',  // Беспроводные наушники
        606 => 'm1',  // Посуда
        593 => 'm1',  // Ножи
        590 => 'm1',  // Термосы и термокружки
        659 => 'm1',  // Чемоданы
        656 => 'm1',  // Очки
        543 => 'm1',  // Саундбары
        // 2 недели
        541 => 'w2',  // Проводные наушники
        546 => 'w2',  // Сетевые зарядки и адаптеры
        549 => 'w2',  // Беспроводные зарядки
        460 => 'w2',  // Аксессуары (все подкатегории, кроме аксессуаров для экшн-камер)
        545 => 'w2',  // Аксессуары для наушников
        660 => 'w2',  // Аксессуары для одежды
        653 => 'w2',  // Перчатки
        662 => 'w2',  // Рюкзаки
        654 => 'w2',  // Сумки
        550 => 'w2',  // Кабели для зарядки
    ];
}

/**
 * Код срока гарантии для товаров по их главной категории на витрине GOODMi.
 *
 * @param int[] $product_ids
 *
 * @return array product_id => код срока (y1, m6, m3, m1, w2); товаров без срока в ответе нет
 */
function fn_gm_delivery_date_warranty_codes(array $product_ids)
{
    $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
    if (!$product_ids) {
        return [];
    }

    $rows = db_get_array(
        'SELECT pc.product_id, c.id_path, pc.link_type'
        . ' FROM ?:products_categories AS pc'
        . ' INNER JOIN ?:categories AS c ON c.category_id = pc.category_id'
        . ' WHERE pc.product_id IN (?n) AND c.company_id = ?i',
        $product_ids,
        fn_gm_delivery_date_company_id()
    );

    // Только главная категория: дополнительные («Уценка», подборки) срок не определяют
    $paths = [];
    foreach ($rows as $row) {
        if ($row['link_type'] === 'M') {
            $paths[(int) $row['product_id']] = (string) $row['id_path'];
        }
    }

    $map = fn_gm_delivery_date_warranty_map();
    $result = [];
    foreach ($paths as $pid => $path) {
        foreach (array_reverse(explode('/', $path)) as $category_id) {
            $category_id = (int) $category_id;
            if (isset($map[$category_id])) {
                if ($map[$category_id] !== '') {
                    $result[$pid] = $map[$category_id];
                }
                break;
            }
        }
    }

    return $result;
}

/**
 * Условия покупки под блоком сроков в карточке товара: гарантия (если срок известен), возврат, оплата, доставка.
 *
 * @param int[] $product_ids
 *
 * @return array product_id => html списка
 */
function fn_gm_delivery_date_terms(array $product_ids)
{
    static $labels = ['y1' => '1 год', 'm6' => '6 месяцев', 'm3' => '3 месяца', 'm1' => '1 месяц', 'w2' => '2 недели'];
    static $icons = [
        'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
        'back' => '<path d="M4 12a8 8 0 1 0 2.3-5.6"/><path d="M4 4v4h4"/>',
        'card' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/>',
        'truck' => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17.5" cy="17.5" r="1.5"/>',
    ];
    $row = function ($icon, $html) use ($icons) {
        return '<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
            . ' stroke-linejoin="round" aria-hidden="true">' . $icons[$icon] . '</svg><div>' . $html . '</div></li>';
    };

    $common = $row('back', '<a href="/info/vozvrat-ru/">Возврат и обмен</a> <span>&#8211; 14 дней</span>')
        . $row('card', '<span>Оплата картой, наличными или в </span><a href="/credit/">кредит</a>')
        . $row('truck', '<a href="/info/delivery/">Доставка СДЭК</a> <span>по России, самовывоз в Крыму</span>');

    $codes = fn_gm_delivery_date_warranty_codes($product_ids);
    $result = [];
    foreach ($product_ids as $pid) {
        $pid = (int) $pid;
        $warranty = '';
        if (isset($codes[$pid])) {
            $code = $codes[$pid];
            // «Оригинальная техника» – только у техники; у аксессуаров и одежды строка короче
            $tail = in_array($code, ['y1', 'm6', 'm3'], true) ? ' <span>&#8211; оригинальная техника</span>' : '';
            $warranty = $row('shield', '<a href="/info/guarantee/">Гарантия ' . $labels[$code] . '</a>' . $tail);
        }
        $result[$pid] = '<ul class="gm-terms">' . $warranty . $common . '</ul>';
    }

    return $result;
}

/**
 * Карточка товара на шаблоне goodmi_card: блок сроков получения и условия покупки одной строкой HTML,
 * чтобы они были в странице сразу, без отдельного запроса. Вызывается из хука products:gm_card_delivery.
 * Разметка та же, что отдаёт запрос gm_delivery_date.estimate (card + условия), поэтому при смене города
 * скрипт модуля заменяет блок как обычно. При любой ошибке – пустая строка: карточка остаётся без блока.
 *
 * @param int $product_id
 *
 * @return string
 */
function fn_gm_delivery_date_card_html($product_id)
{
    $pid = (int) $product_id;
    if ($pid <= 0 || !fn_gm_delivery_date_enabled()) {
        return '';
    }

    try {
        $items = fn_gm_delivery_date_estimate_products([$pid]);
        $terms = fn_gm_delivery_date_terms([$pid]);
    } catch (\Throwable $e) {
        return '';
    }

    return (isset($items[$pid]['card']) ? $items[$pid]['card'] : '') . (isset($terms[$pid]) ? $terms[$pid] : '');
}

/**
 * Право оставить отзыв (модуль «Отзывы о товарах», настройка «только после покупки»): магазин штатно считает покупкой
 * только заказ сайта. На витрине GOODMi покупкой считается и выданный заказ в магазине – по данным личного кабинета
 * (app/gm_cabinet/store.php: подтверждённый номер, МойСклад). Для таких покупателей – один отзыв на товар.
 * Обработчик живёт в этом модуле, потому что CS-Cart подключает обработчики только из модулей.
 */
function fn_gm_delivery_date_product_reviews_is_user_eligible_to_write_product_review($user_id, $product_id, $ip, $need_to_buy_first, $review_ip_check, &$result)
{
    $code = 'product_reviews.need_to_buy_first';
    if (!$need_to_buy_first || !$user_id || !$product_id || (int) \Tygh\Registry::get('runtime.company_id') !== fn_gm_delivery_date_company_id() || !is_object($result)) {
        return;
    }
    $errors = $result->getErrors();
    if (!isset($errors[$code])) {
        return;
    }
    try {
        $store = \Tygh\Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';
        if (!is_file($store)) {
            return;
        }
        require_once $store;
        if (!fn_gm_cab_bought_in_store((int) $user_id, (int) $product_id)) {
            return;
        }
        $result->removeError($code);
        if (db_get_field('SELECT 1 FROM ?:product_reviews WHERE user_id = ?i AND product_id = ?i LIMIT 1', $user_id, $product_id)) {
            $result->addError('gm_cabinet.already_reviewed', 'Вы уже оставили отзыв на этот товар.');

            return;
        }
        if (!$result->hasErrors()) {
            $result->setSuccess(true);
        }
    } catch (\Throwable $e) {
        // Проверка покупки в магазине необязательна: при сбое действует штатное правило
    }
}

/**
 * Доступ к заказу сайта: штатно покупатель видит только заказы своей учётной записи. На витрине GOODMi вошедший
 * покупатель может открыть и заказ, оформленный без входа на его подтверждённый номер телефона (такие заказы
 * показывает «Мои заказы» личного кабинета). Проверка – fn_gm_cab_order_by_phone в app/gm_cabinet/store.php.
 */
function fn_gm_delivery_date_is_order_allowed_post($order_id, $auth, &$allowed)
{
    if (!empty($allowed) || empty($auth['user_id']) || !$order_id || !defined('AREA') || AREA !== 'C'
        || (int) \Tygh\Registry::get('runtime.company_id') !== fn_gm_delivery_date_company_id()) {
        return;
    }
    try {
        $store = \Tygh\Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';
        if (is_file($store)) {
            require_once $store;
            if (fn_gm_cab_order_by_phone((int) $auth['user_id'], (int) $order_id)) {
                $allowed = 1;
            }
        }
    } catch (\Throwable $e) {
        // При сбое действует штатное правило доступа
    }
}

/** Витрина GOODMi на стороне покупателя – условие всех обработчиков входа и регистрации. */
function fn_gm_delivery_date_is_goodmi_front()
{
    return defined('AREA') && AREA === 'C' && (int) \Tygh\Registry::get('runtime.company_id') === fn_gm_delivery_date_company_id();
}

/** Подключить логику личного кабинета (app/gm_cabinet/store.php). false – файла нет, обработчик должен отступить. */
function fn_gm_delivery_date_cabinet()
{
    $store = \Tygh\Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';
    if (!is_file($store)) {
        return false;
    }
    require_once $store;

    return true;
}

/**
 * Вход по номеру телефона (витрина GOODMi). Если в поле входа введён не e-mail, а номер, штатная проверка ищет
 * покупателя, у которого этот номер подтверждён и стоит в профиле; пароль проверяет сам CS-Cart. Введённое
 * покупателем значение не меняем (оно возвращается в форму при ошибке), поэтому подменяем только условие поиска:
 * «user_id = N OR NULL = 'введённое'» находит ровно этого покупателя, «NULL = 'введённое'» – никого.
 * Кого пускать и счётчик неверных паролей – fn_gm_cab_login_by_phone в app/gm_cabinet/store.php.
 * По e-mail-заглушке (gm_cab_is_stub_email) войти нельзя: настоящего адреса за ней нет.
 */
function fn_gm_delivery_date_auth_routines($request, $auth, &$field, &$condition, &$user_login)
{
    if (!fn_gm_delivery_date_is_goodmi_front()) {
        return;
    }
    $login = trim((string) $user_login);
    if ($login === '') {
        return;
    }
    try {
        if (!fn_gm_delivery_date_cabinet()) {
            return;
        }
        if (strpos($login, '@') !== false) {
            if (gm_cab_is_stub_email($login)) {
                $field = 'NULL';
            }

            return;
        }
        $found = fn_gm_cab_login_by_phone($login, isset($request['password']) ? (string) $request['password'] : '');
        if ($found === null) {
            return;
        }
        if ($found['locked']) {
            fn_set_notification('E', __('error'), 'Слишком много неудачных попыток входа по этому номеру. Попробуйте через 15 минут или восстановите пароль.');
        }
        $field = $found['user_id'] > 0 ? 'user_id = ' . (int) $found['user_id'] . ' OR NULL' : 'NULL';
    } catch (\Throwable $e) {
        // При сбое вход работает штатно, по e-mail
    }
}

/**
 * Запись без своей почты (витрина GOODMi) хранится с e-mail-заглушкой из номера телефона (fn_gm_cab_stub_email) –
 * её подставляет profiles.pre.php. Письмо о регистрации на заглушку не отправляем.
 */
function fn_gm_delivery_date_update_user_pre($user_id, $user_data, $auth, $ship_to_another, &$notify_user, &$can_update)
{
    if (!empty($user_id) || !isset($user_data['email']) || !fn_gm_delivery_date_is_goodmi_front()) {
        return;
    }
    try {
        if (fn_gm_delivery_date_cabinet() && gm_cab_is_stub_email($user_data['email'])) {
            $notify_user = false;
        }
    } catch (\Throwable $e) {
    }
}

/**
 * После регистрации через форму сайта (витрина GOODMi): сразу отправляем на номер код подтверждения; покупатель
 * попадает в «Мои заказы», где ждёт поле для кода и подсказка про оператора. Работает только для регистрации,
 * которую пометил profiles.pre.php (runtime.gm_reg_phone), – не для записей, создаваемых при оформлении заказа.
 */
function fn_gm_delivery_date_update_profile($action, &$user_data, $current_user_data)
{
    if ($action !== 'add' || empty($user_data['user_id']) || !fn_gm_delivery_date_is_goodmi_front()
        || (string) \Tygh\Registry::get('runtime.gm_reg_phone') === '') {
        return;
    }
    $user_id = (int) $user_data['user_id'];
    try {
        if (!fn_gm_delivery_date_cabinet()) {
            return;
        }
        $phone = fn_gm_cab_user_phone($user_id);
        if ($phone === '' || !gm_cab_verify_enabled($user_id)) {
            return;
        }
        // Сначала отметка «обязан подтвердить номер» (до подтверждения сайт закрыт), потом отправка кода:
        // сбой отправки не должен оставить запись с полным доступом
        fn_gm_cab_must_verify_set($user_id);
        $_REQUEST['redirect_url'] = 'gm_cabinet.orders';
        try {
            $sent = fn_gm_cab_verify_send($user_id, $phone);
        } catch (\Throwable $e) {
            $sent = ['ok' => false];
        }
        if ($sent['ok']) {
            fn_set_notification('N', 'Подтвердите номер', 'Мы отправили СМС с кодом на ' . gm_cab_phone_mask($phone) . '. Введите код, чтобы подтвердить номер: после этого можно оформлять заказы и входить по номеру.');
        } else {
            fn_set_notification('W', 'Номер пока не подтверждён', 'Отправить СМС с кодом не получилось. ' . GM_CAB_OPERATOR_TEXT);
        }
    } catch (\Throwable $e) {
        // При сбое регистрация завершается, но запись остаётся закрытой до подтверждения номера
        try {
            db_query('INSERT INTO ?:gm_phone_verify (user_id, must_verify) VALUES (?i, 1) ON DUPLICATE KEY UPDATE must_verify = 1', $user_id);
            $_REQUEST['redirect_url'] = 'gm_cabinet.orders';
        } catch (\Throwable $e2) {
        }
    }
}

/**
 * Регистрация по номеру телефона не считается завершённой, пока номер не подтверждён (решение 2026-10-13): такой
 * покупатель с любой страницы сайта попадает в «Мои заказы», где его ждёт блок подтверждения (код из СМС или звонок
 * оператору). Открыты только сама эта страница, отправка кода, профиль (исправить номер) и выход. Действует на записи,
 * отмеченные при регистрации (must_verify, см. fn_gm_cab_verify_required в app/gm_cabinet/store.php), – не на
 * покупателей, зарегистрированных раньше. Фоновые запросы страниц не трогаем, кроме корзины и оформления заказа.
 */
function fn_gm_delivery_date_before_dispatch($controller, $mode, $action, $dispatch_extra, $area)
{
    if ($area !== 'C' || defined('CONSOLE') || (int) \Tygh\Registry::get('runtime.company_id') !== fn_gm_delivery_date_company_id()) {
        return;
    }
    // Свой поиск (с 2026-10-16): старые ссылки на результаты Diginetica (?digiSearch=true&term=…, закладки и поисковики) –
    // на нашу страницу результатов
    if (!empty($_GET['digiSearch']) && isset($_GET['term']) && is_string($_GET['term']) && trim($_GET['term']) !== '') {
        fn_redirect(fn_url('products.search?search_performed=Y&q=' . urlencode(mb_substr(trim($_GET['term']), 0, 100))), false, true);
    }
    $user_id = isset(\Tygh::$app['session']['auth']['user_id']) ? (int) \Tygh::$app['session']['auth']['user_id'] : 0;
    if ($user_id <= 0) {
        return;
    }
    $open = ['gm_cabinet.orders', 'gm_cabinet.verify', 'auth.logout', 'profiles.update'];
    if (in_array($controller . '.' . $mode, $open, true) || (defined('AJAX_REQUEST') && $controller !== 'checkout')) {
        return;
    }
    try {
        if (!fn_gm_delivery_date_cabinet() || !fn_gm_cab_verify_required($user_id)) {
            return;
        }
    } catch (\Throwable $e) {
        return;
    }
    fn_set_notification('W', 'Завершите регистрацию', 'Подтвердите номер телефона: введите код из СМС или позвоните оператору интернет-магазина по номеру 8 (800) 250-17-00 с этого телефона. До подтверждения остальные разделы сайта недоступны.', '', 'gm_verify_required');
    fn_redirect('gm_cabinet.orders');
}

/**
 * Админка: отметка «номер подтверждён» возле телефона покупателя GOODMi (списки заказов и покупателей, заказ, профиль –
 * шаблоны design/backend/templates/addons/gm_delivery_date/hooks/). Возвращает 'Y' – номер подтверждён, 'N' – нет,
 * '' – номера нет или он непригоден. С $user_id проверяется подтверждение именно этого покупателя, без него
 * (заказ) – подтверждён ли номер хоть в одной учётной записи. Таблица ?:gm_phone_verify, см. app/gm_cabinet/store.php.
 */
function fn_gm_delivery_date_phone_mark($phone, $user_id = 0)
{
    static $cache = [];
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) {
        $digits = '7' . substr($digits, 1);
    } elseif (strlen($digits) === 10) {
        $digits = '7' . $digits;
    } else {
        return '';
    }
    $key = $digits . '|' . (int) $user_id;
    if (!isset($cache[$key])) {
        try {
            $found = (int) $user_id > 0
                ? db_get_field('SELECT 1 FROM ?:gm_phone_verify WHERE user_id = ?i AND phone = ?s AND verified_at > 0', (int) $user_id, $digits)
                : db_get_field('SELECT 1 FROM ?:gm_phone_verify WHERE phone = ?s AND verified_at > 0 LIMIT 1', $digits);
            $cache[$key] = $found ? 'Y' : 'N';
        } catch (\Throwable $e) {
            $cache[$key] = '';
        }
    }

    return $cache[$key];
}

/**
 * Хук top_menu_form_post: признак «открывать в новом окне» у пунктов меню – всегда true/false.
 * Пункты подменю, которые CS-Cart строит из дерева категорий (пункт с «C:<id>:Y»), получают new_window = 0 (число),
 * а шаблоны меню темы сравнивают его со строкой "Y"; на PHP 7.4, на котором работает сайт, 0 == "Y" истинно, и все
 * ссылки 2-го и 3-го уровня открывались в новом окне. Значение по смыслу не меняется: 0 → false, 1/"Y" → true.
 * Действует на меню всех витрин (2026-10-14).
 */
function fn_gm_delivery_date_top_menu_form_post(&$top_menu, $level, $active)
{
    foreach ($top_menu as &$item) {
        if (isset($item['new_window']) && !is_bool($item['new_window'])) {
            $item['new_window'] = in_array($item['new_window'], [1, '1', 'Y'], true);
        }
    }
    unset($item);
}

/**
 * Меню админки: «Покупатели → Подтверждение номера» и «Заблокированные номера» (schemas/menu/menu.post.php) – пункты кабинета
 * GOODMi – показываются только при выбранной витрине GOODMi (company_id = 2); на Maxmobiles, Xione и «Всех витринах» их нет
 * (2026-10-16, так же, как пункты Maxmobiles в модуле my_changes). Схема меню кэшируется одна на все витрины – скрываем при
 * каждом построении меню.
 */
function fn_gm_delivery_date_backend_menu_generate_before_process_item(&$menu, $group, $root, &$items)
{
    if ($root === 'customers' && (int) \Tygh\Registry::get('runtime.company_id') !== 2) {
        unset($items['items']['gm_phone_verify'], $items['items']['gm_phone_blocked']);
    }
    if ($root === 'products' && (int) \Tygh\Registry::get('runtime.company_id') !== 2) {
        unset($items['items']['gm_search']);
    }
}

/**
 * Свой поиск GOODMi (этап 3, 2026-10-16; копия хуков поиска Maxmobiles в my_changes/func.php): на странице результатов
 * (products.search) вместо поиска CS-Cart по словам – товары, найденные Manticore (app/gm_search/lib.php), в порядке
 * релевантности. Набор (до 1000 товаров) кладёт controllers/frontend/products.pre.php модуля в Registry 'runtime.gm_search';
 * фильтры, цены, страницы и карточки остаются штатными и считаются по этому набору. Действует только для того же запроса q
 * и только пока набор задан (витрина 2; на этапе 3 – браузер с отметкой gm_search_test) – остальные списки товаров на
 * странице и другие витрины не затрагиваются. Свои ключи gm_search – с хуками Maxmobiles (mm_search) не пересекаются.
 */
function fn_gm_delivery_date_gm_search_for($params)
{
    $s = Registry::get('runtime.gm_search');
    if (!$s || !isset($params['q']) || !is_string($params['q']) || trim($params['q']) !== $s['q']) {
        return null;
    }

    return $s;
}

function fn_gm_delivery_date_get_products_pre(&$params, $items_per_page, $lang_code)
{
    $s = fn_gm_delivery_date_gm_search_for($params);
    if (!$s) {
        return;
    }
    // Ничего не найдено – пустой список, а не «все товары»
    $params['pid'] = $s['ids'] ? $s['ids'] : [0];
    unset($params['q'], $params['pname'], $params['pshort'], $params['pfull'], $params['pkeywords'], $params['pcode_from_q']);
    $params['gm_search'] = true;
}

function fn_gm_delivery_date_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by, $lang_code, &$having)
{
    if (empty($params['gm_search'])) {
        return;
    }
    $s = Registry::get('runtime.gm_search');
    $sortings['relevance'] = db_quote('FIELD(products.product_id, ?n)', $s && $s['ids'] ? $s['ids'] : [0]);
}

function fn_gm_delivery_date_get_products_post(&$products, &$params, $lang_code)
{
    if (empty($params['gm_search'])) {
        return;
    }
    // Запрос остаётся в поле поиска и в ссылках фильтров (Baymard: не терять введённое)
    $s = Registry::get('runtime.gm_search');
    if ($s) {
        $params['q'] = $s['q'];
        // Статистика: какие товары показаны на странице и в каком порядке (один раз на поиск)
        if (!empty($s['log_id']) && empty($s['shown_saved']) && is_array($products)) {
            Registry::set('runtime.gm_search.shown_saved', true);
            try {
                db_query('UPDATE ?:gm_search_log SET shown = ?s WHERE id = ?i', implode(',', array_map('intval', array_keys($products))), $s['log_id']);
            } catch (\Throwable $e) {
            }
        }
    }
}

/**
 * Статистика своего поиска GOODMi: корзина и заказы покупателей, которые искали недавно (корзина – поиск за 2 часа, заказ – за
 * сутки), – в cscart_gm_search_events с последним поиском. Только витрина 2 и только с меткой gm_sid (её ставит поиск). Любой сбой
 * записи пропускается – корзина и оформление заказа работают как обычно.
 */
function fn_gm_delivery_date_gm_search_last($within)
{
    if ((int) Registry::get('runtime.company_id') !== 2 || !isset($_COOKIE['gm_sid']) || !preg_match('/^[a-f0-9]{16}$/', (string) $_COOKIE['gm_sid'])) {
        return [null, 0];
    }
    $sid = (string) $_COOKIE['gm_sid'];
    $id = (int) db_get_field('SELECT id FROM ?:gm_search_log WHERE sid = ?s AND ts > ?i ORDER BY id DESC LIMIT 1', $sid, TIME - $within);

    return [$sid, $id];
}

function fn_gm_delivery_date_add_to_cart(&$cart, $product_id, $_id, $_data)
{
    try {
        list($sid, $search_id) = fn_gm_delivery_date_gm_search_last(7200);
        if (!$search_id) {
            return;
        }
        $item = isset($cart['products'][$_id]) ? $cart['products'][$_id] : [];
        db_query('INSERT INTO ?:gm_search_events ?e', [
            'ts' => TIME, 'sid' => $sid, 'type' => 'cart', 'search_id' => $search_id, 'product_id' => (int) $product_id,
            'qty' => isset($item['amount']) ? (int) $item['amount'] : 1, 'price' => isset($item['price']) ? (float) $item['price'] : 0, 'order_id' => 0,
        ]);
    } catch (\Throwable $e) {
    }
}

function fn_gm_delivery_date_place_order($order_id, $action, $order_status, $cart, $auth)
{
    try {
        list($sid, $search_id) = fn_gm_delivery_date_gm_search_last(86400);
        if (!$search_id || !$order_id || empty($cart['products'])
            || db_get_field('SELECT 1 FROM ?:gm_search_events WHERE order_id = ?i LIMIT 1', $order_id)) {
            return;
        }
        foreach ($cart['products'] as $item) {
            db_query('INSERT INTO ?:gm_search_events ?e', [
                'ts' => TIME, 'sid' => $sid, 'type' => 'order', 'search_id' => $search_id, 'product_id' => (int) $item['product_id'],
                'qty' => (int) $item['amount'], 'price' => isset($item['price']) ? (float) $item['price'] : 0, 'order_id' => (int) $order_id,
            ]);
        }
    } catch (\Throwable $e) {
    }
}

function fn_gm_delivery_date_products_sorting(&$sorting, $simple_mode)
{
    if (!Registry::get('runtime.gm_search')) {
        return;
    }
    $sorting = ['relevance' => ['description' => 'По релевантности', 'default_order' => 'asc', 'desc' => false]] + $sorting;
}
