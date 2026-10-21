<?php
/**
 * GOODMi: личный кабинет – своя таблица заказов МойСклад (?:gm_ms_orders): запись, чтение, загрузка.
 * Работает внутри CS-Cart (функции db_*, fn_*_storage_data). Обращение к МойСклад – в lib.php.
 *
 * Таблица: по строке на документ МойСклад, ключ – идентификатор документа, поиск – по номеру телефона. Вид документа –
 * поле doc (см. gm_cab_ms_docs в lib.php): O – заказ покупателя, D – отгрузка, R – розничная продажа, X и Y – возвраты.
 * Покупкой считаются отгрузка и розничная продажа (решение 2026-10-13): заказ покупателя нужен только пока он не
 * завершён или отменён, выданный заказ сам по себе покупкой не считается – иначе покупка задваивалась бы с продажей.
 * Продажи на обезличенного «Розничного покупателя» (без телефона) в таблицу не попадают.
 * Хранятся документы всех каналов продаж; что показывать покупателю, решается при чтении (gm_cab_hidden_channels).
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

require_once __DIR__ . '/lib.php';

/** Имя записи в хранилище CS-Cart: с какой даты изменения заказов продолжать загрузку (для остальных документов – с хвостом _D, _R, _X, _Y). */
const GM_CAB_CURSOR = 'gm_ms_orders_cursor';
/** С какой даты начинать первую загрузку отгрузок, продаж и возвратов (более раннюю историю догружаем позже). */
const GM_CAB_SALES_FROM = '2024-01-01 00:00:00';

/** Запись заказов. Если заказ в МойСклад изменился, отметка о загруженном составе сбрасывается – состав перечитается. */
function fn_gm_cab_store_upsert(array $rows)
{
    foreach (array_chunk($rows, 200) as $chunk) {
        db_query(
            'INSERT INTO ?:gm_ms_orders ?m ON DUPLICATE KEY UPDATE'
            . ' items_at = IF(updated <> VALUES(updated), NULL, items_at),'
            . ' doc = VALUES(doc), ref_id = VALUES(ref_id), store = VALUES(store),'
            . ' agent_id = VALUES(agent_id), phone = VALUES(phone), number = VALUES(number), moment = VALUES(moment),'
            . ' total = VALUES(total), state = VALUES(state), channel = VALUES(channel),'
            . ' external_code = VALUES(external_code), updated = VALUES(updated)',
            $chunk
        );
    }

    return count($rows);
}

/**
 * Состоявшиеся покупки покупателя в магазинах: отгрузки и розничные продажи по его номеру, новые сверху.
 * Не входят: продажи чужих площадок и заказов сайта (они показаны как заказы сайта), а также полностью
 * возвращённые. returned – сумма возвратов по продаже (частичный возврат покупку не отменяет).
 * Заказ сайта, выданный в магазине, часто пробивают розничной продажей без привязки к заказу. Такую продажу
 * узнаём по совпадению: тот же номер, та же сумма и не больше трёх дней от выданного заказа сайта – и не показываем,
 * иначе покупка была бы видна дважды (заказ сайта и покупка в магазине).
 */
function fn_gm_cab_store_sales($phone, $limit = 300)
{
    if ($phone === '') {
        return [];
    }
    $hidden = gm_cab_hidden_channels();
    $rows = db_get_array(
        'SELECT s.id, s.doc, s.number, s.moment, s.total, s.state, s.channel, s.store, s.items_at,'
        . ' o.channel AS order_channel, o.external_code AS order_code, o.id AS order_id,'
        . " (SELECT IFNULL(SUM(x.total), 0) FROM ?:gm_ms_orders x WHERE x.ref_id = s.id AND x.doc IN ('X', 'Y')) AS returned"
        . " FROM ?:gm_ms_orders s LEFT JOIN ?:gm_ms_orders o ON s.ref_id != '' AND o.id = s.ref_id AND o.doc = 'O'"
        . " WHERE s.phone = ?s AND s.doc IN ('D', 'R') AND s.channel NOT IN (?a)"
        . ' ORDER BY s.moment DESC LIMIT ?i',
        $phone, $hidden, (int) $limit
    );
    // Выданные заказы сайта и чужих площадок этого номера – для сверки с непривязанными продажами
    $site = db_get_array(
        "SELECT moment, total, state FROM ?:gm_ms_orders WHERE phone = ?s AND doc = 'O'"
        . " AND (channel IN (?a) OR (channel = '' AND external_code REGEXP '^[0-9]{3,9}$'))",
        $phone, $hidden
    );
    $site = array_values(array_filter($site, function ($order) {
        return gm_cab_status_kind($order['state']) === 'received' && (float) $order['total'] > 0;
    }));
    $sales = [];
    foreach ($rows as $row) {
        // Продажа по заказу сайта или чужой площадки: покупатель видит её как заказ сайта
        if ($row['order_id'] !== null && (in_array(mb_strtolower((string) $row['order_channel']), $hidden, true)
            || ((string) $row['order_channel'] === '' && preg_match('/^[0-9]{3,9}$/', (string) $row['order_code'])))) {
            continue;
        }
        if ((float) $row['total'] > 0 && (float) $row['returned'] >= (float) $row['total']) {
            continue;
        }
        // Непривязанная продажа, совпавшая с выданным заказом сайта (каждый заказ «гасит» одну продажу)
        if ($row['order_id'] === null) {
            foreach ($site as $k => $order) {
                if (abs((float) $order['total'] - (float) $row['total']) < 0.01
                    && abs(strtotime((string) $order['moment']) - strtotime((string) $row['moment'])) <= 3 * 86400) {
                    unset($site[$k]);
                    continue 2;
                }
            }
        }
        $sales[] = $row;
    }

    return $sales;
}

/**
 * Покупки и заказы покупателя в магазинах для страницы: состоявшиеся покупки (fn_gm_cab_store_sales) и заказы
 * покупателя, которые ещё в работе или отменены. Выданный заказ не показывается: его место занимает продажа.
 * Заказы сайта и чужих площадок не показываются; старые заказы сайта заведены в МойСклад без канала продаж –
 * их выдаёт числовой внешний код (номер заказа сайта).
 */
function fn_gm_cab_store_get($phone, $limit = 300)
{
    if ($phone === '') {
        return [];
    }
    $orders = db_get_array(
        "SELECT id, doc, number, moment, total, state, channel, items_at FROM ?:gm_ms_orders"
        . " WHERE phone = ?s AND doc = 'O' AND channel NOT IN (?a) AND NOT (channel = '' AND external_code REGEXP '^[0-9]{3,9}$')"
        . ' ORDER BY moment DESC LIMIT ?i',
        $phone, gm_cab_hidden_channels(), (int) $limit
    );
    $orders = array_values(array_filter($orders, function ($row) {
        return gm_cab_status_kind($row['state']) !== 'received';
    }));
    $rows = array_merge(fn_gm_cab_store_sales($phone, $limit), $orders);
    usort($rows, function ($a, $b) {
        return strcmp((string) $b['moment'], (string) $a['moment']);
    });

    return array_slice($rows, 0, (int) $limit);
}

/** Карточки покупателя в МойСклад, уже встречавшиеся в таблице. */
function fn_gm_cab_store_agents($phone)
{
    return db_get_fields("SELECT DISTINCT agent_id FROM ?:gm_ms_orders WHERE phone = ?s AND agent_id != ''", $phone);
}

/**
 * Дозагрузка свежих документов одного покупателя при открытии страницы: заказы, отгрузки и розничные продажи.
 * Продажи покупателя, у которого до этого в таблице были только заказы, один раз берутся целиком – иначе после
 * перехода на учёт по продажам его выданные заказы исчезли бы, а продажи ещё не появились. Отметка о такой
 * загрузке – файл в кэше кабинета. Возвращает число обновлённых строк или null, если МойСклад недоступен.
 */
function fn_gm_cab_store_refresh_customer($phone)
{
    $agents = fn_gm_cab_store_agents($phone);
    $cursor = (string) fn_get_storage_data(GM_CAB_CURSOR);
    // Берём с запасом в сутки от места, до которого дошла общая загрузка
    $since = $cursor !== '' ? date('Y-m-d H:i:s', strtotime($cursor) - 86400) : '';
    $dir = rtrim(\Tygh\Registry::get('config.dir.cache_misc'), '/') . '/gm_cabinet';
    $mark = $dir . '/' . md5('sales_full_' . $phone) . '.json';
    $full = is_file($mark) ? [] : ['D', 'R'];
    $rows = gm_cab_ms_customer_rows($phone, $agents, $since, ['O', 'D', 'R'], $full);
    if ($rows === null) {
        return null;
    }
    fn_gm_cab_store_upsert($rows);
    if ($full) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($mark, json_encode(['t' => time()]), LOCK_EX);
    }

    return count($rows);
}

/**
 * Общая загрузка документов вида $doc, изменённых после сохранённой отметки, страницами по 100 по возрастанию даты
 * изменения. Отметка у каждого вида своя и сохраняется после каждой страницы, поэтому прерванная загрузка
 * продолжается с того же места. Первый запуск по заказам проходит всю историю, по продажам и возвратам – начиная
 * с GM_CAB_SALES_FROM.
 * $history = true – догрузка старой истории продаж и возвратов (изменённых до GM_CAB_SALES_FROM): идёт с начала
 * времён по своей отдельной отметке (…_H) и заканчивается, дойдя до GM_CAB_SALES_FROM; основную отметку не трогает.
 *
 * @return array ['pages', 'orders', 'stored', 'cursor', 'done', 'error']
 */
