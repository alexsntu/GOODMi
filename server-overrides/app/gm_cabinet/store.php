<?php
/**
 * GOODMi: личный кабинет – своя таблица заказов МойСклад (?:gm_ms_orders): запись, чтение, загрузка.
 * Работает внутри CS-Cart (функции db_*, fn_*_storage_data). Обращение к МойСклад – в lib.php.
 *
 * Таблица: по строке на заказ покупателя из МойСклад, ключ – идентификатор заказа, поиск – по номеру телефона.
 * Хранятся заказы всех каналов продаж; что показывать покупателю, решается при чтении (gm_cab_hidden_channels).
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

require_once __DIR__ . '/lib.php';

/** Имя записи в хранилище CS-Cart: с какой даты изменения заказов продолжать загрузку. */
const GM_CAB_CURSOR = 'gm_ms_orders_cursor';

/** Запись заказов. Если заказ в МойСклад изменился, отметка о загруженном составе сбрасывается – состав перечитается. */
function fn_gm_cab_store_upsert(array $rows)
{
    foreach (array_chunk($rows, 200) as $chunk) {
        db_query(
            'INSERT INTO ?:gm_ms_orders ?m ON DUPLICATE KEY UPDATE'
            . ' items_at = IF(updated <> VALUES(updated), NULL, items_at),'
            . ' agent_id = VALUES(agent_id), phone = VALUES(phone), number = VALUES(number), moment = VALUES(moment),'
            . ' total = VALUES(total), state = VALUES(state), channel = VALUES(channel),'
            . ' external_code = VALUES(external_code), updated = VALUES(updated)',
            $chunk
        );
    }

    return count($rows);
}

/**
 * Покупки покупателя в магазинах: всё, кроме заказов сайта и чужих площадок, новые сверху.
 * Старые заказы сайта заведены в МойСклад без канала продаж – их выдаёт числовой внешний код (номер заказа сайта).
 */
function fn_gm_cab_store_get($phone, $limit = 300)
{
    if ($phone === '') {
        return [];
    }

    return db_get_array(
        'SELECT id, number, moment, total, state, channel, items_at FROM ?:gm_ms_orders'
        . " WHERE phone = ?s AND channel NOT IN (?a) AND NOT (channel = '' AND external_code REGEXP '^[0-9]{3,9}$')"
        . ' ORDER BY moment DESC LIMIT ?i',
        $phone, gm_cab_hidden_channels(), (int) $limit
    );
}

/** Карточки покупателя в МойСклад, уже встречавшиеся в таблице. */
function fn_gm_cab_store_agents($phone)
{
    return db_get_fields("SELECT DISTINCT agent_id FROM ?:gm_ms_orders WHERE phone = ?s AND agent_id != ''", $phone);
}

/**
 * Дозагрузка свежих заказов одного покупателя при открытии страницы.
 * Возвращает число обновлённых строк или null, если МойСклад недоступен.
 */
function fn_gm_cab_store_refresh_customer($phone)
{
    $agents = fn_gm_cab_store_agents($phone);
    $cursor = (string) fn_get_storage_data(GM_CAB_CURSOR);
    // Берём с запасом в сутки от места, до которого дошла общая загрузка
    $since = $cursor !== '' ? date('Y-m-d H:i:s', strtotime($cursor) - 86400) : '';
    $rows = gm_cab_ms_customer_rows($phone, $agents, $since);
    if ($rows === null) {
        return null;
    }
    fn_gm_cab_store_upsert($rows);

    return count($rows);
}

/**
 * Общая загрузка: заказы, изменённые после сохранённой отметки, страницами по 100 по возрастанию даты изменения.
 * Отметка сохраняется после каждой страницы, поэтому прерванная загрузка продолжается с того же места.
 * Первый запуск (отметки нет) проходит всю историю.
 *
 * @return array ['pages', 'orders', 'stored', 'cursor', 'done', 'error']
 */
