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

/** Отправить код на номер из профиля. Возвращает ['ok' => bool, 'message' => текст покупателю]. */
function fn_gm_cab_verify_send($user_id, $phone)
{
    if ($phone === '') {
        return ['ok' => false, 'message' => 'Сначала укажите номер телефона в профиле.'];
    }
    if (!gm_cab_verify_sms_enabled()) {
        return ['ok' => false, 'message' => 'Подтвердить номер сейчас можно у сотрудника магазина GOODMi или по телефону 8 (800) 250-17-00.'];
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
        return ['ok' => false, 'message' => 'Слишком много запросов кода. Попробуйте через час.'];
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

        return ['ok' => false, 'message' => 'Не удалось отправить код. Попробуйте позже или позвоните нам: 8 (800) 250-17-00.'];
    }
    fn_set_storage_data($day_key, $day + 1);
    db_replace_into('gm_phone_verify', [
        'user_id' => (int) $user_id,
        'phone' => $row ? $row['phone'] : '',
        'verified_at' => $row ? (int) $row['verified_at'] : 0,
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

        return ['ok' => false, 'message' => 'Неверный код. Проверьте СМС и попробуйте ещё раз.'];
    }
    db_query(
        "UPDATE ?:gm_phone_verify SET phone = ?s, verified_at = ?i, code_hash = '', code_expires = 0, attempts = 0 WHERE user_id = ?i",
        $phone, TIME, $user_id
    );

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

    return ['ok' => true, 'message' => 'Номер ' . gm_cab_phone_mask($phone) . ' подтверждён.'];
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
 * Считаются только выданные заказы по подтверждённому номеру из профиля. Сначала смотрим уже загруженный состав
 * заказов, затем спрашиваем МойСклад (ответ запоминается: «покупал» – на 30 дней, «не покупал» – на 10 минут).
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

    $states = db_get_fields(
        'SELECT o.state FROM ?:gm_ms_order_items i JOIN ?:gm_ms_orders o ON o.id = i.order_id'
        . " WHERE o.phone = ?s AND ((i.ext_code != '' AND i.ext_code IN (?a)) OR (?s != '' AND i.code = ?s))",
        $phone, $ext ?: [''], $code, $code
    );
    foreach ($states as $state) {
        if (gm_cab_status_kind($state) === 'received') {
            return true;
        }
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
 * Догрузить состав покупок в магазинах, у которых он ещё не загружен (состав хранится по первому показу заказа,
 * а для этого раздела нужен весь). МойСклад отдаёт до 100 заказов с составом за запрос; за один показ страницы –
 * не больше $max_pages запросов, продолжение – при следующем показе. Возвращает true, если загружено всё.
 */
function fn_gm_cab_store_load_items($phone, $max_pages = 3)
{
    $missing = db_get_fields('SELECT id FROM ?:gm_ms_orders WHERE phone = ?s AND items_at IS NULL', $phone);
    if (!$missing) {
        return true;
    }
    $dir = rtrim(\Tygh\Registry::get('config.dir.cache_misc'), '/') . '/gm_cabinet';
    $file = $dir . '/' . md5('items_load_' . $phone) . '.json';
    $mark = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    // Всё, что МойСклад отдал по контрагентам номера, уже записано; оставшиеся заказы не перепроверяем чаще раза в 6 часов
    if (is_array($mark) && !empty($mark['done']) && time() - (int) $mark['t'] < 6 * 3600) {
        return true;
    }
    $agents = fn_gm_cab_store_agents($phone);
    if (!$agents) {
        return true;
    }
    $missing = array_flip($missing);
    $offset = is_array($mark) && empty($mark['done']) && isset($mark['offset']) ? (int) $mark['offset'] : 0;
    $done = false;
    for ($page = 0; $page < $max_pages; $page++) {
        $data = gm_cab_ms_customer_items($agents, $offset);
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

    return $done;
}

/**
 * Купленные товары покупателя: выданные заказы сайта (свои и по подтверждённому номеру) и выданные покупки в магазинах.
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
        $rows = db_get_array(
            'SELECT i.ext_code, i.code, i.name, i.price, o.moment, o.state FROM ?:gm_ms_order_items i'
            . ' JOIN ?:gm_ms_orders o ON o.id = i.order_id WHERE o.phone = ?s ORDER BY o.moment DESC LIMIT 5000',
            $phone
        );
        $rows = array_values(array_filter($rows, function ($row) {
            return gm_cab_status_kind($row['state']) === 'received';
        }));
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