function fn_gm_cab_store_sync($max_seconds, $log = null, $doc = 'O', $history = false)
{
    $started = time();
    $key = GM_CAB_CURSOR . ($doc === 'O' ? '' : '_' . $doc) . ($history ? '_H' : '');
    $cursor = (string) fn_get_storage_data($key);
    // Небольшой возврат назад, чтобы не потерять документы, изменённые в ту же секунду, на которой остановились
    $from = $cursor !== '' ? date('Y-m-d H:i:s', strtotime($cursor) - 300)
        : ($doc === 'O' || $history ? '2000-01-01 00:00:00' : GM_CAB_SALES_FROM);
    $offset = 0;
    $stat = ['pages' => 0, 'orders' => 0, 'stored' => 0, 'cursor' => $cursor, 'done' => false, 'error' => ''];
    if ($history && ($doc === 'O' || $from >= GM_CAB_SALES_FROM)) {
        // У заказов старой истории нет (они загружены целиком); у остальных – уже дошли до общей загрузки
        $stat['done'] = true;

        return $stat;
    }

    while (time() - $started < $max_seconds) {
        // МойСклад делит ограничение частоты запросов со штатным обменом сайта (остатки, цены), поэтому отказ –
        // обычное дело: ждём и повторяем, до восьми попыток с паузой 20 секунд
        $page = null;
        for ($attempt = 0; $attempt < 8 && $page === null; $attempt++) {
            if ($attempt > 0) {
                sleep(20);
            }
            $page = gm_cab_ms_page($from, $offset, 100, $doc);
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
            fn_set_storage_data($key, $from);
            $stat['cursor'] = $from;
        } else {
            // Вся страница изменена в одну секунду – идём дальше смещением
            $offset += 100;
        }
        if ($log && $stat['pages'] % 25 === 0) {
            call_user_func($log, $stat);
        }
        if ($page['count'] < 100 || ($history && $from >= GM_CAB_SALES_FROM)) {
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
    // Берём номер из самого профиля: поле clear_phone магазин заполняет один раз и при смене номера не обновляет
    $user = db_get_row('SELECT phone FROM ?:users WHERE user_id = ?i', $user_id);
    $phone = gm_cab_phone(isset($user['phone']) ? $user['phone'] : '');
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
    // Заказы, найденные по номеру (оформленные без входа), тоже открываются – если номер подтверждён (см. fn_gm_cab_order_by_phone)
    $by_phone = $phone !== '' && fn_gm_cab_phone_verified($user_id, $phone);
    foreach ($orders as $k => $order) {
        if (!empty($order['own']) || $by_phone) {
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
        gm_cab_cached($cache_dir, 'msr2_' . $phone, 600, function () use ($phone) {
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
    $doc_of = db_get_hash_single_array('SELECT id, doc FROM ?:gm_ms_orders WHERE id IN (?a)', ['id', 'doc'], array_keys($orders));
    foreach ($orders as $order_id => $items_at) {
        if ($items_at !== '' || $fetched >= $max_fetch) {
            continue;
        }
        $fetched++;
        $rows = gm_cab_ms_positions($order_id, isset($doc_of[$order_id]) ? $doc_of[$order_id] : 'O');
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
        ['key' => 'purchased', 'title' => 'Купленные товары', 'url' => fn_url('gm_cabinet.products')],
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

/* ---------------------------------------------------------------------------------------------------------------
 * Подтверждение номера телефона кодом из СМС (таблица ?:gm_phone_verify, по строке на покупателя).
 * Пока номер из профиля не подтверждён, кабинет не показывает данные, найденные по номеру: покупки в магазинах,
 * заказы сайта, оформленные без входа, и бонусы. При смене номера в профиле подтверждение перестаёт действовать.
 * Ограничения: код из 4 цифр живёт 5 минут, 5 попыток ввода; повторная отправка – не раньше чем через минуту,
 * не больше 3 СМС в час на покупателя и на номер, общий дневной потолок – GM_CAB_SMS_DAY_LIMIT.
 * ------------------------------------------------------------------------------------------------------------- */

const GM_CAB_CODE_TTL = 300;
const GM_CAB_CODE_ATTEMPTS = 5;
const GM_CAB_SMS_PAUSE = 60;
const GM_CAB_SMS_PER_HOUR = 3;
const GM_CAB_SMS_DAY_LIMIT = 300;
// Потолок СМС с одного адреса (решение 2026-10-15): защита общего суточного лимита от выбирания скриптом
const GM_CAB_SMS_IP_HOUR = 6;
const GM_CAB_SMS_IP_DAY = 15;
// Блокировка номера неверными кодами временная (решение 2026-10-15)
const GM_CAB_BLOCK_TTL = 3600;

/** Подтверждён ли этот номер этим покупателем. */
function fn_gm_cab_phone_verified($user_id, $phone)
{
    if ($phone === '') {
        return false;
    }

    return (bool) db_get_field('SELECT 1 FROM ?:gm_phone_verify WHERE user_id = ?i AND phone = ?s AND verified_at > 0', $user_id, $phone);
}

/** Состояние подтверждения для шаблона: подтверждён ли номер, ждём ли ввода кода, через сколько секунд можно отправить ещё. */
function fn_gm_cab_verify_state($user_id, $phone)
{
    $row = db_get_row('SELECT * FROM ?:gm_phone_verify WHERE user_id = ?i', $user_id);
    $now = TIME;
    $active = $row && $row['code_hash'] !== '' && (int) $row['code_expires'] > $now && (int) $row['attempts'] < GM_CAB_CODE_ATTEMPTS;
    $pending = $active && $row['code_phone'] === $phone;

    return [
        'verified' => $row && $row['phone'] === $phone && (int) $row['verified_at'] > 0,
        'pending' => $pending,
        'sms' => gm_cab_verify_sms_enabled(),
        // Код отправлен на другой номер – идёт смена номера (страница gm_cabinet.phone)
        'change_phone' => $active && $row['code_phone'] !== $phone ? $row['code_phone'] : '',
        'change_mask' => $active && $row['code_phone'] !== $phone ? gm_cab_phone_mask($row['code_phone']) : '',
        'wait' => $row ? max(0, (int) $row['last_sent'] + GM_CAB_SMS_PAUSE - $now) : 0,
        'mask' => gm_cab_phone_mask($phone),
    ];
}

/** Короткий отпечаток адреса посетителя для журнала и лимитов: сам адрес в журнале не хранится. */
function fn_gm_cab_ip_key()
{
    $ip = fn_get_ip();

    return 'ip:' . substr(md5((string) (isset($ip['host']) ? $ip['host'] : '')), 0, 12);
}

/** Превышен ли потолок СМС с этого адреса (за час и за сутки). */
function fn_gm_cab_ip_limited()
{
    $key = fn_gm_cab_ip_key();
    $hour = (int) db_get_field("SELECT COUNT(*) FROM ?:gm_auth_log WHERE event IN ('sms_verify', 'sms_recover') AND info = ?s AND ts > ?i", $key, TIME - 3600);
    if ($hour >= GM_CAB_SMS_IP_HOUR) {
        return true;
    }

    return (int) db_get_field("SELECT COUNT(*) FROM ?:gm_auth_log WHERE event IN ('sms_verify', 'sms_recover') AND info = ?s AND ts > ?i", $key, TIME - 86400) >= GM_CAB_SMS_IP_DAY;
}

/** Выполнить отправку кода под общей блокировкой: проверки лимитов и запись счётчиков идут по одной, параллельные запросы их не обходят. */
function fn_gm_cab_with_send_lock(callable $fn)
{
    $name = 'gm_cab_sms_send';
    if (!(int) db_get_field('SELECT GET_LOCK(?s, 5)', $name)) {
        return ['ok' => false, 'message' => 'Сервис занят, попробуйте ещё раз через несколько секунд.'];
    }
    try {
        return $fn();
    } finally {
        db_get_field('SELECT RELEASE_LOCK(?s)', $name);
    }
}

/** Отправить код на номер из профиля. Возвращает ['ok' => bool, 'message' => текст покупателю]. */
function fn_gm_cab_verify_send($user_id, $phone)
{
    return fn_gm_cab_with_send_lock(function () use ($user_id, $phone) {
        return fn_gm_cab_verify_send_locked($user_id, $phone);
    });
}

function fn_gm_cab_verify_send_locked($user_id, $phone)
{
    if ($phone === '') {
        return ['ok' => false, 'message' => 'Сначала укажите номер телефона в профиле.'];
    }
    if (!gm_cab_verify_sms_enabled()) {
        return ['ok' => false, 'message' => 'Подтвердить номер сейчас может оператор интернет-магазина: позвоните по номеру 8 (800) 250-17-00 с того телефона, который нужно подтвердить.'];
    }
    if (fn_gm_cab_phone_blocked($phone)) {
        return ['ok' => false, 'message' => GM_CAB_BLOCKED_TEXT];
    }
    if (fn_gm_cab_ip_limited()) {
        return fn_gm_cab_limit_refused($phone, 0);
    }
    $now = TIME;
    $row = db_get_row('SELECT * FROM ?:gm_phone_verify WHERE user_id = ?i', $user_id);
    if ($row && (int) $row['last_sent'] + GM_CAB_SMS_PAUSE > $now) {
        return ['ok' => false, 'message' => 'Код уже отправлен. Повторно можно запросить через минуту.'];
    }
    $window = $row && (int) $row['sent_window'] > $now - 3600 ? (int) $row['sent_window'] : $now;
    $count = $row && $window === (int) $row['sent_window'] ? (int) $row['sent_count'] : 0;
    $by_phone = (int) db_get_field(
        'SELECT SUM(sent_count) FROM ?:gm_phone_verify WHERE code_phone = ?s AND sent_window > ?i AND user_id != ?i',
        $phone, $now - 3600, $user_id
    );
    if ($count >= GM_CAB_SMS_PER_HOUR || $count + $by_phone >= GM_CAB_SMS_PER_HOUR) {
        return fn_gm_cab_limit_refused($phone, $user_id);
    }
    $day_key = 'gm_sms_day_' . date('Ymd', $now);
    $day = (int) fn_get_storage_data($day_key);
    if ($day >= GM_CAB_SMS_DAY_LIMIT) {
        return ['ok' => false, 'message' => 'Не удалось отправить код. Попробуйте позже или позвоните нам: 8 (800) 250-17-00.'];
    }

    $code = (string) random_int(1000, 9999);
    $sent = gm_cab_sms_send($phone, 'Код подтверждения GOODMi: ' . $code);
    if (!$sent['ok']) {
        fn_log_event('general', 'runtime', ['message' => 'GOODMi кабинет: СМС не отправлено – ' . $sent['error']]);
        fn_gm_cab_log('sms_error', $phone, $user_id, $sent['error']);

        return ['ok' => false, 'message' => 'Не удалось отправить код. Попробуйте позже или позвоните нам: 8 (800) 250-17-00.'];
    }
    fn_set_storage_data($day_key, $day + 1);
    fn_gm_cab_log('sms_verify', $phone, $user_id, fn_gm_cab_ip_key());
    db_replace_into('gm_phone_verify', [
        'user_id' => (int) $user_id,
        'phone' => $row ? $row['phone'] : '',
        'verified_at' => $row ? (int) $row['verified_at'] : 0,
        'verified_by' => $row ? (int) $row['verified_by'] : 0,
        'note' => $row ? (string) $row['note'] : '',
        'must_verify' => $row ? (int) $row['must_verify'] : 0,
        'code_phone' => $phone,
        'code_hash' => gm_cab_code_hash($code, $user_id, $phone),
        'code_expires' => $now + GM_CAB_CODE_TTL,
        'attempts' => 0,
        'sent_count' => $count + 1,
        'sent_window' => $window,
        'last_sent' => $now,
    ]);

    return ['ok' => true, 'message' => 'Код отправлен на ' . gm_cab_phone_mask($phone) . '.'];
}

/** Проверить введённый код. Возвращает ['ok' => bool, 'message' => текст покупателю]. */
function fn_gm_cab_verify_check($user_id, $phone, $code)
{
    $code = preg_replace('/\D+/', '', (string) $code);
    $row = db_get_row('SELECT * FROM ?:gm_phone_verify WHERE user_id = ?i', $user_id);
    if (!$row || $row['code_hash'] === '' || $row['code_phone'] !== $phone || (int) $row['code_expires'] <= TIME) {
        return ['ok' => false, 'message' => 'Срок действия кода истёк. Запросите новый.'];
    }
    if ((int) $row['attempts'] >= GM_CAB_CODE_ATTEMPTS) {
        return ['ok' => false, 'message' => 'Слишком много попыток. Запросите новый код.'];
    }
    if (strlen($code) !== 4 || !hash_equals($row['code_hash'], gm_cab_code_hash($code, $user_id, $phone))) {
        db_query('UPDATE ?:gm_phone_verify SET attempts = attempts + 1 WHERE user_id = ?i', $user_id);
        fn_gm_cab_log('code_bad', $phone, $user_id);
        if (fn_gm_cab_strike($phone, 'неверные коды', $user_id)) {
            return ['ok' => false, 'message' => GM_CAB_BLOCKED_TEXT];
        }

        return ['ok' => false, 'message' => 'Неверный код. Проверьте СМС и попробуйте ещё раз.'];
    }
    db_query(
        "UPDATE ?:gm_phone_verify SET phone = ?s, verified_at = ?i, code_hash = '', code_expires = 0, attempts = 0 WHERE user_id = ?i",
        $phone, TIME, $user_id
    );

    fn_gm_cab_verify_claim($user_id, $phone);
    fn_gm_cab_strike_reset($phone);
    fn_gm_cab_log('code_ok', $phone, $user_id);

    return ['ok' => true, 'message' => 'Номер подтверждён.'];
}

/**
 * Ручное подтверждение номера сотрудником (страница админки gm_cabinet.phones): без СМС, по номеру из профиля покупателя.
 * В verified_by записывается, кто подтвердил (0 – покупатель сам, кодом из СМС).
 */
function fn_gm_cab_verify_manual($user_id, $admin_id)
{
    $phone = fn_gm_cab_user_phone($user_id);
    if ($phone === '') {
        return ['ok' => false, 'message' => 'У покупателя в профиле нет номера телефона.'];
    }
    db_query(
        'INSERT INTO ?:gm_phone_verify (user_id, phone, verified_at, verified_by) VALUES (?i, ?s, ?i, ?i)'
        . " ON DUPLICATE KEY UPDATE phone = VALUES(phone), verified_at = VALUES(verified_at), verified_by = VALUES(verified_by), note = '', code_hash = '', code_expires = 0, attempts = 0",
        $user_id, $phone, TIME, $admin_id
    );
    fn_gm_cab_verify_claim($user_id, $phone);

    return ['ok' => true, 'message' => 'Номер ' . gm_cab_phone_mask($phone) . ' подтверждён.'];
}

/**
 * Один номер – один владелец: когда покупатель подтвердил номер (кодом из СМС или через оператора), подтверждение
 * этого номера у остальных записей снимается. Иначе вход и восстановление пароля по номеру были бы неоднозначны.
 */
function fn_gm_cab_verify_claim($user_id, $phone)
{
    if ((string) $phone === '') {
        return;
    }
    $others = db_get_fields('SELECT user_id FROM ?:gm_phone_verify WHERE phone = ?s AND verified_at > 0 AND user_id != ?i', $phone, $user_id);
    if (!$others) {
        return;
    }
    db_query("UPDATE ?:gm_phone_verify SET phone = '', verified_at = 0, verified_by = 0 WHERE user_id IN (?n)", $others);
    foreach ($others as $other) {
        fn_gm_cab_log('unverify', $phone, (int) $other, 'номер подтвердила запись ' . (int) $user_id);
    }
}

/** Снять подтверждение (покупателю снова понадобится код из СМС или ручное подтверждение). */
function fn_gm_cab_verify_reset($user_id)
{
    db_query("UPDATE ?:gm_phone_verify SET phone = '', verified_at = 0, verified_by = 0 WHERE user_id = ?i", $user_id);

    return ['ok' => true, 'message' => 'Подтверждение снято.'];
}

/** Поиск покупателей GOODMi по номеру телефона (от 4 цифр, по окончанию) или части e-mail – для страницы админки. */
function fn_gm_cab_verify_search($query, $limit = 30)
{
    $query = trim((string) $query);
    $digits = preg_replace('/\D+/', '', $query);
    if (strpos($query, '@') === false && strlen($digits) >= 4) {
        $tail = '%' . substr($digits, -10);
        $condition = db_quote(
            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(u.phone, '+', ''), '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?l", $tail
        );
    } elseif (mb_strlen($query) >= 3) {
        $condition = db_quote('u.email LIKE ?l', '%' . $query . '%');
    } else {
        return [];
    }
    $rows = db_get_array(
        'SELECT u.user_id, u.firstname, u.lastname, u.email, u.phone, v.phone AS v_phone, v.verified_at, v.verified_by, v.note,'
        . " TRIM(CONCAT(a.firstname, ' ', a.lastname)) AS admin_name"
        . ' FROM ?:users u LEFT JOIN ?:gm_phone_verify v ON v.user_id = u.user_id LEFT JOIN ?:users a ON a.user_id = v.verified_by'
        . " WHERE u.company_id = 2 AND u.user_type = 'C' AND $condition ORDER BY u.user_id DESC LIMIT ?i",
        (int) $limit
    );
    foreach ($rows as $k => $row) {
        $phone = gm_cab_phone($row['phone']);
        $verified = $phone !== '' && $row['v_phone'] === $phone && (int) $row['verified_at'] > 0;
        $rows[$k]['name'] = trim($row['firstname'] . ' ' . $row['lastname']);
        $rows[$k]['has_phone'] = $phone !== '';
        $rows[$k]['verified'] = $verified;
        $rows[$k]['verified_when'] = $verified ? date('d.m.Y H:i', (int) $row['verified_at']) : '';
        $rows[$k]['verified_how'] = '';
        if ($verified) {
            if ((string) $row['note'] !== '') {
                $rows[$k]['verified_how'] = $row['note'];
            } elseif ((int) $row['verified_by'] > 0) {
                $rows[$k]['verified_how'] = 'вручную' . ($row['admin_name'] !== null && $row['admin_name'] !== '' ? ', ' . $row['admin_name'] : '');
            } else {
                $rows[$k]['verified_how'] = 'код из СМС';
            }
        }
    }

    return $rows;
}

/**
 * Отметить, что покупатель зарегистрировался по номеру телефона (форма регистрации сайта, с 2026-10-13): пока он не
 * подтвердит номер, оформить заказ нельзя (fn_gm_cab_verify_required, controllers/frontend/checkout.pre.php модуля
 * gm_delivery_date). На покупателей, зарегистрированных раньше, это не распространяется.
 */
function fn_gm_cab_must_verify_set($user_id)
{
    db_query('INSERT INTO ?:gm_phone_verify (user_id, must_verify) VALUES (?i, 1) ON DUPLICATE KEY UPDATE must_verify = 1', $user_id);
}

/** Должен ли покупатель подтвердить номер, прежде чем оформлять заказ: отмечен при регистрации и номер ещё не подтверждён. */
function fn_gm_cab_verify_required($user_id)
{
    if (!gm_cab_verify_enabled($user_id)) {
        return false;
    }
    $row = db_get_row('SELECT phone, verified_at, must_verify FROM ?:gm_phone_verify WHERE user_id = ?i', $user_id);
    if (!$row || !(int) $row['must_verify']) {
        return false;
    }
    $phone = fn_gm_cab_user_phone($user_id);

    return !($phone !== '' && $row['phone'] === $phone && (int) $row['verified_at'] > 0);
}

/** Запрещено ли покупателю править номер в профиле напрямую: подтверждение включено и текущий номер подтверждён. */
function fn_gm_cab_phone_locked($user_id)
{
    return gm_cab_verify_enabled($user_id) && fn_gm_cab_phone_verified($user_id, fn_gm_cab_user_phone($user_id));
}

/** Записать покупателю новый номер после подтверждения (в том виде, в каком номера хранит магазин: +7(978)719-34-85). */
function fn_gm_cab_set_user_phone($user_id, $phone)
{
    $pretty = '+7(' . substr($phone, 1, 3) . ')' . substr($phone, 4, 3) . '-' . substr($phone, 7, 2) . '-' . substr($phone, 9, 2);
    db_query('UPDATE ?:users SET phone = ?s, clear_phone = ?s WHERE user_id = ?i', $pretty, $phone, $user_id);
}

/**
 * Покупал ли покупатель этот товар в магазинах GOODMi (для права оставить отзыв, см. хук в модуле gm_delivery_date).
 * Считаются только отгрузки и розничные продажи по подтверждённому номеру из профиля. Сначала смотрим уже
 * загруженный состав продаж, затем спрашиваем МойСклад (ответ запоминается: «покупал» – на 30 дней, «не покупал» – на 10 минут).
 */
function fn_gm_cab_bought_in_store($user_id, $product_id)
{
    $phone = fn_gm_cab_user_phone($user_id);
    if ($phone === '' || (gm_cab_verify_enabled($user_id) && !fn_gm_cab_phone_verified($user_id, $phone))) {
        return false;
    }
    $agents = fn_gm_cab_store_agents($phone);
    if (!$agents) {
        return false;
    }
    $ext = db_get_fields(
        "SELECT entity_id FROM ?:commerceml_import_entity_map WHERE company_id = 2 AND entity_type = 'product' AND local_id = ?s",
        (string) (int) $product_id
    );
    $code = (string) db_get_field('SELECT product_code FROM ?:products WHERE product_id = ?i', $product_id);
    if (!$ext && $code === '') {
        return false;
    }

    // Покупка – товар в составе отгрузки или розничной продажи (не полностью возвращённой)
    $sale_ids = array_column(fn_gm_cab_store_sales($phone, 2000), 'id');
    if ($sale_ids && db_get_field(
        'SELECT 1 FROM ?:gm_ms_order_items i WHERE i.order_id IN (?a)'
        . " AND ((i.ext_code != '' AND i.ext_code IN (?a)) OR (?s != '' AND i.code = ?s)) LIMIT 1",
        $sale_ids, $ext ?: [''], $code, $code
    )) {
        return true;
    }

    $dir = rtrim(\Tygh\Registry::get('config.dir.cache_misc'), '/') . '/gm_cabinet';
    $key = 'bought_' . (int) $user_id . '_' . (int) $product_id . '_' . $phone;
    $file = $dir . '/' . md5($key) . '.json';
    $old = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    if (is_array($old) && isset($old['t'], $old['d']) && time() - (int) $old['t'] < ($old['d'] === 'Y' ? 30 * 86400 : 600)) {
        return $old['d'] === 'Y';
    }
    $bought = gm_cab_ms_bought($agents, $ext, $code);
    if ($bought === null) {
        return false;
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($file, json_encode(['t' => time(), 'd' => $bought ? 'Y' : 'N']), LOCK_EX);

    return $bought;
}

/**
 * Можно ли покупателю открыть заказ сайта, который не привязан к его учётной записи: заказ оформлен на его
 * подтверждённый номер телефона (обычно – без входа). Используется обработчиком is_order_allowed_post
 * в модуле gm_delivery_date; сам заказ при этом не меняется.
 */
function fn_gm_cab_order_by_phone($user_id, $order_id)
{
    $phone = fn_gm_cab_user_phone($user_id);
    if ($phone === '' || !fn_gm_cab_phone_verified($user_id, $phone)) {
        return false;
    }
    $order_phone = db_get_field('SELECT phone FROM ?:orders WHERE order_id = ?i AND company_id = 2', $order_id);

    return gm_cab_phone($order_phone) === $phone;
}

/* ---------------------------------------------------------------------------------------------------------------
 * Раздел «Купленные товары» (gm_cabinet.products): товары из выданных заказов сайта и покупок в магазинах,
 * по одному на товар, свежие покупки первыми.
 * ------------------------------------------------------------------------------------------------------------- */

/**
 * Догрузить состав покупок в магазинах (отгрузок и розничных продаж), у которых он ещё не загружен: состав хранится
 * по первому показу документа, а для этого раздела нужен весь. МойСклад отдаёт до 100 документов с составом за
 * запрос; за один показ страницы – не больше $max_pages запросов, продолжение – при следующем показе.
 * Возвращает true, если загружено всё.
 */
function fn_gm_cab_store_load_items($phone, $max_pages = 3)
{
    $agents = fn_gm_cab_store_agents($phone);
    if (!$agents) {
        return true;
    }
    $dir = rtrim(\Tygh\Registry::get('config.dir.cache_misc'), '/') . '/gm_cabinet';
    $all_done = true;
    foreach (['R', 'D'] as $doc) {
        $missing = db_get_fields('SELECT id FROM ?:gm_ms_orders WHERE phone = ?s AND doc = ?s AND items_at IS NULL', $phone, $doc);
        if (!$missing) {
            continue;
        }
        $file = $dir . '/' . md5('items_load_' . $doc . '_' . $phone) . '.json';
        $mark = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        // Всё, что МойСклад отдал по контрагентам номера, уже записано; оставшиеся документы не перепроверяем чаще раза в 6 часов
        if (is_array($mark) && !empty($mark['done']) && time() - (int) $mark['t'] < 6 * 3600) {
            continue;
        }
        if ($max_pages <= 0) {
            $all_done = false;
            continue;
        }
        $missing = array_flip($missing);
        $offset = is_array($mark) && empty($mark['done']) && isset($mark['offset']) ? (int) $mark['offset'] : 0;
        $done = false;
        for (; $max_pages > 0; $max_pages--) {
            $data = gm_cab_ms_customer_items($agents, $offset, $doc);
            if ($data === null) {
                break;
            }
            foreach ($data['orders'] as $order_id => $rows) {
                if (!isset($missing[$order_id])) {
                    continue;
                }
                db_query('DELETE FROM ?:gm_ms_order_items WHERE order_id = ?s', $order_id);
                if ($rows) {
                    db_query('INSERT INTO ?:gm_ms_order_items ?m', $rows);
                }
                db_query('UPDATE ?:gm_ms_orders SET items_at = NOW() WHERE id = ?s', $order_id);
            }
            $offset += 100;
            if ($offset >= $data['size'] || !$data['orders']) {
                $done = true;
                break;
            }
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($file, json_encode(['t' => time(), 'done' => $done, 'offset' => $done ? 0 : $offset]), LOCK_EX);
        $all_done = $all_done && $done;
    }

    return $all_done;
}

/**
 * Купленные товары покупателя: выданные заказы сайта (свои и по подтверждённому номеру) и покупки в магазинах –
 * отгрузки и розничные продажи.
 * $phone – номер, которому можно доверять (пустой – только заказы своей учётной записи).
 */
function fn_gm_cab_purchased($user_id, $phone)
{
    $tail = $phone !== '' ? substr($phone, 1) : '';
    $found = [];
    $add = function ($key, $product_id, $name, $ts, $price) use (&$found) {
        if (!isset($found[$key])) {
            $found[$key] = ['product_id' => $product_id, 'name' => $name, 'ts' => 0, 'price' => 0, 'times' => 0];
        }
        $found[$key]['times']++;
        if ($ts >= $found[$key]['ts']) {
            $found[$key]['ts'] = $ts;
            $found[$key]['price'] = $price;
        }
    };

    $rows = db_get_array(
        'SELECT od.product_id, od.product_code, od.price, od.extra, o.timestamp FROM ?:orders o'
        . ' JOIN ?:order_details od ON od.order_id = o.order_id'
        . " WHERE o.company_id = 2 AND o.is_parent_order != 'Y' AND o.status = 'C'"
        . " AND (o.user_id = ?i OR (?s != '' AND RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(o.phone, '+', ''), '(', ''), ')', ''), '-', ''), ' ', ''), 10) = ?s))"
        . ' ORDER BY o.timestamp DESC LIMIT 3000',
        $user_id, $tail, $tail
    );
    foreach ($rows as $row) {
        $extra = @unserialize($row['extra'], ['allowed_classes' => false]);
        $name = is_array($extra) && !empty($extra['product']) ? (string) $extra['product'] : 'Товар ' . $row['product_code'];
        $add('p' . (int) $row['product_id'], (int) $row['product_id'], $name, (int) $row['timestamp'], (float) $row['price']);
    }

    if ($phone !== '') {
        // Товары из отгрузок и розничных продаж (заказ покупателя сам по себе покупкой не считается)
        $sale_ids = array_column(fn_gm_cab_store_sales($phone, 2000), 'id');
        $rows = $sale_ids ? db_get_array(
            'SELECT i.ext_code, i.code, i.name, i.price, o.moment FROM ?:gm_ms_order_items i'
            . ' JOIN ?:gm_ms_orders o ON o.id = i.order_id WHERE i.order_id IN (?a) ORDER BY o.moment DESC LIMIT 5000',
            $sale_ids
        ) : [];
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
        foreach ($rows as $row) {
            $product_id = 0;
            if ($row['ext_code'] !== '' && isset($by_ext[$row['ext_code']])) {
                $product_id = (int) $by_ext[$row['ext_code']];
            } elseif ($row['code'] !== '' && isset($by_code[$row['code']])) {
                $product_id = (int) $by_code[$row['code']];
            }
            $name = (string) $row['name'];
            if ($product_id === 0 && $name === '') {
                continue;
            }
            $add($product_id ? 'p' . $product_id : 'n' . md5($name), $product_id, $name, gm_cab_ts($row['moment'], 'Europe/Moscow'), (float) $row['price']);
        }
    }
    if (!$found) {
        return [];
    }

    $ids = array_values(array_filter(array_column($found, 'product_id')));
    $view = fn_gm_cab_products_view($ids);
    $reviewed = $ids ? array_flip(db_get_fields('SELECT DISTINCT product_id FROM ?:product_reviews WHERE user_id = ?i AND product_id IN (?n)', $user_id, $ids)) : [];
    usort($found, function ($a, $b) {
        return $b['ts'] <=> $a['ts'];
    });
    $list = [];
    foreach ($found as $item) {
        $product = isset($view[$item['product_id']]) ? $view[$item['product_id']] : null;
        $list[] = [
            'name' => $product && $product['name'] !== '' ? $product['name'] : $item['name'],
            'url' => $product ? $product['url'] : '',
            'review_url' => $product && !isset($reviewed[$item['product_id']]) ? $product['review_url'] : '',
            'reviewed' => isset($reviewed[$item['product_id']]),
            'img' => $product ? $product['img'] : '',
            'price' => gm_cab_money($item['price']),
            'when' => gm_cab_date_ru($item['ts']),
            'times' => $item['times'],
        ];
    }

    return $list;
}

/* ---------------------------------------------------------------------------------------------------------------
 * Вход по номеру телефона и восстановление пароля кодом из СМС (таблица ?:gm_phone_auth, по строке на номер).
 * По номеру пускаем только того покупателя, у которого этот номер подтверждён и стоит в профиле: иначе номер мог бы
 * занять любой, кто вписал его в профиль. Пароль – тот же, что и для входа по e-mail. Восстановление по СМС работает
 * только для подтверждённого номера. Ограничения: GM_CAB_LOGIN_FAILS неверных паролей подряд закрывают вход по номеру
 * на GM_CAB_LOGIN_LOCK секунд; коды восстановления – те же правила, что у подтверждения (5 минут, 5 попыток,
 * пауза минута, 3 СМС в час на номер, общий дневной потолок).
 * Подключение: обработчики auth_routines, update_user_pre, update_profile, before_dispatch в модуле gm_delivery_date (func.php),
 * проверка формы регистрации – controllers/frontend/profiles.pre.php, страница gm_cabinet.recover.
 * ------------------------------------------------------------------------------------------------------------- */

const GM_CAB_LOGIN_FAILS = 8;
const GM_CAB_LOGIN_LOCK = 900;
const GM_CAB_PASSWORD_MIN = 6;
// Домен e-mail-заглушки для записей без почты, см. fn_gm_cab_stub_email (решение 2026-10-13)
const GM_CAB_STUB_DOMAIN = '@goodmi.ru';
const GM_CAB_OPERATOR_TEXT = 'Позвоните оператору интернет-магазина по номеру 8 (800) 250-17-00 с того телефона, который нужно подтвердить.';

/**
 * E-mail-заглушка: покупателю, который не указал почту, записывается адрес из его номера – 79780000000@goodmi.ru
 * (CS-Cart без e-mail не создаёт ни запись, ни заказ, а одинаковый адрес у двух покупателей не допускает).
 * Настоящего ящика за ней нет, поэтому по заглушке нельзя войти и восстановить пароль, письма на неё не шлём,
 * в форме профиля она не показывается (js/addons/gm_delivery_date/auth.js – то же правило разбора).
 */
function gm_cab_is_stub_email($email)
{
    $email = mb_strtolower(trim((string) $email));

    // mail@goodmi.ru – первая версия заглушки (несколько часов 2026-10-13)
    return $email === 'mail@goodmi.ru' || (bool) preg_match('/^7\d{10}(-\d+)?' . preg_quote(GM_CAB_STUB_DOMAIN, '/') . '$/', $email);
}

/**
 * Свободная заглушка для номера. Обычно это сам номер; если адрес занят незавершённой регистрацией на тот же номер
 * (её владелец номер так и не подтвердил), добавляется порядковый хвост: 79780000000-2@goodmi.ru.
 */
function fn_gm_cab_stub_email($phone, $user_id = 0)
{
    $phone = (string) $phone;
    if ($phone === '') {
        return '';
    }
    for ($n = 1; $n <= 50; $n++) {
        $email = $phone . ($n > 1 ? '-' . $n : '') . GM_CAB_STUB_DOMAIN;
        if (!db_get_field('SELECT user_id FROM ?:users WHERE email = ?s AND user_id != ?i', $email, (int) $user_id)) {
            return $email;
        }
    }

    return $phone . '-' . substr(md5(uniqid('', true)), 0, 8) . GM_CAB_STUB_DOMAIN;
}

/**
 * Покупатели GOODMi, у которых этот номер подтверждён и сейчас стоит в профиле. Владелец у номера один
 * (fn_gm_cab_verify_claim), список оставлен на случай старых данных: первым идёт подтвердивший позже.
 * $active_only – только включённые записи (для восстановления пароля; вход отключённой записи отклоняет сам CS-Cart).
 */
function fn_gm_cab_phone_owners($phone, $active_only = false)
{
    if ((string) $phone === '') {
        return [];
    }
    $ids = db_get_fields(
        'SELECT v.user_id FROM ?:gm_phone_verify v JOIN ?:users u ON u.user_id = v.user_id'
        . " WHERE v.phone = ?s AND v.verified_at > 0 AND u.company_id = 2 AND u.user_type = 'C' ?p"
        . ' ORDER BY v.verified_at DESC, v.user_id DESC',
        $phone, $active_only ? "AND u.status = 'A'" : ''
    );
    $owners = [];
    foreach ($ids as $id) {
        if (fn_gm_cab_user_phone((int) $id) === $phone) {
            $owners[] = (int) $id;
        }
    }

    return $owners;
}

/**
 * Вход по номеру: кого искать штатной проверке входа. null – введён не номер (пусть ищет по e-mail как обычно);
 * иначе ['user_id' => покупатель или 0, 'locked' => вход по номеру временно закрыт из-за неверных паролей].
 * Пароль сверяется здесь же только ради счётчика неудачных попыток: сам вход выполняет CS-Cart.
 */
function fn_gm_cab_login_by_phone($login, $password)
{
    $phone = gm_cab_phone($login);
    if ($phone === '') {
        return null;
    }
    $owners = fn_gm_cab_phone_owners($phone);
    if (!$owners) {
        return ['user_id' => 0, 'locked' => false];
    }
    $now = TIME;
    $row = db_get_row('SELECT login_fails, login_window FROM ?:gm_phone_auth WHERE phone = ?s', $phone);
    $fails = $row && (int) $row['login_window'] > $now - GM_CAB_LOGIN_LOCK ? (int) $row['login_fails'] : 0;
    if ($fails >= GM_CAB_LOGIN_FAILS) {
        return ['user_id' => 0, 'locked' => true];
    }
    $users = db_get_hash_array('SELECT user_id, password, salt FROM ?:users WHERE user_id IN (?n)', 'user_id', $owners);
    foreach ($owners as $id) {
        if ((string) $password !== '' && isset($users[$id])
            && fn_password_verify((string) $password, (string) $users[$id]['password'], (string) $users[$id]['salt'])) {
            if ($fails > 0) {
                db_query('UPDATE ?:gm_phone_auth SET login_fails = 0 WHERE phone = ?s', $phone);
            }

            return ['user_id' => $id, 'locked' => false];
        }
    }
    db_query(
        'INSERT INTO ?:gm_phone_auth (phone, login_fails, login_window) VALUES (?s, 1, ?i)'
        . ' ON DUPLICATE KEY UPDATE login_fails = IF(login_window > ?i, login_fails + 1, 1), login_window = IF(login_window > ?i, login_window, ?i)',
        $phone, $now, $now - GM_CAB_LOGIN_LOCK, $now - GM_CAB_LOGIN_LOCK, $now
    );
    fn_gm_cab_log('login_bad', $phone, $owners[0]);
    if ($fails + 1 === GM_CAB_LOGIN_FAILS) {
        fn_gm_cab_log('login_lock', $phone, $owners[0]);
    }

    return ['user_id' => $owners[0], 'locked' => false];
}

/** Состояние восстановления пароля для страницы: ждём ли ввода кода, отправленного на этот номер. */
function fn_gm_cab_recover_state($phone)
{
    $row = (string) $phone !== '' ? db_get_row('SELECT * FROM ?:gm_phone_auth WHERE phone = ?s', $phone) : [];
    $pending = $row && $row['code_hash'] !== '' && (int) $row['code_expires'] > TIME && (int) $row['attempts'] < GM_CAB_CODE_ATTEMPTS;

    return [
        'pending' => (bool) $pending,
        'mask' => gm_cab_phone_mask($phone),
        'sms' => gm_cab_verify_sms_enabled(),
    ];
}

/** Отправить код восстановления пароля на подтверждённый номер. Возвращает ['ok' => bool, 'message' => текст покупателю]. */
function fn_gm_cab_recover_send($phone)
{
    return fn_gm_cab_with_send_lock(function () use ($phone) {
        return fn_gm_cab_recover_send_locked($phone);
    });
}

function fn_gm_cab_recover_send_locked($phone)
{
    if ((string) $phone === '') {
        return ['ok' => false, 'message' => 'Введите номер полностью, например +7 978 000-00-00.'];
    }
    if (!gm_cab_verify_sms_enabled()) {
        return ['ok' => false, 'message' => 'Восстановление по СМС сейчас недоступно. ' . GM_CAB_OPERATOR_TEXT];
    }
    if (fn_gm_cab_phone_blocked($phone)) {
        return ['ok' => false, 'message' => GM_CAB_BLOCKED_TEXT];
    }
    if (fn_gm_cab_ip_limited()) {
        return fn_gm_cab_limit_refused($phone, 0);
    }
    $owners = fn_gm_cab_phone_owners($phone, true);
    if (!$owners) {
        return ['ok' => false, 'message' => 'Этот номер не подтверждён ни в одной учётной записи, поэтому восстановить пароль по СМС нельзя.'
            . ' Восстановите пароль по e-mail или позвоните оператору интернет-магазина по номеру 8 (800) 250-17-00 с этого телефона.'];
    }
    $now = TIME;
    $row = db_get_row('SELECT * FROM ?:gm_phone_auth WHERE phone = ?s', $phone);
    if ($row && (int) $row['last_sent'] + GM_CAB_SMS_PAUSE > $now) {
        return ['ok' => false, 'message' => 'Код уже отправлен. Повторно можно запросить через минуту.'];
    }
    $window = $row && (int) $row['sent_window'] > $now - 3600 ? (int) $row['sent_window'] : $now;
    $count = $row && $window === (int) $row['sent_window'] ? (int) $row['sent_count'] : 0;
    // В часовой потолок номера входят и коды подтверждения, отправленные на него из кабинета
    $by_verify = (int) db_get_field('SELECT SUM(sent_count) FROM ?:gm_phone_verify WHERE code_phone = ?s AND sent_window > ?i', $phone, $now - 3600);
    if ($count + $by_verify >= GM_CAB_SMS_PER_HOUR) {
        return fn_gm_cab_limit_refused($phone, $owners[0]);
    }
    $fail = ['ok' => false, 'message' => 'Не удалось отправить код. Попробуйте позже или позвоните оператору интернет-магазина: 8 (800) 250-17-00.'];
    $day_key = 'gm_sms_day_' . date('Ymd', $now);
    $day = (int) fn_get_storage_data($day_key);
    if ($day >= GM_CAB_SMS_DAY_LIMIT) {
        return $fail;
    }

    $user_id = $owners[0];
    $code = (string) random_int(1000, 9999);
    $sent = gm_cab_sms_send($phone, 'Код для восстановления пароля GOODMi: ' . $code);
    if (!$sent['ok']) {
        fn_log_event('general', 'runtime', ['message' => 'GOODMi кабинет: СМС восстановления не отправлено – ' . $sent['error']]);
        fn_gm_cab_log('sms_error', $phone, $user_id, $sent['error']);

        return $fail;
    }
    fn_set_storage_data($day_key, $day + 1);
    fn_gm_cab_log('sms_recover', $phone, $user_id, fn_gm_cab_ip_key());
    // Не REPLACE: в строке номера живут ещё счётчики входа и блокировка
    db_query(
        'INSERT INTO ?:gm_phone_auth (phone, user_id, code_hash, code_expires, attempts, sent_count, sent_window, last_sent)'
        . ' VALUES (?s, ?i, ?s, ?i, 0, ?i, ?i, ?i) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), code_hash = VALUES(code_hash),'
        . ' code_expires = VALUES(code_expires), attempts = 0, sent_count = VALUES(sent_count), sent_window = VALUES(sent_window), last_sent = VALUES(last_sent)',
        $phone, $user_id, gm_cab_code_hash($code, $user_id, 'recover:' . $phone), $now + GM_CAB_CODE_TTL, $count + 1, $window, $now
    );

    return ['ok' => true, 'message' => 'Код отправлен на ' . gm_cab_phone_mask($phone) . '.'];
}

/**
 * Проверить код восстановления и записать новый пароль. Пароли – в исходном виде, как их ввёл покупатель.
 * Возвращает ['ok' => bool, 'message' => текст покупателю, 'user_id' => чей пароль изменён].
 */
function fn_gm_cab_recover_check($phone, $code, $password1, $password2)
{
    $code = preg_replace('/\D+/', '', (string) $code);
    $password1 = (string) $password1;
    $row = (string) $phone !== '' ? db_get_row('SELECT * FROM ?:gm_phone_auth WHERE phone = ?s', $phone) : [];
    if (!$row || $row['code_hash'] === '' || (int) $row['code_expires'] <= TIME) {
        return ['ok' => false, 'message' => 'Срок действия кода истёк. Запросите новый.', 'user_id' => 0];
    }
    if ((int) $row['attempts'] >= GM_CAB_CODE_ATTEMPTS) {
        return ['ok' => false, 'message' => 'Слишком много попыток. Запросите новый код.', 'user_id' => 0];
    }
    if (mb_strlen($password1) < GM_CAB_PASSWORD_MIN) {
        return ['ok' => false, 'message' => 'Пароль должен быть не короче ' . GM_CAB_PASSWORD_MIN . ' символов.', 'user_id' => 0];
    }
    if ($password1 !== (string) $password2) {
        return ['ok' => false, 'message' => 'Пароли не совпадают. Введите новый пароль дважды одинаково.', 'user_id' => 0];
    }
    $user_id = (int) $row['user_id'];
    if (strlen($code) !== 4 || !hash_equals($row['code_hash'], gm_cab_code_hash($code, $user_id, 'recover:' . $phone))) {
        db_query('UPDATE ?:gm_phone_auth SET attempts = attempts + 1 WHERE phone = ?s', $phone);
        fn_gm_cab_log('code_bad', $phone, $user_id);
        if (fn_gm_cab_strike($phone, 'неверные коды', $user_id)) {
            return ['ok' => false, 'message' => GM_CAB_BLOCKED_TEXT, 'user_id' => 0];
        }

        return ['ok' => false, 'message' => 'Неверный код. Проверьте СМС и попробуйте ещё раз.', 'user_id' => 0];
    }
    // Номер за время ожидания кода могли сменить или снять с него подтверждение
    if (!in_array($user_id, fn_gm_cab_phone_owners($phone, true), true)) {
        db_query("UPDATE ?:gm_phone_auth SET code_hash = '', code_expires = 0 WHERE phone = ?s", $phone);

        return ['ok' => false, 'message' => 'Этот номер больше не подтверждён. ' . GM_CAB_OPERATOR_TEXT, 'user_id' => 0];
    }
    db_query(
        "UPDATE ?:users SET password = ?s, salt = '', password_change_timestamp = ?i WHERE user_id = ?i",
        fn_password_hash($password1), TIME, $user_id
    );
    db_query("UPDATE ?:gm_phone_auth SET code_hash = '', code_expires = 0, attempts = 0, login_fails = 0 WHERE phone = ?s", $phone);
    fn_gm_cab_strike_reset($phone);
    fn_gm_cab_log('recover_ok', $phone, $user_id);
    fn_log_event('users', 'update', ['user_id' => $user_id]);

    return ['ok' => true, 'message' => 'Пароль изменён. Теперь вы можете входить по номеру телефона и новому паролю.', 'user_id' => $user_id];
}

/* ---------------------------------------------------------------------------------------------------------------
 * Блокировка номера и журнал (решение 2026-10-13). GM_CAB_STRIKES неверных кодов подряд по номеру блокируют
 * отправку СМС на него: и подтверждение, и восстановление пароля. Покупатель
 * видит просьбу позвонить оператору; снять блокировку можно только в админке («Покупатели → Заблокированные номера»,
 * gm_cabinet.blocked). Удачный ввод кода счётчик обнуляет. Неверные пароли в счёт не идут (у входа своя временная
 * пауза) и запросы кода сверх лимита тоже: иначе чужой номер можно было бы заблокировать, не имея к нему доступа.
 * Журнал ?:gm_auth_log – события для страницы админки и сводки gm_cabinet.auth_status (оповещения):
 * sms_verify, sms_recover – СМС принято сервисом; sms_error – сервис отказал; code_ok, recover_ok – код введён верно;
 * code_bad – неверный код; limit – запрос сверх потолка; login_bad, login_lock – неверный пароль и пауза входа;
 * block, unblock – блокировка номера и её снятие; unverify – подтверждение номера перешло к другой записи.
 * ------------------------------------------------------------------------------------------------------------- */

const GM_CAB_STRIKES = 6;
const GM_CAB_BLOCKED_TEXT = 'Отправка СМС на этот номер заблокирована на 1 час: слишком много неудачных попыток. Позвоните оператору интернет-магазина по номеру 8 (800) 250-17-00 с этого телефона.';

/** Записать событие в журнал. Сбой журнала не должен мешать работе кабинета. */
function fn_gm_cab_log($event, $phone, $user_id = 0, $info = '')
{
    try {
        // В тексте ошибки сервиса СМС могут оказаться логин и пароль – вырезаем
        $info = (string) $info;
        foreach (gm_cab_secrets() as $secret) {
            if (is_string($secret) && strlen($secret) >= 4) {
                $info = str_replace($secret, '***', $info);
            }
        }
        db_query('INSERT INTO ?:gm_auth_log ?e', [
            'ts' => TIME,
            'phone' => (string) $phone,
            'user_id' => (int) $user_id,
            'event' => (string) $event,
            'info' => mb_substr($info, 0, 120),
        ]);
    } catch (\Throwable $e) {
    }
}

/** Заблокирована ли отправка СМС на номер. */
function fn_gm_cab_phone_blocked($phone)
{
    if ((string) $phone === '') {
        return false;
    }
    $at = (int) db_get_field('SELECT blocked_at FROM ?:gm_phone_auth WHERE phone = ?s', $phone);
    if ($at > 0 && $at + GM_CAB_BLOCK_TTL <= TIME) {
        // Срок блокировки истёк: снимаем сами и обнуляем счётчики
        db_query("UPDATE ?:gm_phone_auth SET blocked_at = 0, blocked_reason = '', strikes = 0, attempts = 0, sent_count = 0 WHERE phone = ?s", $phone);
        db_query('UPDATE ?:gm_phone_verify SET sent_count = 0, attempts = 0 WHERE code_phone = ?s', $phone);
        fn_gm_cab_log('unblock', $phone, 0, 'срок истёк');

        return false;
    }

    return $at > 0;
}

/** Засчитать неудачу по номеру. true – после неё номер заблокирован. */
function fn_gm_cab_strike($phone, $reason, $user_id = 0)
{
    if ((string) $phone === '') {
        return false;
    }
    fn_gm_cab_phone_blocked($phone);
    db_query('INSERT INTO ?:gm_phone_auth (phone, strikes) VALUES (?s, 1) ON DUPLICATE KEY UPDATE strikes = strikes + 1', $phone);
    $row = db_get_row('SELECT strikes, blocked_at FROM ?:gm_phone_auth WHERE phone = ?s', $phone);
    if ((int) $row['blocked_at'] > 0) {
        return true;
    }
    if ((int) $row['strikes'] < GM_CAB_STRIKES) {
        return false;
    }
    db_query('UPDATE ?:gm_phone_auth SET blocked_at = ?i, blocked_reason = ?s WHERE phone = ?s', TIME, (string) $reason, $phone);
    fn_gm_cab_log('block', $phone, $user_id, $reason);

    return true;
}

/** Обнулить счётчик неудач после удачного ввода кода (блокировку не снимает). */
function fn_gm_cab_strike_reset($phone)
{
    db_query('UPDATE ?:gm_phone_auth SET strikes = 0 WHERE phone = ?s AND blocked_at = 0', $phone);
}

/**
 * Отказ в отправке кода из-за часового потолка. В журнал пишем, но неудачей по номеру не считаем: запросить код
 * может кто угодно, и посторонний не должен блокировать чужой номер одними запросами.
 */
function fn_gm_cab_limit_refused($phone, $user_id = 0)
{
    fn_gm_cab_log('limit', $phone, $user_id);

    return ['ok' => false, 'message' => 'Слишком много запросов кода. Попробуйте через час.'];
}

/** Покупатели GOODMi с этим номером в профиле – для страниц админки. */
function fn_gm_cab_users_by_phone($phone)
{
    $rows = db_get_array(
        "SELECT user_id, firstname, lastname, email, phone FROM ?:users WHERE company_id = 2 AND user_type = 'C'"
        . " AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?l ORDER BY user_id DESC LIMIT 5",
        '%' . substr((string) $phone, -10)
    );
    foreach ($rows as $k => $row) {
        $rows[$k]['name'] = trim($row['firstname'] . ' ' . $row['lastname']);
        $rows[$k]['email'] = gm_cab_is_stub_email($row['email']) ? '' : $row['email'];
    }

    return $rows;
}

/** Заблокированные номера для страницы админки: когда, за что, чьи, последние события. */
function fn_gm_cab_blocked_list()
{
    $rows = db_get_array('SELECT phone, blocked_at, blocked_reason, strikes FROM ?:gm_phone_auth WHERE blocked_at > ?i ORDER BY blocked_at DESC LIMIT 300', TIME - GM_CAB_BLOCK_TTL);
    $titles = gm_cab_log_titles();
    foreach ($rows as $k => $row) {
        $rows[$k]['pretty'] = '+7 ' . substr($row['phone'], 1, 3) . ' ' . substr($row['phone'], 4, 3) . '-' . substr($row['phone'], 7, 2) . '-' . substr($row['phone'], 9, 2);
        $rows[$k]['when'] = date('d.m.Y H:i', (int) $row['blocked_at']);
        $rows[$k]['users'] = fn_gm_cab_users_by_phone($row['phone']);
        $events = db_get_array('SELECT ts, event FROM ?:gm_auth_log WHERE phone = ?s ORDER BY id DESC LIMIT 8', $row['phone']);
        foreach ($events as $i => $event) {
            $events[$i] = date('d.m H:i', (int) $event['ts']) . ' – ' . (isset($titles[$event['event']]) ? $titles[$event['event']] : $event['event']);
        }
        $rows[$k]['events'] = $events;
    }

    return $rows;
}

/** Названия событий журнала для админки. */
function gm_cab_log_titles()
{
    return [
        'sms_verify' => 'СМС с кодом подтверждения',
        'sms_recover' => 'СМС с кодом восстановления',
        'sms_error' => 'сервис СМС отказал',
        'code_ok' => 'код введён верно',
        'recover_ok' => 'пароль восстановлен',
        'code_bad' => 'неверный код',
        'limit' => 'запрос кода сверх лимита',
        'login_bad' => 'неверный пароль',
        'login_lock' => 'пауза входа по номеру',
        'block' => 'номер заблокирован',
        'unblock' => 'блокировка снята',
        'unverify' => 'подтверждение перешло к другой записи',
    ];
}

/** Снять блокировку с номера (админка): обнуляются и счётчики, чтобы покупатель мог сразу запросить код. */
function fn_gm_cab_unblock($phone, $admin_id)
{
    $phone = gm_cab_phone($phone);
    if ($phone === '' || !fn_gm_cab_phone_blocked($phone)) {
        return ['ok' => false, 'message' => 'Этот номер не заблокирован.'];
    }
    db_query(
        "UPDATE ?:gm_phone_auth SET blocked_at = 0, blocked_reason = '', strikes = 0, login_fails = 0, attempts = 0, sent_count = 0 WHERE phone = ?s",
        $phone
    );
    db_query('UPDATE ?:gm_phone_verify SET sent_count = 0, attempts = 0 WHERE code_phone = ?s', $phone);
    fn_gm_cab_log('unblock', $phone, (int) $admin_id);

    return ['ok' => true, 'message' => 'Блокировка с номера ' . gm_cab_phone_mask($phone) . ' снята.'];
}

/**
 * Сводка для оповещений (страница gm_cabinet.auth_status, читает облачная задача и пишет в Telegram): счётчики за час
 * и за сегодня и список тревог. Номера – только со скрытыми цифрами.
 */
function fn_gm_cab_auth_status($hours = 1)
{
    // $hours – за сколько последних часов считать окно «hour» и тревоги (утренний запуск смотрит всю ночь)
    $hours = max(1, min(24, (int) $hours));
    $now = TIME;
    $hour = $now - 3600 * $hours;
    $day = strtotime('today', $now);
    $count = function (array $events, $since) {
        return (int) db_get_field('SELECT COUNT(*) FROM ?:gm_auth_log WHERE event IN (?a) AND ts > ?i', $events, $since);
    };
    $sms = ['sms_verify', 'sms_recover'];
    $ok = ['code_ok', 'recover_ok'];
    // Сколько СМС подряд осталось без введённого кода (не считая отправленных за последние 10 минут)
    $last_ok = (int) db_get_field('SELECT MAX(ts) FROM ?:gm_auth_log WHERE event IN (?a)', $ok);
    $streak = (int) db_get_field('SELECT COUNT(*) FROM ?:gm_auth_log WHERE event IN (?a) AND ts > ?i AND ts < ?i', $sms, $last_ok, $now - 600);
    $blocked_new = db_get_array('SELECT phone, blocked_at, blocked_reason FROM ?:gm_phone_auth WHERE blocked_at > ?i ORDER BY blocked_at', $hour);
    foreach ($blocked_new as $k => $row) {
        $blocked_new[$k] = ['phone' => gm_cab_phone_mask($row['phone']), 'at' => date('H:i', (int) $row['blocked_at']), 'reason' => $row['blocked_reason']];
    }
    $pressure = db_get_array(
        "SELECT phone, COUNT(*) AS n FROM ?:gm_auth_log WHERE event IN ('login_bad', 'code_bad', 'limit') AND ts > ?i GROUP BY phone HAVING n >= 5 ORDER BY n DESC LIMIT 10",
        $hour
    );
    foreach ($pressure as $k => $row) {
        $pressure[$k] = ['phone' => gm_cab_phone_mask($row['phone']), 'fails' => (int) $row['n']];
    }
    $status = [
        'time' => date('Y-m-d H:i', $now),
        'window_hours' => $hours,
        'hour' => [
            'sms_sent' => $count($sms, $hour), 'sms_errors' => $count(['sms_error'], $hour), 'codes_ok' => $count($ok, $hour),
            'codes_bad' => $count(['code_bad'], $hour), 'limit_refusals' => $count(['limit'], $hour),
            'login_bad' => $count(['login_bad'], $hour), 'login_locks' => $count(['login_lock'], $hour),
        ],
        'today' => [
            'sms_sent' => $count($sms, $day), 'sms_errors' => $count(['sms_error'], $day), 'codes_ok' => $count($ok, $day),
            'login_bad' => $count(['login_bad'], $day), 'blocks' => $count(['block'], $day),
        ],
        'sms_without_code_in_row' => $streak,
        'blocked_total' => (int) db_get_field('SELECT COUNT(*) FROM ?:gm_phone_auth WHERE blocked_at > 0'),
        'blocked_last_hour' => $blocked_new,
        'numbers_under_pressure' => $pressure,
        'alerts' => [],
    ];
    if ($status['hour']['sms_errors'] > 0) {
        $error = (string) db_get_field("SELECT info FROM ?:gm_auth_log WHERE event = 'sms_error' ORDER BY id DESC LIMIT 1");
        $status['alerts'][] = 'Сервис СМС отказал в отправке: ' . $status['hour']['sms_errors'] . ' раз' . ($error !== '' ? ' (' . $error . ')' : '');
    }
    if ($streak >= 5) {
        $status['alerts'][] = 'СМС, похоже, не доходят: ' . $streak . ' подряд отправлено, ни один код не введён';
    }
    foreach ($blocked_new as $row) {
        $status['alerts'][] = 'Заблокирован номер ' . $row['phone'] . ' в ' . $row['at'] . ' (' . $row['reason'] . ')';
    }
    foreach ($pressure as $row) {
        $status['alerts'][] = 'Много неудачных попыток по номеру ' . $row['phone'] . ': ' . $row['fails'];
    }
    if ($status['hour']['login_locks'] > 0) {
        $status['alerts'][] = 'Пауза входа по номеру сработала ' . $status['hour']['login_locks'] . ' раз';
    }

    // Антивирус maldet: выжимку последней ежедневной проверки раз в час кладёт root (/usr/local/sbin/gm-maldet-status).
    // Находки сообщаем один раз – в окне, куда попало окончание проверки; «проверка не шла 30 часов» – тоже один раз.
    $scan = json_decode((string) @file_get_contents('/var/lib/gm-status/maldet.json'), true);
    if (!is_array($scan) || empty($scan['written_at']) || (int) $scan['written_at'] < $now - 3 * 3600) {
        $status['malware'] = null;
        $status['alerts'][] = 'Нет свежих данных о проверке сайтов на вирусы – не работает выжимка /usr/local/sbin/gm-maldet-status';
    } else {
        $done = (int) $scan['completed_at'];
        $status['malware'] = [
            'last_scan' => $done ? date('Y-m-d H:i', $done) : '', 'files' => (int) $scan['files'],
            'hits' => (int) $scan['hits'], 'cleaned' => (int) $scan['cleaned'],
        ];
        if ($scan['hits'] > 0 && $done > $hour) {
            $files = array_slice((array) $scan['hit_files'], 0, 5);
            $status['alerts'][] = 'Антивирус нашёл подозрительные файлы на сервере: ' . (int) $scan['hits'] . ' (проверка ' . date('d.m H:i', $done) . ')'
                . ($files ? ': ' . implode('; ', $files) : '') . '. Отчёт: maldet --report ' . $scan['scan_id'];
        }
        $stale = $now - 30 * 3600;
        if ($done < $stale && $done >= $stale - 3600 * $hours) {
            $status['alerts'][] = 'Проверка сайтов на вирусы не запускалась больше 30 часов (последняя – ' . ($done ? date('d.m H:i', $done) : 'неизвестно') . ')';
        }
    }

    // Сторож кода сайтов (2026-10-18, после найденных веб-шеллов): раз в час /usr/local/sbin/gm-webshell-watch проверяет своими
    // правилами ClamAV все файлы с кодом в /var/www (официальные базы и maldet закладки не узнали) и сравнивает PHP-файлы с прошлым
    // запуском. Закладка – в каждой сводке, пока её не убрали; новые и изменённые файлы – по журналу code-changes.jsonl за окно
    // сводки (каждое изменение один раз); нет свежих данных 3 часа – тоже тревога.
    $watch = json_decode((string) @file_get_contents('/var/lib/gm-status/webshell.json'), true);
    if (!is_array($watch) || empty($watch['written_at']) || (int) $watch['written_at'] < $now - 3 * 3600) {
        $status['code_watch'] = null;
        $status['alerts'][] = 'Нет свежих данных сторожа кода сайтов – не работает /usr/local/sbin/gm-webshell-watch (cron /etc/cron.d/gm-webshell-watch)';
    } else {
        $status['code_watch'] = ['checked' => date('Y-m-d H:i', (int) $watch['written_at']), 'files' => (int) $watch['files'], 'hits' => count((array) $watch['hits'])];
        foreach ((array) $watch['hits'] as $h) {
            $status['alerts'][] = 'ЗАКЛАДКА ВЗЛОМЩИКА на сервере: ' . $h['file'] . ' (' . $h['rule'] . '). Убрать в карантин, как в /root/incident-2026-10-18/';
        }
        if (!empty($watch['error'])) {
            $status['alerts'][] = 'Сторож кода сайтов: ' . $watch['error'];
        }
        // Тестовая копия (dev.*, пользователь www-root) – одной строкой без списка (2026-10-19): там правим мы сами, а подробный
        // список вперемешку с боевыми мешал заметить настоящее изменение. Закладки на копии всё равно – отдельной тревогой выше.
        $changes = [];
        $dev_changes = 0;
        foreach ((array) @file('/var/lib/gm-status/code-changes.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $c = json_decode($line, true);
            if (!is_array($c) || (int) $c['ts'] <= $hour) {
                continue;
            }
            if (strpos($c['file'], '/var/www/www-root/') === 0) {
                $dev_changes++;
                continue;
            }
            $changes[] = ($c['kind'] === 'added' ? 'новый ' : 'изменён ') . str_replace('/var/www/maxmobiles/data/www/maxmobiles.ru/', '', $c['file']);
        }
        if ($changes) {
            $status['alerts'][] = 'Код сайтов изменился (' . count($changes) . '): ' . implode('; ', array_slice($changes, 0, 10))
                . (count($changes) > 10 ? '; …' : '') . '. Если это не наша правка – проверить';
        }
        if ($dev_changes) {
            $status['alerts'][] = 'На тестовой копии (dev) изменено файлов с кодом: ' . $dev_changes
                . ' – обычно это наши правки; список – /var/lib/gm-status/code-changes.jsonl';
        }
        // События из журналов сайтов за окно сводки: ошибки доступа к файлам (после снятия «записи для всех») и отказы нашего
        // правила gm-no-php-in-data.conf, которые могли быть нужными (закрытые папки данных, существующий PHP в папке данных)
        $events = ['perm_error' => [], 'blocked_data' => [], 'blocked_php' => []];
        foreach ((array) @file('/var/lib/gm-status/events.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $e = json_decode($line, true);
            if (is_array($e) && (int) $e['ts'] > $hour && isset($events[$e['kind']])) {
                $events[$e['kind']][] = $e['kind'] === 'perm_error' ? $e['site'] . ': ' . mb_substr($e['line'], 0, 200) : $e['site'] . $e['uri'] . ' (' . $e['ip'] . ')';
            }
        }
        $titles = [
            'perm_error' => 'Ошибки доступа к файлам на сайтах – возможно, после снятия «записи для всех» что-то не может записать файл',
            'blocked_data' => 'Заблокированы запросы к закрытым папкам данных (bonusplus, logsklad, mc_log, sber) – если это нужная интеграция, разобраться',
            'blocked_php' => 'Заблокирован запуск существующего PHP-файла в папке данных – проверить, что это за файл',
        ];
        foreach ($events as $kind => $list) {
            if ($list) {
                $status['alerts'][] = $titles[$kind] . ' (' . count($list) . '): ' . implode('; ', array_slice(array_unique($list), 0, 3)) . (count($list) > 3 ? '; …' : '');
            }
        }
    }

    // Свой поиск обеих витрин (Manticore, общий для сервера): индекс собирается каждые 2 часа (GOODMi – :15 чётных часов,
    // Maxmobiles – :15 нечётных). «Не обновлялся 5 часов» сообщаем один раз – в окне, куда попал порог; «Manticore не отвечает» –
    // при каждой сводке (2026-10-17: Manticore завис, сборки 14 ч не проходили, и никто об этом не узнал).
    $status['search'] = [];
    $mc = mysqli_init();
    $mc->options(MYSQLI_OPT_CONNECT_TIMEOUT, 1);
    $mc->options(MYSQLI_OPT_READ_TIMEOUT, 3);
    if (!@$mc->real_connect('127.0.0.1', '', '', '', 9306)) {
        $status['search'] = null;
        $status['alerts'][] = 'Поиск на сайтах не работает: Manticore не отвечает (служба manticore на сервере магазинов)';
    } else {
        foreach (['gm' => 'GOODMi', 'mm' => 'Maxmobiles'] as $px => $name) {
            $r = @$mc->query("SELECT MAX(ts) AS last FROM {$px}_status WHERE kind = 'index'");
            $row = $r ? $r->fetch_assoc() : null;
            if (!$row) {
                $status['search'][$px] = null;
                $status['alerts'][] = 'Поиск ' . $name . ': не прочитать время последней сборки индекса (' . ($mc->error !== '' ? $mc->error : 'нет данных') . ')';
                continue;
            }
            $last = (int) $row['last'];
            $status['search'][$px] = $last ? date('Y-m-d H:i', $last) : '';
            $stale = $now - 5 * 3600;
            if ($last < $stale && $last >= $stale - 3600 * $hours) {
                $status['alerts'][] = 'Поиск ' . $name . ': индекс не обновлялся больше 5 часов (последняя сборка – ' . date('d.m H:i', $last)
                    . '). Журнал: /root/' . $px . '-search/cron.log';
            }
        }
        $mc->close();
    }

    // СМС всех витрин (2026-10-18): Таргет СМС отвечает, баланс, доставка, коды Maxmobiles – app/sms_stats/stats.php
    // (та же проверка – плашка на странице «Покупатели → СМС: работа и баланс»). Сбой расчёта не ломает сводку
    try {
        require_once __DIR__ . '/../sms_stats/stats.php';
        $sms_health = sms_stats_health();
        $status['sms'] = ['level' => $sms_health['level'], 'checks' => []];
        foreach ($sms_health['checks'] as $c) {
            $status['sms']['checks'][] = $c['title'] . ': ' . $c['text'];
        }
        $status['alerts'] = array_merge($status['alerts'], sms_stats_alerts($hours));
    } catch (\Throwable $e) {
        $status['sms'] = null;
        $status['alerts'][] = 'СМС: не удалось проверить состояние – ' . $e->getMessage();
    }

    return $status;
}

/**
 * Ширины полос и промежутков Code 128 для строки цифр (2026-10-21): чётное число цифр – набор C (две цифры в знаке),
 * нечётное – набор B. Контрольный знак – (старт + сумма значение × позиция) mod 103. Таблица – стандартные 107 шаблонов
 * (значения 0–105 и стоп), как в app/addons/barcode/Tygh/Tools/Barcode/Generator.php (набор B). Свой расчёт вместо
 * генератора CS-Cart: в его наборе C нет знаков 100–102, и когда контрольный знак на них попадает (~3 % номеров), он
 * выпадает из кода – касса такой штрих-код не читает (в журнале – «Undefined array key» в Generator.php:726).
 */
function fn_gm_cab_code128_widths($digits)
{
    static $patterns = null;
    if ($patterns === null) {
        $patterns = explode(',', '212222,222122,222221,121223,121322,131222,122213,122312,132212,221213,221312,231212,112232,122132,122231,113222,123122,123221,223211,221132,221231,213212,223112,312131,311222,321122,321221,312212,322112,322211,212123,212321,232121,111323,131123,131321,112313,132113,132311,211313,231113,231311,112133,112331,132131,113123,113321,133121,313121,211331,231131,213113,213311,213131,311123,311321,331121,312113,312311,332111,314111,221411,431111,111224,111422,121124,121421,141122,141221,112214,112412,122114,122411,142112,142211,241211,221114,413111,241112,134111,111242,121142,121241,114212,124112,124211,411212,421112,421211,212141,214121,412121,111143,111341,131141,114113,114311,411113,411311,113141,114131,311141,411131,211412,211214,211232,2331112');
    }
    if (strlen($digits) % 2 === 0) {
        $start = 105;
        $values = array_map('intval', str_split($digits, 2));
    } else {
        $start = 104;
        $values = array_map(static function ($c) {
            return ord($c) - 32;
        }, str_split($digits));
    }
    $sum = $start;
    $widths = $patterns[$start];
    foreach ($values as $i => $value) {
        $sum += $value * ($i + 1);
        $widths .= $patterns[$value];
    }

    return $widths . $patterns[$sum % 103] . $patterns[106];
}

/**
 * Штрих-код номера карты лояльности (Code 128) в виде SVG – покупатель показывает его на кассе с экрана телефона.
 * Ширины полос – fn_gm_cab_code128_widths (своя кодировка по стандартной таблице; с 2026-10-21 вместо генератора модуля
 * CS-Cart «Штрих-код», у которого в наборе C терялся контрольный знак). Картинку рисуем сами векторно: она остаётся чёткой
 * при любом размере. Пустая строка – номер не из цифр или не той длины: тогда показывается только номер.
 */
function fn_gm_cab_barcode_svg($digits)
{
    $digits = (string) $digits;
    if (!ctype_digit($digits) || strlen($digits) < 6 || strlen($digits) > 24) {
        return '';
    }
    try {
        $widths = fn_gm_cab_code128_widths($digits);
        $quiet = 10;
        $x = $quiet;
        $bars = '';
        foreach (str_split($widths) as $i => $w) {
            if ($i % 2 === 0) {
                $bars .= '<rect x="' . $x . '" y="0" width="' . (int) $w . '" height="60"/>';
            }
            $x += (int) $w;
        }
        $total = $x + $quiet;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $total . ' 60" preserveAspectRatio="none" shape-rendering="crispEdges"'
            . ' role="img" aria-label="Штрих-код карты лояльности"><rect width="' . $total . '" height="60" fill="#fff"/><g fill="#000">' . $bars . '</g></svg>';
    } catch (\Throwable $e) {
        return '';
    }
}