function fn_gm_cab_store_sync($max_seconds, $log = null)
{
    $started = time();
    $cursor = (string) fn_get_storage_data(GM_CAB_CURSOR);
    // Небольшой возврат назад, чтобы не потерять заказы, изменённые в ту же секунду, на которой остановились
    $from = $cursor !== '' ? date('Y-m-d H:i:s', strtotime($cursor) - 300) : '2000-01-01 00:00:00';
    $offset = 0;
    $stat = ['pages' => 0, 'orders' => 0, 'stored' => 0, 'cursor' => $cursor, 'done' => false, 'error' => ''];

    while (time() - $started < $max_seconds) {
        // МойСклад делит ограничение частоты запросов со штатным обменом сайта (остатки, цены), поэтому отказ –
        // обычное дело: ждём и повторяем, до восьми попыток с паузой 20 секунд
        $page = null;
        for ($attempt = 0; $attempt < 8 && $page === null; $attempt++) {
            if ($attempt > 0) {
                sleep(20);
            }
            $page = gm_cab_ms_page($from, $offset);
        }
        if ($page === null) {
            $stat['error'] = 'МойСклад не ответил (отметка ' . $from . ', смещение ' . $offset . ')';
            break;
        }
        $stat['pages']++;
        $stat['orders'] += $page['count'];
        $stat['stored'] += fn_gm_cab_store_upsert($page['rows']);

        if ($page['last'] !== '' && $page['last'] > $from) {
            $from = $page['last'];
            $offset = 0;
            fn_set_storage_data(GM_CAB_CURSOR, $from);
            $stat['cursor'] = $from;
        } else {
            // Вся страница изменена в одну секунду – идём дальше смещением
            $offset += 100;
        }
        if ($log && $stat['pages'] % 25 === 0) {
            call_user_func($log, $stat);
        }
        if ($page['count'] < 100) {
            $stat['done'] = true;
            break;
        }
        usleep(150000);
    }

    return $stat;
}

/** Номер телефона покупателя из его профиля (не из запроса). Пустая строка, если номера нет. */
function fn_gm_cab_user_phone($user_id)
{
    $user = db_get_row('SELECT phone, clear_phone FROM ?:users WHERE user_id = ?i', $user_id);
    $phone = gm_cab_phone(!empty($user['clear_phone']) ? $user['clear_phone'] : (isset($user['phone']) ? $user['phone'] : ''));
    if ($phone === '') {
        $profile = db_get_row('SELECT s_phone, b_phone FROM ?:user_profiles WHERE user_id = ?i ORDER BY profile_id LIMIT 1', $user_id);
        $phone = gm_cab_phone(!empty($profile['s_phone']) ? $profile['s_phone'] : (isset($profile['b_phone']) ? $profile['b_phone'] : ''));
    }

    return $phone;
}

/**
 * Заказы сайта в виде для страницы: привязанные к учётной записи и оформленные без входа с тем же номером телефона.
 * Ссылка на подробности – только у заказов самой учётной записи (чужой заказ магазин не откроет).
 */
function fn_gm_cab_site_orders($user_id, $phone, $limit = 200)
{
    $tail = $phone !== '' ? substr($phone, 1) : '';
    $rows = db_get_array(
        "SELECT order_id, user_id, timestamp, total, status FROM ?:orders"
        . " WHERE company_id = 2 AND is_parent_order != 'Y' AND status NOT IN ('N', 'T')"
        . " AND (user_id = ?i OR (?s != '' AND RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), '(', ''), ')', ''), '-', ''), ' ', ''), 10) = ?s))"
        . " ORDER BY timestamp DESC LIMIT ?i",
        $user_id, $tail, $tail, (int) $limit
    );
    $statuses = fn_get_simple_statuses(STATUSES_ORDER, true, true);
    $own_ids = [];
    foreach ($rows as $k => $row) {
        $rows[$k]['status_name'] = isset($statuses[$row['status']]) ? $statuses[$row['status']] : '';
        if ((int) $row['user_id'] === (int) $user_id) {
            $own_ids[] = (int) $row['order_id'];
        }
    }
    $orders = gm_cab_site_orders($rows, $own_ids);
    foreach ($orders as $k => $order) {
        if (!empty($order['own'])) {
            $orders[$k]['url'] = fn_url('orders.details?order_id=' . $order['id']);
        }
    }

    return $orders;
}

