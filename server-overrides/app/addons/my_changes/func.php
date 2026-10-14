<?php
/**
 * Модуль «Мои изменения» (my_changes): свои доработки, которые не относятся к другим модулям.
 * Шаблоны Maxmobiles лежат в design/themes/abt__unitheme2/templates/addons/my_changes/ (у каждого – условие витрины).
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/*
 * Сравнение товаров: группировка списков «по разделам» (класс GmSectionComparisonGrouper рядом с этим файлом).
 * Включается на витрине в настройках модуля «Расширенное сравнение»: «Группировать по» = «По разделам».
 */

spl_autoload_register(function ($class) {
    if ($class === 'Ab_extendedComparisonWishlist\\ComparisonLists\\GmSectionComparisonGrouper') {
        require_once __DIR__ . '/GmSectionComparisonGrouper.php';
    }
});

/**
 * Хук модуля сравнения: добавляет вариант «По разделам» в настройку «Группировать по».
 */
function fn_my_changes_ab__extended_comparison_wishlist_group_by_post(&$group_by)
{
    $group_by['gm_section'] = 'По разделам (свои группы GOODMi и Maxmobiles)';
}

/**
 * Крупные группы сравнения по витринам: товар попадает в группу, если его главная категория лежит внутри одной из
 * категорий roots (вместе с подкатегориями). link – куда ведёт «Смотреть все» у списка (по умолчанию первая из roots).
 * Чтобы добавить группу – дописать строку; товары остальных категорий группируются по разделу второго уровня.
 */
function fn_my_changes_gm_compare_groups($company_id)
{
    $groups = [
        // Maxmobiles
        1 => [
            ['title' => 'iPhone', 'roots' => [166, 413]],                   // все iPhone + iPhone БУ
            ['title' => 'iPad', 'roots' => [203]],
            ['title' => 'Apple Watch', 'roots' => [174]],
            ['title' => 'Android-смартфоны', 'roots' => [2129]],            // Samsung, Google, HONOR, HUAWEI, OnePlus
            ['title' => 'MacBook', 'roots' => [225, 267, 2532], 'link' => 223],   // MacBook Air, Pro, Neo
            ['title' => 'Компьютеры Mac', 'roots' => [219, 407, 671], 'link' => 223], // iMac, Mac mini, Mac Studio
        ],
        // GOODMi
        2 => [
            ['title' => 'Смартфоны', 'roots' => [412]],
            ['title' => 'Планшеты', 'roots' => [448]],
        ],
    ];

    return isset($groups[$company_id]) ? $groups[$company_id] : [];
}

/**
 * Группа сравнения для каждого товара: [product_id => ['key' => '166', 'title' => 'iPhone', 'link' => 166]].
 */
function fn_my_changes_gm_compare_product_groups(array $product_ids, $company_id)
{
    $product_ids = array_filter(array_map('intval', $product_ids));
    if (!$product_ids) {
        return [];
    }
    $main = db_get_hash_single_array(
        'SELECT product_id, category_id FROM ?:products_categories WHERE product_id IN (?n) AND link_type = ?s',
        ['product_id', 'category_id'],
        $product_ids,
        'M'
    );
    if (!$main) {
        return [];
    }
    $paths = db_get_hash_single_array('SELECT category_id, id_path FROM ?:categories WHERE category_id IN (?n)', ['category_id', 'id_path'], array_unique(array_values($main)));
    $config = fn_my_changes_gm_compare_groups($company_id);

    $result = [];
    $need_names = [];
    foreach ($main as $product_id => $category_id) {
        $path = isset($paths[$category_id]) ? array_map('intval', explode('/', $paths[$category_id])) : [(int) $category_id];
        $found = null;
        foreach ($config as $group) {
            if (array_intersect($path, $group['roots'])) {
                $found = [
                    'key' => (string) reset($group['roots']),
                    'title' => $group['title'],
                    'link' => isset($group['link']) ? $group['link'] : reset($group['roots']),
                ];
                break;
            }
        }
        if ($found === null) {
            $section = isset($path[1]) ? $path[1] : $path[0];
            $found = ['key' => (string) $section, 'title' => '', 'link' => $section];
            $need_names[$section] = $section;
        }
        $result[(int) $product_id] = $found;
    }
    if ($need_names) {
        $names = (array) fn_get_category_name(array_values($need_names));
        foreach ($result as $product_id => $group) {
            if ($group['title'] === '') {
                $result[$product_id]['title'] = isset($names[$group['link']]) ? $names[$group['link']] : '';
            }
        }
    }

    return $result;
}