/** Дозагрузка свежих заказов покупателя не чаще раза в 10 минут; сбой МойСклад страницу не ломает. */
function fn_gm_cab_refresh_cached($phone)
{
    $cache_dir = rtrim(\Tygh\Registry::get('config.dir.cache_misc'), '/') . '/gm_cabinet';
    try {
        gm_cab_cached($cache_dir, 'msr_' . $phone, 600, function () use ($phone) {
            $count = fn_gm_cab_store_refresh_customer($phone);

            return $count === null ? null : ['n' => $count];
        });
    } catch (\Throwable $e) {
    }
}

/** Карточки товаров сайта для вывода в составе заказа: ссылка, название, миниатюра. Только товары витрины GOODMi. */
function fn_gm_cab_products_view(array $product_ids)
{
    $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
    if (!$product_ids) {
        return [];
    }
    $rows = db_get_hash_array(
        'SELECT p.product_id, p.status, d.product FROM ?:products p'
        . ' LEFT JOIN ?:product_descriptions d ON d.product_id = p.product_id AND d.lang_code = ?s'
        . ' WHERE p.product_id IN (?n) AND p.company_id = 2',
        'product_id', CART_LANGUAGE, $product_ids
    );
    $images = [];
    try {
        $pairs = fn_get_image_pairs(array_keys($rows), 'product', 'M', true, true, CART_LANGUAGE);
        foreach ((array) $pairs as $product_id => $list) {
            $pair = is_array($list) ? reset($list) : null;
            if ($pair) {
                $image = fn_image_to_display($pair, 240, 240);
                if (!empty($image['image_path'])) {
                    $images[$product_id] = $image['image_path'];
                }
            }
        }
    } catch (\Throwable $e) {
    }
    $view = [];
    foreach ($rows as $product_id => $row) {
        $view[$product_id] = [
            'name' => (string) $row['product'],
            // Ссылка – только на товар, который открыт покупателям
            'url' => in_array($row['status'], ['A', 'H'], true) ? fn_url('products.view?product_id=' . $product_id) : '',
            'review_url' => in_array($row['status'], ['A', 'H'], true)
                ? fn_url('products.view?product_id=' . $product_id . '&selected_section=product_reviews') . '#product_reviews' : '',
            'img' => isset($images[$product_id]) ? $images[$product_id] : '',
        ];
    }

    return $view;
}

/** Состав заказов сайта: [order_id => строки состава в виде для страницы]. */
function fn_gm_cab_site_items(array $order_ids)
{
    $order_ids = array_values(array_filter(array_map('intval', $order_ids)));
    if (!$order_ids) {
        return [];
    }
    $rows = db_get_array('SELECT order_id, product_id, product_code, price, amount, extra FROM ?:order_details WHERE order_id IN (?n) ORDER BY item_id', $order_ids);
    $view = fn_gm_cab_products_view(array_column($rows, 'product_id'));
    $items = [];
    foreach ($rows as $row) {
        $product_id = (int) $row['product_id'];
        $name = isset($view[$product_id]) ? $view[$product_id]['name'] : '';
        if ($name === '') {
            $extra = @unserialize($row['extra'], ['allowed_classes' => false]);
            $name = is_array($extra) && !empty($extra['product']) ? (string) $extra['product'] : 'Товар ' . $row['product_code'];
        }
        $items[(int) $row['order_id']][] = [
            'name' => $name,
            'url' => isset($view[$product_id]) ? $view[$product_id]['url'] : '',
            'review_url' => isset($view[$product_id]) ? $view[$product_id]['review_url'] : '',
            'img' => isset($view[$product_id]) ? $view[$product_id]['img'] : '',
            'qty' => gm_cab_qty($row['amount']),
            'price' => gm_cab_money($row['price']),
        ];
    }

    return $items;
}

/**
 * Состав покупок из магазинов: [id заказа МойСклад => строки состава]. Состав хранится в ?:gm_ms_order_items;
 * чего ещё нет, дочитывается из МойСклад (не больше $max_fetch заказов за раз) и сохраняется.
 * Товар сайта находится по внешнему коду через карту обмена commerceml, а если там нет – по коду товара.
 *
 * @param array $orders [id заказа => отметка items_at ('' – состав ещё не загружался)]
 */