/* ================================================================
 * Личный кабинет Maxmobiles (company_id = 1) – отдельная копия кабинета GOODMi (решение пользователя 2026-10-14).
 * Логика – app/mm_cabinet/store.php; здесь обработчики событий магазина (подключены в init.php): отзывы от купивших
 * в магазине, доступ к заказу по подтверждённому номеру, вход по номеру, регистрация без почты, код после регистрации,
 * «до подтверждения номера – только страница подтверждения», отметки «номер подтверждён» в админке.
 * Копии обработчиков GOODMi из app/addons/gm_delivery_date/func.php – исправления вносить в обе копии.
 * ================================================================ */

/**
 * Право оставить отзыв (модуль «Отзывы о товарах», настройка «только после покупки»): магазин штатно считает покупкой
 * только заказ сайта. На витрине Maxmobiles покупкой считается и выданный заказ в магазине – по данным личного кабинета
 * (app/mm_cabinet/store.php: подтверждённый номер, МойСклад). Для таких покупателей – один отзыв на товар.
 * Копия обработчика GOODMi из модуля gm_delivery_date.
 */
function fn_my_changes_product_reviews_is_user_eligible_to_write_product_review($user_id, $product_id, $ip, $need_to_buy_first, $review_ip_check, &$result)
{
    $code = 'product_reviews.need_to_buy_first';
    if (!$need_to_buy_first || !$user_id || !$product_id || (int) \Tygh\Registry::get('runtime.company_id') !== 1 || !is_object($result)) {
        return;
    }
    $errors = $result->getErrors();
    if (!isset($errors[$code])) {
        return;
    }
    try {
        $store = \Tygh\Registry::get('config.dir.root') . '/app/mm_cabinet/store.php';
        if (!is_file($store)) {
            return;
        }
        require_once $store;
        if (!fn_mm_cab_bought_in_store((int) $user_id, (int) $product_id)) {
            return;
        }
        $result->removeError($code);
        if (db_get_field('SELECT 1 FROM ?:product_reviews WHERE user_id = ?i AND product_id = ?i LIMIT 1', $user_id, $product_id)) {
            $result->addError('mm_cabinet.already_reviewed', 'Вы уже оставили отзыв на этот товар.');

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
 * Доступ к заказу сайта: штатно покупатель видит только заказы своей учётной записи. На витрине Maxmobiles вошедший
 * покупатель может открыть и заказ, оформленный без входа на его подтверждённый номер телефона (такие заказы
 * показывает «Мои заказы» личного кабинета). Проверка – fn_mm_cab_order_by_phone в app/mm_cabinet/store.php.
 */
function fn_my_changes_is_order_allowed_post($order_id, $auth, &$allowed)
{
    if (!empty($allowed) || empty($auth['user_id']) || !$order_id || !defined('AREA') || AREA !== 'C'
        || (int) \Tygh\Registry::get('runtime.company_id') !== 1) {
        return;
    }
    try {
        $store = \Tygh\Registry::get('config.dir.root') . '/app/mm_cabinet/store.php';
        if (is_file($store)) {
            require_once $store;
            if (fn_mm_cab_order_by_phone((int) $auth['user_id'], (int) $order_id)) {
                $allowed = 1;
            }
        }
    } catch (\Throwable $e) {
        // При сбое действует штатное правило доступа
    }
}

/** Витрина Maxmobiles на стороне покупателя – условие всех обработчиков входа и регистрации. */
function fn_my_changes_mm_is_front()
{
    return defined('AREA') && AREA === 'C' && (int) \Tygh\Registry::get('runtime.company_id') === 1;
}

/** Подключить логику личного кабинета (app/mm_cabinet/store.php). false – файла нет, обработчик должен отступить. */
function fn_my_changes_mm_cabinet()
{
    $store = \Tygh\Registry::get('config.dir.root') . '/app/mm_cabinet/store.php';
    if (!is_file($store)) {
        return false;
    }
    require_once $store;

    return true;
}

/**
 * Вход по номеру телефона (витрина Maxmobiles). Если в поле входа введён не e-mail, а номер, штатная проверка ищет
 * покупателя, у которого этот номер подтверждён и стоит в профиле; пароль проверяет сам CS-Cart. Введённое
 * покупателем значение не меняем (оно возвращается в форму при ошибке), поэтому подменяем только условие поиска:
 * «user_id = N OR NULL = 'введённое'» находит ровно этого покупателя, «NULL = 'введённое'» – никого.
 * Кого пускать и счётчик неверных паролей – fn_mm_cab_login_by_phone в app/mm_cabinet/store.php.
 * По e-mail-заглушке (mm_cab_is_stub_email) войти нельзя: настоящего адреса за ней нет.
 */
function fn_my_changes_auth_routines($request, $auth, &$field, &$condition, &$user_login)
{
    if (!fn_my_changes_mm_is_front()) {
        return;
    }
    $login = trim((string) $user_login);
    if ($login === '') {
        return;
    }
    try {
        if (!fn_my_changes_mm_cabinet()) {
            return;
        }
        if (strpos($login, '@') !== false) {
            if (mm_cab_is_stub_email($login)) {
                $field = 'NULL';
            }

            return;
        }
        $found = fn_mm_cab_login_by_phone($login, isset($request['password']) ? (string) $request['password'] : '');
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
 * Запись без своей почты (витрина Maxmobiles) хранится с e-mail-заглушкой из номера телефона (fn_mm_cab_stub_email) –
 * её подставляет profiles.pre.php. Письмо о регистрации на заглушку не отправляем.
 */
function fn_my_changes_update_user_pre($user_id, $user_data, $auth, $ship_to_another, &$notify_user, &$can_update)
{
    if (!empty($user_id) || !isset($user_data['email']) || !fn_my_changes_mm_is_front()) {
        return;
    }
    try {
        if (fn_my_changes_mm_cabinet() && mm_cab_is_stub_email($user_data['email'])) {
            $notify_user = false;
        }
    } catch (\Throwable $e) {
    }
}

/**
 * После регистрации через форму сайта (витрина Maxmobiles): сразу отправляем на номер код подтверждения; покупатель
 * попадает в «Мои заказы», где ждёт поле для кода и подсказка про оператора. Работает только для регистрации,
 * которую пометил profiles.pre.php (runtime.mm_reg_phone), – не для записей, создаваемых при оформлении заказа.
 */
function fn_my_changes_update_profile($action, &$user_data, $current_user_data)
{
    if ($action !== 'add' || empty($user_data['user_id']) || !fn_my_changes_mm_is_front()
        || (string) \Tygh\Registry::get('runtime.mm_reg_phone') === '') {
        return;
    }
    $user_id = (int) $user_data['user_id'];
    try {
        if (!fn_my_changes_mm_cabinet()) {
            return;
        }
        $phone = fn_mm_cab_user_phone($user_id);
        if ($phone === '' || !mm_cab_verify_enabled($user_id)) {
            return;
        }
        // Сначала отметка «обязан подтвердить номер» (до подтверждения сайт закрыт), потом отправка кода:
        // сбой отправки не должен оставить запись с полным доступом
        fn_mm_cab_must_verify_set($user_id);
        $_REQUEST['redirect_url'] = 'mm_cabinet.orders';
        try {
            $sent = fn_mm_cab_verify_send($user_id, $phone);
        } catch (\Throwable $e) {
            $sent = ['ok' => false];
        }
        if ($sent['ok']) {
            fn_set_notification('N', 'Подтвердите номер', 'Мы отправили СМС с кодом на ' . mm_cab_phone_mask($phone) . '. Введите код, чтобы подтвердить номер: после этого можно оформлять заказы и входить по номеру.');
        } else {
            fn_set_notification('W', 'Номер пока не подтверждён', 'Отправить СМС с кодом не получилось. ' . MM_CAB_OPERATOR_TEXT);
        }
    } catch (\Throwable $e) {
        // При сбое регистрация завершается, но запись остаётся закрытой до подтверждения номера
        try {
            db_query('INSERT INTO ?:mm_phone_verify (user_id, must_verify) VALUES (?i, 1) ON DUPLICATE KEY UPDATE must_verify = 1', $user_id);
            $_REQUEST['redirect_url'] = 'mm_cabinet.orders';
        } catch (\Throwable $e2) {
        }
    }
}

/**
 * Регистрация по номеру телефона не считается завершённой, пока номер не подтверждён (решение 2026-10-13): такой
 * покупатель с любой страницы сайта попадает в «Мои заказы», где его ждёт блок подтверждения (код из СМС или звонок
 * оператору). Открыты только сама эта страница, отправка кода, профиль (исправить номер) и выход. Действует на записи,
 * отмеченные при регистрации (must_verify, см. fn_mm_cab_verify_required в app/mm_cabinet/store.php), – не на
 * покупателей, зарегистрированных раньше. Фоновые запросы страниц не трогаем, кроме корзины и оформления заказа.
 */
function fn_my_changes_before_dispatch($controller, $mode, $action, $dispatch_extra, $area)
{
    if ($area !== 'C' || defined('CONSOLE') || (int) \Tygh\Registry::get('runtime.company_id') !== 1) {
        return;
    }
    $user_id = isset(\Tygh::$app['session']['auth']['user_id']) ? (int) \Tygh::$app['session']['auth']['user_id'] : 0;
    if ($user_id <= 0) {
        return;
    }
    $open = ['mm_cabinet.orders', 'mm_cabinet.verify', 'auth.logout', 'profiles.update'];
    if (in_array($controller . '.' . $mode, $open, true) || (defined('AJAX_REQUEST') && $controller !== 'checkout')) {
        return;
    }
    try {
        if (!fn_my_changes_mm_cabinet() || !fn_mm_cab_verify_required($user_id)) {
            return;
        }
    } catch (\Throwable $e) {
        return;
    }
    fn_set_notification('W', 'Завершите регистрацию', 'Подтвердите номер телефона: введите код из СМС или позвоните оператору интернет-магазина по номеру 8 (800) 250-81-58 с этого телефона. До подтверждения остальные разделы сайта недоступны.', '', 'mm_verify_required');
    fn_redirect('mm_cabinet.orders');
}

/**
 * Админка: отметка «номер подтверждён» возле телефона покупателя Maxmobiles (списки заказов и покупателей, заказ, профиль –
 * шаблоны design/backend/templates/addons/my_changes/hooks/). Возвращает 'Y' – номер подтверждён, 'N' – нет,
 * '' – номера нет или он непригоден. С $user_id проверяется подтверждение именно этого покупателя, без него
 * (заказ) – подтверждён ли номер хоть в одной учётной записи. Таблица ?:mm_phone_verify, см. app/mm_cabinet/store.php.
 */
function fn_my_changes_mm_phone_mark($phone, $user_id = 0)
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
                ? db_get_field('SELECT 1 FROM ?:mm_phone_verify WHERE user_id = ?i AND phone = ?s AND verified_at > 0', (int) $user_id, $digits)
                : db_get_field('SELECT 1 FROM ?:mm_phone_verify WHERE phone = ?s AND verified_at > 0 LIMIT 1', $digits);
            $cache[$key] = $found ? 'Y' : 'N';
        } catch (\Throwable $e) {
            $cache[$key] = '';
        }
    }

    return $cache[$key];
}