function fn_gm_cab_store_items(array $orders, $max_fetch = 12)
{
    if (!$orders) {
        return [];
    }
    $fetched = 0;
    foreach ($orders as $order_id => $items_at) {
        if ($items_at !== '' || $fetched >= $max_fetch) {
            continue;
        }
        $fetched++;
        $rows = gm_cab_ms_positions($order_id);
        if ($rows === null) {
            continue;
        }
        db_query('DELETE FROM ?:gm_ms_order_items WHERE order_id = ?s', $order_id);
        if ($rows) {
            db_query('INSERT INTO ?:gm_ms_order_items ?m', $rows);
        }
        db_query('UPDATE ?:gm_ms_orders SET items_at = NOW() WHERE id = ?s', $order_id);
    }

    $rows = db_get_array('SELECT order_id, ext_code, code, name, qty, price FROM ?:gm_ms_order_items WHERE order_id IN (?a) ORDER BY order_id, pos', array_keys($orders));
    if (!$rows) {
        return [];
    }
    $ext = array_values(array_unique(array_filter(array_column($rows, 'ext_code'), 'strlen')));
    $codes = array_values(array_unique(array_filter(array_column($rows, 'code'), 'strlen')));
    $by_ext = $ext ? db_get_hash_single_array(
        "SELECT entity_id, local_id FROM ?:commerceml_import_entity_map WHERE company_id = 2 AND entity_type = 'product' AND entity_id IN (?a)",
        ['entity_id', 'local_id'], $ext
    ) : [];
    $by_code = $codes ? db_get_hash_single_array(
        'SELECT product_code, product_id FROM ?:products WHERE company_id = 2 AND product_code IN (?a)',
        ['product_code', 'product_id'], $codes
    ) : [];

    $product_of = [];
    foreach ($rows as $k => $row) {
        $product_id = 0;
        if ($row['ext_code'] !== '' && isset($by_ext[$row['ext_code']])) {
            $product_id = (int) $by_ext[$row['ext_code']];
        } elseif ($row['code'] !== '' && isset($by_code[$row['code']])) {
            $product_id = (int) $by_code[$row['code']];
        }
        $product_of[$k] = $product_id;
    }
    $view = fn_gm_cab_products_view($product_of);

    $items = [];
    foreach ($rows as $k => $row) {
        $product = isset($view[$product_of[$k]]) ? $view[$product_of[$k]] : null;
        $items[$row['order_id']][] = [
            'name' => $product && $product['name'] !== '' ? $product['name'] : (string) $row['name'],
            'url' => $product ? $product['url'] : '',
            'review_url' => $product ? $product['review_url'] : '',
            'img' => $product ? $product['img'] : '',
            'qty' => gm_cab_qty($row['qty']),
            'price' => gm_cab_money($row['price']),
        ];
    }

    return $items;
}

/** Меню кабинета (левая колонка) и имя покупателя. $active – ключ открытого раздела. */
function fn_gm_cab_nav($user_id, $active)
{
    $user = db_get_row('SELECT firstname, lastname, email FROM ?:users WHERE user_id = ?i', $user_id);
    $name = trim((isset($user['firstname']) ? $user['firstname'] : '') . ' ' . (isset($user['lastname']) ? $user['lastname'] : ''));
    $items = [
        ['key' => 'orders', 'title' => 'Мои заказы', 'url' => fn_url('gm_cabinet.orders')],
        ['key' => 'bonuses', 'title' => 'Мои бонусы', 'url' => fn_url('gm_cabinet.bonuses')],
        ['key' => 'wishlist', 'title' => 'Избранное', 'url' => fn_url('wishlist.view')],
        ['key' => 'compare', 'title' => 'Сравнение', 'url' => fn_url('product_features.compare')],
        ['key' => 'profile', 'title' => 'Данные профиля', 'url' => fn_url('profiles.update')],
    ];
    foreach ($items as $k => $item) {
        $items[$k]['active'] = $item['key'] === $active;
    }

    return [
        'name' => $name !== '' ? $name : (isset($user['email']) ? (string) $user['email'] : ''),
        'initial' => $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '',
        'items' => $items,
        'profile_url' => fn_url('profiles.update'),
        'logout_url' => fn_url('auth.logout?redirect_url=' . urlencode(fn_url(''))),
    ];
}
