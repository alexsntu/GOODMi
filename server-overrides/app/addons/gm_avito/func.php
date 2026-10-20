<?php
/**
 * GOODMi: выгрузка на Авито – функции (этап 1 – 2026-10-19, этап 2 – админка и описания, 2026-10-19).
 *
 * Файл автозагрузки (XML, formatVersion 3) собирается из cscart_gm_avito_items (у каждого товара витрины своя строка:
 * «Продавать на Авито», заголовок, «о товаре» – вступление описания, поля объявления), шаблонов категорий
 * (cscart_gm_avito_templates – категория Авито и её поля для категории сайта и вложенных) и живых данных сайта:
 * цена витрины (иначе общая цена товара), фото карточки, характеристики, остаток. Описание = «о товаре» + характеристики
 * + общий блок витрины (настройка common_block, {warranty} – срок гарантии категории); объявления, перенесённые из UnitXML,
 * до перевода на новый текст (настройка text_mode) идут с прежним описанием. Какие товары попадают в файл – настройка
 * stock_mode (GOODMi – только с остатком, Maxmobiles – все включённые); выключенные на сайте не выгружаются никогда.
 * Файл – var/files/<company>/gm_avito/feed.xml, отдаёт его controllers/frontend/gm_avito.php по секретному ключу.
 * Требования Авито: Id менять нельзя (опубликует заново), фото обновляется только по новому адресу, заголовок до 50 знаков,
 * описание до 7500 знаков, файл забирает Avitobot-Autoload с адресов Авито (не блокировать фильтрами).
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/** Поля объявления, которые генератор пишет сам; остальные берутся из шаблона и items.fields как есть */
const GM_AVITO_OWN_TAGS = ['Id', 'Title', 'Description', 'Price', 'Images'];

/** Поля объявления, которые шаблон категории может брать из характеристик товара: поле Авито => характеристики сайта */
const GM_AVITO_FEATURE_FIELDS = [
    'Brand' => ['Бренд', 'Производитель'],
    'Vendor' => ['Бренд', 'Производитель'],
    'Model' => ['Модель устройства', 'Модель'],
    'Color' => ['Цвет'],
];

/** Характеристики, которые не выводим в описании */
const GM_AVITO_SPECS_SKIP = ['Наличие по магазинам', 'Производитель', 'Модель устройства'];

/**
 * Предложения из описания сайта, которые не берём во вступление: про магазин, доставку, гарантию, город, ссылки и
 * контакты (это – в общем блоке или запрещено Авито), а также запрещённые для GOODMi слова
 */
const GM_AVITO_STOP_WORDS = '/фирменн|официальн|авторизованн|goodmi|http|www\.|@|\+7|8\s?\(?800|купить|закаж|заказ|в\s+наличии|доставк|самовывоз|гарант|рассрочк|кредит|скидк|акци|севастопол|симферопол|ялт|крым|магазин/iu';

/** Сроки гарантии модуля gm_delivery_date */
const GM_AVITO_WARRANTY_LABELS = ['y1' => '1 год', 'm6' => '6 месяцев', 'm3' => '3 месяца', 'm1' => '1 месяц', 'w2' => '2 недели'];

/** Витрины модуля: company_id => префикс Id новых объявлений */
const GM_AVITO_COMPANIES = [1 => 'mm', 2 => 'gm'];

/**
 * Минуты запуска по cron (crontab пользователя maxmobiles, держать в согласии): сборка файла – до того, как Авито его
 * заберёт (GOODMi – в :08 каждый час, Maxmobiles – в :47 по чётным часам), отчёт Авито – после загрузки
 */
const GM_AVITO_CRON = [2 => ['build' => 55, 'sync' => 30], 1 => ['build' => 35, 'sync' => 10]];

/**
 * Настройка витрины (cscart_gm_avito_settings)
 */
function fn_gm_avito_get_setting($company_id, $name, $default = null)
{
    $value = db_get_field('SELECT value FROM ?:gm_avito_settings WHERE company_id = ?i AND name = ?s', $company_id, $name);

    return $value === null || $value === false || $value === '' ? $default : $value;
}

function fn_gm_avito_set_setting($company_id, $name, $value)
{
    db_query('REPLACE INTO ?:gm_avito_settings ?e', ['company_id' => $company_id, 'name' => $name, 'value' => $value]);
}

/**
 * Текст для записи в базу: CS-Cart подключается в utf8 (3 байта), 4-байтовые символы (эмодзи) превращаются в «????» –
 * храним их числовыми ссылками &#N;
 */
function fn_gm_avito_db_text($text)
{
    $text = mb_convert_encoding((string) $text, 'UTF-8', 'UTF-8');

    return (string) preg_replace_callback('/[\x{10000}-\x{10FFFF}]/u', static function ($m) {
        return '&#' . mb_ord($m[0], 'UTF-8') . ';';
    }, (string) $text);
}

/**
 * Обратно для файла Авито и админки: числовые ссылки на 4-байтовые символы → сами символы (остальной текст не трогаем)
 */
function fn_gm_avito_xml_text($text)
{
    return preg_replace_callback('/&#(\d{5,7});/', static function ($m) {
        $code = (int) $m[1];

        return $code >= 0x10000 && $code <= 0x10FFFF ? mb_chr($code, 'UTF-8') : $m[0];
    }, (string) $text);
}

function fn_gm_avito_esc($text)
{
    return htmlspecialchars((string) $text, ENT_NOQUOTES, 'UTF-8');
}

/**
 * «Поле = значение» по строкам ↔ массив полей объявления (шаблоны и поля по умолчанию в админке)
 */
function fn_gm_avito_parse_fields($text)
{
    $fields = [];
    foreach (preg_split('/\R/u', (string) $text) as $line) {
        if (strpos($line, '=') === false) {
            continue;
        }
        list($tag, $value) = array_map('trim', explode('=', $line, 2));
        if ($tag !== '' && $value !== '' && preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $tag) && !in_array($tag, GM_AVITO_OWN_TAGS, true)) {
            $fields[$tag] = fn_gm_avito_db_text($value);
        }
    }

    return $fields;
}

function fn_gm_avito_fields_text($fields)
{
    $lines = [];
    foreach ((array) $fields as $tag => $value) {
        $lines[] = $tag . ' = ' . fn_gm_avito_xml_text($value);
    }

    return implode("\n", $lines);
}

/**
 * Путь к файлу автозагрузки витрины
 */
function fn_gm_avito_feed_path($company_id, $test = false)
{
    return rtrim(fn_get_files_dir_path($company_id), '/') . '/gm_avito/' . ($test ? 'feed-test.xml' : 'feed.xml');
}

function fn_gm_avito_site_url($company_id)
{
    return rtrim((string) fn_gm_avito_get_setting($company_id, 'site_url', 'https://' . db_get_field('SELECT url FROM ?:storefronts WHERE storefront_id = ?i', $company_id)), '/');
}

/**
 * Ссылка на файл для кабинета Авито (ключ создаётся при первом обращении)
 */
function fn_gm_avito_feed_url($company_id)
{
    $key = fn_gm_avito_get_setting($company_id, 'feed_key');
    if (!$key) {
        $key = bin2hex(random_bytes(16));
        fn_gm_avito_set_setting($company_id, 'feed_key', $key);
    }

    return fn_gm_avito_site_url($company_id) . '/index.php?dispatch=gm_avito.feed&key=' . $key;
}

/**
 * Цена товара для витрины: цена витрины (ult_product_prices), иначе общая цена (product_prices)
 */
function fn_gm_avito_product_price($product_id, $company_id)
{
    $price = db_get_field(
        'SELECT price FROM ?:ult_product_prices WHERE product_id = ?i AND company_id = ?i AND lower_limit = 1 AND usergroup_id = 0',
        $product_id,
        $company_id
    );
    if (!is_numeric($price)) {
        $price = db_get_field('SELECT price FROM ?:product_prices WHERE product_id = ?i AND lower_limit = 1 AND usergroup_id = 0', $product_id);
    }

    return (float) $price;
}

/**
 * Полные адреса фото товара: основное + дополнительные, не больше $limit
 */
function fn_gm_avito_product_images($product_id, $site_url, $limit)
{
    $urls = [];
    $main = fn_get_image_pairs($product_id, 'product', 'M', true, true, CART_LANGUAGE);
    $additional = fn_get_image_pairs($product_id, 'product', 'A', true, true, CART_LANGUAGE);
    foreach (array_merge($main ? [$main] : [], (array) $additional) as $pair) {
        if (!empty($pair['detailed']['relative_path'])) {
            $urls[] = rtrim($site_url, '/') . '/images/' . ltrim($pair['detailed']['relative_path'], '/');
        }
        if (count($urls) >= $limit) {
            break;
        }
    }

    return array_values(array_unique($urls));
}

/**
 * Главные категории товаров: product_id => id_path
 */
function fn_gm_avito_main_paths(array $product_ids)
{
    if (!$product_ids) {
        return [];
    }

    return db_get_hash_single_array(
        'SELECT pc.product_id, c.id_path FROM ?:products_categories AS pc'
        . ' INNER JOIN ?:categories AS c ON c.category_id = pc.category_id'
        . ' WHERE pc.product_id IN (?n) AND pc.link_type = ?s',
        ['product_id', 'id_path'],
        $product_ids,
        'M'
    );
}

/**
 * Шаблоны категорий витрины: category_id => строка с полями (fields – массив)
 */
function fn_gm_avito_templates($company_id)
{
    $templates = db_get_hash_array('SELECT * FROM ?:gm_avito_templates WHERE company_id = ?i', 'category_id', $company_id);
    foreach ($templates as &$template) {
        $template['fields'] = (array) json_decode((string) $template['fields'], true);
        $template['auto_fields'] = array_values(array_filter(explode(',', (string) $template['auto_fields'])));
        $template['required_fields'] = array_values(array_filter(array_map('trim', explode(',', (string) ($template['required_fields'] ?? '')))));
    }
    unset($template);

    return $templates;
}

/**
 * Шаблон для товара: ближайший по пути главной категории (сама категория, затем родители)
 */
function fn_gm_avito_template_for_path(array $templates, $id_path)
{
    foreach (array_reverse(explode('/', (string) $id_path)) as $category_id) {
        if (isset($templates[(int) $category_id])) {
            return $templates[(int) $category_id];
        }
    }

    return null;
}

/**
 * Характеристики товара, видимые в карточке: feature_id => [name, header (главная), values[]], главные – первыми
 */
function fn_gm_avito_product_features($product_id)
{
    $rows = db_get_array(
        'SELECT v.feature_id, fd.description AS name, fd.prefix, fd.suffix, f.display_on_header, vd.variant, v.value, v.value_int'
        . ' FROM ?:product_features_values AS v'
        . ' INNER JOIN ?:product_features AS f ON f.feature_id = v.feature_id AND f.status = ?s AND f.display_on_product = ?s'
        . ' INNER JOIN ?:product_features_descriptions AS fd ON fd.feature_id = v.feature_id AND fd.lang_code = ?s'
        . ' LEFT JOIN ?:product_feature_variant_descriptions AS vd ON vd.variant_id = v.variant_id AND vd.lang_code = ?s'
        . ' WHERE v.product_id = ?i AND v.lang_code = ?s'
        . ' ORDER BY f.display_on_header DESC, f.position, v.feature_id, v.variant_id, v.value',
        'A',
        'Y',
        'ru',
        'ru',
        $product_id,
        'ru'
    );

    $features = [];
    foreach ($rows as $row) {
        $value = trim((string) $row['variant']);
        if ($value === '') {
            $value = trim((string) $row['value']);
        }
        if ($value === '' && $row['value_int'] !== null && (float) $row['value_int'] != 0) {
            $value = rtrim(rtrim(number_format((float) $row['value_int'], 2, '.', ''), '0'), '.');
        }
        if ($value === '') {
            continue;
        }
        $prefix = trim((string) $row['prefix']);
        $suffix = trim((string) $row['suffix']);
        if ($prefix !== '' && mb_stripos($value, $prefix) !== 0) {
            $value = $prefix . ' ' . $value;
        }
        if ($suffix !== '' && mb_substr($value, -mb_strlen($suffix)) !== $suffix) {
            $value .= ' ' . $suffix;
        }
        $feature_id = (int) $row['feature_id'];
        if (!isset($features[$feature_id])) {
            $features[$feature_id] = ['name' => trim((string) $row['name']), 'header' => $row['display_on_header'] === 'Y', 'values' => []];
        }
        if (!in_array($value, $features[$feature_id]['values'], true)) {
            $features[$feature_id]['values'][] = $value;
        }
    }

    return $features;
}

/**
 * Значения характеристики строкой: не больше $limit, дальше «и др.» (у аксессуаров списки совместимых моделей – десятки)
 */
function fn_gm_avito_feature_text(array $feature, $limit = 5)
{
    $values = array_slice($feature['values'], 0, $limit);

    return implode(', ', $values) . (count($feature['values']) > $limit ? ' и др.' : '');
}

/**
 * Значение характеристики по первому найденному названию
 */
function fn_gm_avito_feature_value(array $features, array $names)
{
    foreach ($names as $name) {
        foreach ($features as $feature) {
            if (mb_strtolower($feature['name']) === mb_strtolower($name)) {
                return implode(', ', $feature['values']);
            }
        }
    }

    return '';
}

/**
 * Заголовок по названию товара: до 50 знаков (без кода модели в скобках, затем по границе слова)
 */
function fn_gm_avito_auto_title($name)
{
    $title = trim(preg_replace('/\s+/u', ' ', (string) $name));
    if (mb_strlen($title) > 50) {
        $title = trim(preg_replace('/\s*\([^)]*\)/u', '', $title));
    }
    if (mb_strlen($title) > 50) {
        $cut = mb_substr($title, 0, 51);
        $space = mb_strrpos($cut, ' ');
        $title = preg_replace('/[\s,;:–-]+$/u', '', mb_substr($title, 0, $space ?: 50));
    }

    return $title;
}

/**
 * Вступление «о товаре»: до трёх предложений из описания сайта (без предложений про магазин, доставку, город и т.п.),
 * если подходящих нет – из названия и главных характеристик
 */
function fn_gm_avito_auto_intro($product_id, array $features = null)
{
    $row = db_get_row('SELECT product, full_description FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s', $product_id, 'ru');
    $name = trim((string) ($row['product'] ?? ''));
    $html = preg_replace('~<(script|style)\b.*?</\1>~isu', ' ', (string) ($row['full_description'] ?? ''));
    $html = preg_replace('~<(br|/p|/div|/li|/h[1-6]|/tr|/td)\b[^>]*>~iu', "\n", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\xc2\xa0", '—'], [' ', '–'], $text);

    $sentences = [];
    foreach (preg_split('/\n+/u', $text) as $line) {
        $line = trim(preg_replace('/\s+/u', ' ', $line));
        if (mb_strlen($line) < 40 || mb_substr($line, -1) === ':') {
            continue;
        }
        foreach (preg_split('/(?<=[.!?…])\s+(?=[А-ЯЁA-Z0-9«"])/u', $line) as $sentence) {
            $sentence = trim($sentence);
            if (mb_strlen($sentence) < 30 || mb_strlen($sentence) > 400 || preg_match(GM_AVITO_STOP_WORDS, $sentence)) {
                continue;
            }
            if (!preg_match('/[.!?…]$/u', $sentence)) {
                $sentence .= '.';
            }
            $sentences[] = $sentence;
        }
    }

    $intro = '';
    foreach ($sentences as $i => $sentence) {
        if ($intro !== '' && mb_strlen($intro) + mb_strlen($sentence) > 600) {
            break;
        }
        $intro .= ($intro === '' ? '' : ' ') . $sentence;
        if ($i >= 2 || mb_strlen($intro) >= 350) {
            break;
        }
    }
    if ($intro !== '') {
        return $intro;
    }

    $features = $features ?? fn_gm_avito_product_features($product_id);
    $main = [];
    foreach ($features as $feature) {
        if ($feature['header'] && !in_array($feature['name'], GM_AVITO_SPECS_SKIP, true)) {
            $main[] = mb_strtolower(mb_substr($feature['name'], 0, 1)) . mb_substr($feature['name'], 1) . ' ' . fn_gm_avito_feature_text($feature, 3);
        }
        if (count($main) >= 3) {
            break;
        }
    }

    return $name . ' – новый товар.' . ($main ? ' Главное: ' . implode('; ', $main) . '.' : '');
}

/**
 * Срок гарантии товара (только GOODMi – по категориям модуля gm_delivery_date), '' – неизвестен
 */
function fn_gm_avito_warranty($company_id, $product_id)
{
    if ((int) $company_id !== 2 || !function_exists('fn_gm_delivery_date_warranty_codes')) {
        return '';
    }
    $codes = fn_gm_delivery_date_warranty_codes([$product_id]);

    return isset($codes[$product_id]) && array_key_exists($codes[$product_id], GM_AVITO_WARRANTY_LABELS) ? GM_AVITO_WARRANTY_LABELS[$codes[$product_id]] : '';
}

/**
 * Новое описание: «о товаре» (абзацы по строкам) + характеристики + общий блок витрины
 */
function fn_gm_avito_compose_description($company_id, $product_id, $intro, array $features = null)
{
    $features = $features ?? fn_gm_avito_product_features($product_id);
    $html = '';
    foreach (preg_split('/\R+/u', trim(fn_gm_avito_xml_text($intro))) as $paragraph) {
        if (trim($paragraph) !== '') {
            $html .= '<p>' . fn_gm_avito_esc(trim($paragraph)) . '</p>';
        }
    }

    $limit = (int) fn_gm_avito_get_setting($company_id, 'specs_limit', 10);
    $specs = [];
    foreach ($features as $feature) {
        if (in_array($feature['name'], GM_AVITO_SPECS_SKIP, true)) {
            continue;
        }
        $specs[] = '<li>' . fn_gm_avito_esc($feature['name'] . ': ' . fn_gm_avito_feature_text($feature)) . '</li>';
        if (count($specs) >= $limit) {
            break;
        }
    }
    $specs_html = $specs ? '<p><strong>Характеристики</strong></p><ul>' . implode('', $specs) . '</ul>' : '';

    $common = fn_gm_avito_xml_text((string) fn_gm_avito_get_setting($company_id, 'common_block', ''));
    $warranty = fn_gm_avito_warranty($company_id, $product_id);
    if ($warranty === '') {
        $common = preg_replace('~<li>(?:(?!</li>).)*\{warranty\}(?:(?!</li>).)*</li>~su', '', $common);
    }
    $common = str_replace('{warranty}', $warranty, $common);

    $description = $html . $specs_html . $common;
    if (mb_strlen($description) > 7500) {
        $description = $html . $common;
    }

    return $description;
}

/**
 * Новая строка товара для cscart_gm_avito_items: выключен, заголовок и «о товаре» – автоматически
 */
function fn_gm_avito_new_item($company_id, $product_id, array $features = null)
{
    $name = (string) db_get_field('SELECT product FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s', $product_id, 'ru');

    return [
        'company_id' => $company_id,
        'product_id' => $product_id,
        'ad_id' => GM_AVITO_COMPANIES[$company_id] . $company_id . '-' . $product_id,
        'enabled' => 'N',
        'title' => fn_gm_avito_db_text(fn_gm_avito_auto_title($name)),
        'title_auto' => 'Y',
        'intro' => fn_gm_avito_db_text(fn_gm_avito_auto_intro($product_id, $features)),
        'intro_auto' => 'Y',
        'description' => '',
        'fields' => '{}',
        'updated_at' => TIME,
    ];
}

/**
 * Строка товара; $create – создать, если её ещё нет
 */
function fn_gm_avito_get_item($company_id, $product_id, $create = false)
{
    $item = db_get_row('SELECT * FROM ?:gm_avito_items WHERE company_id = ?i AND product_id = ?i', $company_id, $product_id);
    if (!$item && $create && array_key_exists((int) $company_id, GM_AVITO_COMPANIES)) {
        $item = fn_gm_avito_new_item($company_id, $product_id);
        db_query('INSERT INTO ?:gm_avito_items ?e', $item);
    }

    return $item ?: null;
}

/**
 * Заполнить строки всех включённых на сайте товаров витрины: новые – создать (выключенными), у остальных
 * обновить «о товаре», если оно автоматическое (правленое вручную не трогаем). Возвращает счётчики.
 */
function fn_gm_avito_fill_items($company_id)
{
    $stats = ['created' => 0, 'updated' => 0, 'same' => 0, 'manual' => 0];
    $product_ids = db_get_fields('SELECT product_id FROM ?:products WHERE company_id = ?i AND status = ?s', $company_id, 'A');
    $existing = db_get_hash_array('SELECT product_id, intro, intro_auto FROM ?:gm_avito_items WHERE company_id = ?i', 'product_id', $company_id);
    foreach ($product_ids as $product_id) {
        $product_id = (int) $product_id;
        $row = $existing[$product_id] ?? null;
        if ($row && $row['intro_auto'] === 'N' && (string) $row['intro'] !== '') {
            $stats['manual']++;
            continue;
        }
        $features = fn_gm_avito_product_features($product_id);
        if (!$row) {
            db_query('INSERT INTO ?:gm_avito_items ?e', fn_gm_avito_new_item($company_id, $product_id, $features));
            $stats['created']++;
            continue;
        }
        $intro = fn_gm_avito_db_text(fn_gm_avito_auto_intro($product_id, $features));
        if ($intro === (string) $row['intro']) {
            $stats['same']++;
            continue;
        }
        db_query(
            'UPDATE ?:gm_avito_items SET ?u WHERE company_id = ?i AND product_id = ?i',
            ['intro' => $intro, 'intro_auto' => 'Y', 'updated_at' => TIME],
            $company_id,
            $product_id
        );
        $stats['updated']++;
    }

    return $stats;
}

/**
 * Объявление товара целиком (как попадёт в файл) или причина, по которой не попадёт.
 * $context – общие данные сборки (настройки, шаблоны), чтобы не читать их на каждый товар.
 */
function fn_gm_avito_prepare_ad($company_id, array $item, array $context)
{
    $product_id = (int) $item['product_id'];
    $product = db_get_row('SELECT status, amount FROM ?:products WHERE product_id = ?i', $product_id);
    $path = $context['paths'][$product_id] ?? db_get_field(
        'SELECT c.id_path FROM ?:products_categories AS pc INNER JOIN ?:categories AS c ON c.category_id = pc.category_id'
        . ' WHERE pc.product_id = ?i AND pc.link_type = ?s',
        $product_id,
        'M'
    );
    $template = fn_gm_avito_template_for_path($context['templates'], $path);
    $features = fn_gm_avito_product_features($product_id);

    // Перенесённые из UnitXML объявления (свои поля с категорией Авито) – поля ровно как были, шаблон и поля по умолчанию
    // к ним не примешиваются: иначе в объявление попадут чужие подтипы (смешанные категории) и характеристики
    $own_fields = (array) json_decode((string) $item['fields'], true);
    if (!empty($own_fields['Category'])) {
        $fields = $own_fields;
    } else {
        $fields = $context['defaults'];
        if ($template) {
            $fields = array_merge($fields, $template['fields']);
            foreach ($template['auto_fields'] as $tag) {
                $value = array_key_exists($tag, GM_AVITO_FEATURE_FIELDS) ? fn_gm_avito_feature_value($features, GM_AVITO_FEATURE_FIELDS[$tag]) : '';
                if ($value !== '') {
                    $fields[$tag] = $value;
                }
            }
        }
        $fields = array_merge($fields, $own_fields);
    }

    $category_ids = array_map('intval', explode('/', (string) $path));
    $new_text = (string) $item['description'] === ''
        || $context['text_mode'] === 'new'
        || ($context['text_mode'] === 'categories' && array_intersect($category_ids, $context['new_text_categories']));
    $intro = (string) $item['intro'] !== '' ? $item['intro'] : fn_gm_avito_auto_intro($product_id, $features);

    $ad = [
        'id' => $item['ad_id'],
        'fields' => $fields,
        'title' => fn_gm_avito_xml_text($item['title']),
        'description' => $new_text ? fn_gm_avito_compose_description($company_id, $product_id, $intro, $features) : fn_gm_avito_xml_text($item['description']),
        'new_text' => (bool) $new_text,
        'price' => fn_gm_avito_product_price($product_id, $company_id),
        'images' => fn_gm_avito_product_images($product_id, $context['site_url'], $context['images_limit']),
        'template' => $template,
        'amount' => (int) ($product['amount'] ?? 0),
        'missing' => [],
        'skip' => '',
    ];
    if ($template && empty($own_fields['Category'])) {
        foreach ($template['required_fields'] as $tag) {
            if (!isset($fields[$tag]) || trim((string) $fields[$tag]) === '') {
                $ad['missing'][] = $tag;
            }
        }
    }

    if (fn_gm_avito_path_skipped($context, $path)) {
        $ad['skip'] = 'категория не выгружается на Авито (настройки)';
    } elseif (empty($product) || $product['status'] !== 'A') {
        $ad['skip'] = 'товар выключен или скрыт на сайте';
    } elseif ($context['stock_mode'] === 'in_stock' && $ad['amount'] <= 0) {
        $ad['skip'] = 'нет в наличии';
    } elseif ($context['stock_mode'] === 'out_of_stock' && $ad['amount'] > 0) {
        $ad['skip'] = 'есть в наличии (выгружаются только товары без остатка)';
    } elseif ($ad['price'] <= 0) {
        $ad['skip'] = 'нет цены';
    } elseif (!$ad['images']) {
        $ad['skip'] = 'нет фото';
    } elseif (empty($fields['Category'])) {
        $ad['skip'] = 'нет шаблона Авито для категории товара';
    } elseif ($ad['missing']) {
        $ad['skip'] = 'не заполнены обязательные поля Авито: ' . implode(', ', $ad['missing']);
    } elseif ($ad['title'] === '') {
        $ad['skip'] = 'нет заголовка';
    }

    return $ad;
}

/**
 * Общие данные сборки витрины
 */
function fn_gm_avito_context($company_id, array $product_ids = [])
{
    return [
        'site_url' => fn_gm_avito_site_url($company_id),
        'images_limit' => max(1, min(10, (int) fn_gm_avito_get_setting($company_id, 'images_limit', 10))),
        'defaults' => (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'defaults', '{}'), true),
        'stock_mode' => (string) fn_gm_avito_get_setting($company_id, 'stock_mode', 'all'),
        'text_mode' => (string) fn_gm_avito_get_setting($company_id, 'text_mode', 'old'),
        'new_text_categories' => array_values(array_filter(array_map('intval', explode(',', (string) fn_gm_avito_get_setting($company_id, 'new_text_categories', ''))))),
        'templates' => fn_gm_avito_templates($company_id),
        'paths' => fn_gm_avito_main_paths($product_ids),
        'skip_categories' => array_values(array_filter(array_map('intval', explode(',', (string) fn_gm_avito_get_setting($company_id, 'skip_categories', ''))))),
    ];
}

/**
 * Категория товара (или её родитель) в списке «не выгружаются на Авито» (настройка skip_categories: сервисный центр,
 * услуги, уценка и т.п. – шаблонов у них нет намеренно)
 */
function fn_gm_avito_path_skipped(array $context, $id_path)
{
    return (bool) array_intersect(array_map('intval', explode('/', (string) $id_path)), $context['skip_categories'] ?? []);
}

/**
 * Собирает файл автозагрузки витрины. Возвращает ['ads' => число объявлений, 'skipped' => [product_id => причина],
 * 'reasons' => [причина => сколько], 'path' => файл].
 * $test – пробный файл для проверки шаблонов на autoload.avito.ru/format/xmlcheck (feed-test.xml, на Авито не подключается):
 * по $test товаров в наличии из каждой категории с шаблоном, включены они или нет (перенесённые из UnitXML – не берутся).
 */
function fn_gm_avito_build_feed($company_id, $test = 0)
{
    $path = fn_gm_avito_feed_path($company_id, (bool) $test);
    fn_mkdir(dirname($path));
    $tmp = $path . '.tmp';

    $xml = new XMLWriter();
    $xml->openUri($tmp);
    $xml->setIndent(true);
    $xml->startDocument('1.0', 'UTF-8');
    $xml->startElement('Ads');
    $xml->writeAttribute('formatVersion', '3');
    $xml->writeAttribute('target', 'Avito.ru');

    $result = ['ads' => 0, 'skipped' => [], 'reasons' => [], 'path' => $path];
    if ($test) {
        $items = db_get_array(
            'SELECT i.* FROM ?:gm_avito_items AS i INNER JOIN ?:products AS p ON p.product_id = i.product_id'
            . ' WHERE i.company_id = ?i AND p.status = ?s AND p.amount > 0 AND i.fields NOT LIKE ?l ORDER BY i.product_id DESC',
            $company_id,
            'A',
            '%"Category"%'
        );
    } else {
        $items = db_get_array('SELECT * FROM ?:gm_avito_items WHERE company_id = ?i AND enabled = ?s ORDER BY ad_id', $company_id, 'Y');
    }
    $context = fn_gm_avito_context($company_id, array_column($items, 'product_id'));
    $per_template = [];
    foreach ($items as $item) {
        if ($test) {
            $template = fn_gm_avito_template_for_path($context['templates'], $context['paths'][(int) $item['product_id']] ?? '');
            if (!$template || ($per_template[$template['category_id']] ?? 0) >= $test) {
                continue;
            }
        }
        $ad = fn_gm_avito_prepare_ad($company_id, $item, $context);
        if ($test && $ad['skip'] === '') {
            $per_template[$template['category_id']] = ($per_template[$template['category_id']] ?? 0) + 1;
        }
        if ($ad['skip'] !== '') {
            $result['skipped'][(int) $item['product_id']] = $ad['skip'];
            $result['reasons'][$ad['skip']] = ($result['reasons'][$ad['skip']] ?? 0) + 1;
            continue;
        }

        $xml->startElement('Ad');
        $xml->writeElement('Id', $ad['id']);
        foreach ($ad['fields'] as $tag => $value) {
            if (in_array($tag, GM_AVITO_OWN_TAGS, true) || !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', (string) $tag)) {
                continue;
            }
            $xml->writeElement($tag, fn_gm_avito_xml_text($value));
        }
        $xml->writeElement('Title', $ad['title']);
        $xml->startElement('Description');
        $xml->writeCdata(str_replace(']]>', ']]&gt;', fn_gm_avito_xml_text($ad['description'])));
        $xml->endElement();
        $xml->writeElement('Price', (string) (floor($ad['price']) == $ad['price'] ? (int) $ad['price'] : $ad['price']));
        $xml->startElement('Images');
        foreach ($ad['images'] as $url) {
            $xml->startElement('Image');
            $xml->writeAttribute('url', $url);
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();
        $result['ads']++;
    }

    $xml->endElement();
    $xml->endDocument();
    $xml->flush();
    chmod($tmp, 0644);
    rename($tmp, $path);
    if ($test) {
        return $result;
    }
    fn_gm_avito_set_setting($company_id, 'last_build', json_encode([
        'time' => TIME, 'ads' => $result['ads'], 'skipped' => count($result['skipped']), 'reasons' => $result['reasons'],
    ], JSON_UNESCAPED_UNICODE));

    return $result;
}

/**
 * Хук сохранения товара в админке: вкладка «Авито» (product_data[gm_avito]); без неё (обмен, API, массовая правка) – ничего
 */
function fn_gm_avito_update_product_post($product_data, $product_id, $lang_code, $create)
{
    if (empty($product_data['gm_avito']) || !is_array($product_data['gm_avito'])) {
        return;
    }
    $data = $product_data['gm_avito'];
    $company_id = (int) db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
    $item = fn_gm_avito_get_item($company_id, (int) $product_id, true);
    if (!$item) {
        return;
    }

    $update = ['enabled' => ($data['enabled'] ?? 'N') === 'Y' ? 'Y' : 'N', 'updated_at' => TIME];
    if (($data['reset_auto'] ?? '') === 'Y') {
        $auto = fn_gm_avito_new_item($company_id, (int) $product_id);
        $update += ['title' => $auto['title'], 'title_auto' => 'Y', 'intro' => $auto['intro'], 'intro_auto' => 'Y'];
    } else {
        if (isset($data['title'])) {
            $title = trim(preg_replace('/\s+/u', ' ', (string) $data['title']));
            if ($title !== '' && $title !== fn_gm_avito_xml_text($item['title'])) {
                $update += ['title' => fn_gm_avito_db_text($title), 'title_auto' => 'N'];
            }
        }
        if (isset($data['fields'])) {
            // Сравниваем без пустых значений: у перенесённых из UnitXML бывают пустые поля, в форме их не видно –
            // сохранение товара без правок не должно менять объявление
            $new_fields = fn_gm_avito_parse_fields($data['fields']);
            $old_fields = array_filter((array) json_decode((string) $item['fields'], true), static function ($value) {
                return trim((string) $value) !== '';
            });
            if ($new_fields != $old_fields) {
                $update['fields'] = $new_fields ? json_encode($new_fields, JSON_UNESCAPED_UNICODE) : '{}';
            }
        }
        if (isset($data['intro'])) {
            $intro = trim(str_replace("\r", '', strip_tags((string) $data['intro'])));
            if ($intro !== '' && $intro !== fn_gm_avito_xml_text($item['intro'])) {
                $update += ['intro' => fn_gm_avito_db_text($intro), 'intro_auto' => 'N'];
            }
        }
    }
    db_query('UPDATE ?:gm_avito_items SET ?u WHERE company_id = ?i AND product_id = ?i', $update, $company_id, $product_id);
}

/**
 * Данные вкладки «Авито» в карточке товара
 */
function fn_gm_avito_admin_item($company_id, $product_id)
{
    $item = fn_gm_avito_get_item($company_id, $product_id, true);
    if (!$item) {
        return null;
    }
    $ad = fn_gm_avito_prepare_ad($company_id, $item, fn_gm_avito_context($company_id));
    $stock_labels = ['in_stock' => 'только товары с остатком', 'out_of_stock' => 'только товары без остатка', 'all' => 'все включённые товары'];
    $stock_mode = (string) fn_gm_avito_get_setting($company_id, 'stock_mode', 'all');

    if ($item['enabled'] !== 'Y') {
        $state = 'Не продаётся на Авито.' . ($ad['skip'] !== '' ? ' Если включить, в файл пока не попадёт: ' . $ad['skip'] . '.' : '');
    } elseif ($ad['skip'] !== '') {
        $state = 'Включено, но в файл сейчас не попадает: ' . $ad['skip'] . '.';
    } else {
        $state = 'Включено, объявление попадает в файл.';
    }

    $category = [];
    foreach (['Category', 'GoodsType', 'GoodsSubType', 'ProductType', 'ProductsType', 'ProductSubType', 'AccessoryType'] as $tag) {
        if (!empty($ad['fields'][$tag])) {
            $category[] = fn_gm_avito_xml_text($ad['fields'][$tag]);
        }
    }

    return [
        'company_id' => $company_id,
        'enabled' => $item['enabled'],
        'ad_id' => $item['ad_id'],
        'avito_id' => $item['avito_id'],
        'title' => fn_gm_avito_xml_text($item['title']),
        'title_len' => mb_strlen(fn_gm_avito_xml_text($item['title'])),
        'title_auto' => $item['title_auto'],
        'intro' => fn_gm_avito_xml_text($item['intro']),
        'intro_auto' => $item['intro_auto'],
        'state' => $state,
        'stock_mode' => $stock_labels[$stock_mode] ?? $stock_mode,
        'avito_category' => $category ? implode(' / ', $category) : 'нет шаблона для категории товара',
        'own_fields' => fn_gm_avito_fields_text((array) json_decode((string) $item['fields'], true)),
        'imported' => !empty(((array) json_decode((string) $item['fields'], true))['Category']),
        'template_fields' => $ad['template'] ? fn_gm_avito_fields_text($ad['template']['fields']) : '',
        'template_auto' => $ad['template'] ? implode(', ', $ad['template']['auto_fields']) : '',
        'required' => $ad['template'] ? implode(', ', $ad['template']['required_fields']) : '',
        'missing' => implode(', ', $ad['missing']),
        'report' => (array) json_decode(fn_gm_avito_xml_text((string) $item['status_text']), true),
        'report_status' => $item['status'],
        'new_text' => $ad['new_text'],
        'preview' => $ad['description'],
        'price' => $ad['price'],
        'images' => count($ad['images']),
    ];
}

/**
 * Проверка для страницы «Авито: проблемы»: включённые товары, которые не попадают в файл (причина), длинные заголовки,
 * у всех включённых на сайте товаров – категории без шаблона и незаполненные обязательные поля раздела Авито.
 * Результат – настройка check (JSON), её показывает gm_avito.problems; запускается после сборки файла и по кнопке.
 */
function fn_gm_avito_check($company_id)
{
    $rows = db_get_array(
        'SELECT p.product_id, p.amount, pd.product, i.enabled, i.title, i.fields FROM ?:products AS p'
        . ' INNER JOIN ?:product_descriptions AS pd ON pd.product_id = p.product_id AND pd.lang_code = ?s'
        . ' LEFT JOIN ?:gm_avito_items AS i ON i.product_id = p.product_id AND i.company_id = ?i'
        . ' WHERE p.company_id = ?i AND p.status = ?s',
        'ru',
        $company_id,
        $company_id,
        'A'
    );
    $context = fn_gm_avito_context($company_id, array_column($rows, 'product_id'));
    $names = db_get_hash_single_array(
        'SELECT c.category_id, cd.category FROM ?:categories AS c INNER JOIN ?:category_descriptions AS cd ON cd.category_id = c.category_id AND cd.lang_code = ?s WHERE c.company_id = ?i',
        ['category_id', 'category'],
        'ru',
        $company_id
    );
    $path_name = static function ($path) use ($names) {
        return implode(' / ', array_map(static function ($id) use ($names) {
            return $names[$id] ?? $id;
        }, explode('/', (string) $path)));
    };

    $check = [
        'time' => TIME, 'products' => count($rows), 'enabled' => 0, 'in_file' => 0, 'reasons' => [],
        'enabled_problems' => [], 'long_titles' => [], 'missing' => [], 'no_template' => [], 'by_category' => [],
    ];
    foreach ($rows as $row) {
        $product_id = (int) $row['product_id'];
        $path = (string) ($context['paths'][$product_id] ?? '');
        $main = (int) substr(strrchr('/' . $path, '/'), 1);
        $own = (array) json_decode((string) $row['fields'], true);
        $in_stock = (int) $row['amount'] > 0;
        if (fn_gm_avito_path_skipped($context, $path) && $row['enabled'] !== 'Y') {
            $check['skipped_by_settings'] = ($check['skipped_by_settings'] ?? 0) + 1;
            continue;
        }

        // Сколько товаров категории продаётся на Авито и сколько нет (в том числе в наличии)
        if (!isset($check['by_category'][$main])) {
            $check['by_category'][$main] = [
                'path' => $path_name($path), 'total' => 0, 'in_stock' => 0, 'enabled' => 0, 'off' => 0, 'off_in_stock' => 0,
                'template' => (bool) fn_gm_avito_template_for_path($context['templates'], $path),
            ];
        }
        $by = &$check['by_category'][$main];
        $by['total']++;
        $by['in_stock'] += $in_stock ? 1 : 0;
        if ($row['enabled'] === 'Y') {
            $by['enabled']++;
        } else {
            $by['off']++;
            $by['off_in_stock'] += $in_stock ? 1 : 0;
        }
        unset($by);

        if ($row['enabled'] === 'Y') {
            $check['enabled']++;
            $ad = fn_gm_avito_prepare_ad($company_id, db_get_row('SELECT * FROM ?:gm_avito_items WHERE company_id = ?i AND product_id = ?i', $company_id, $product_id), $context);
            if ($ad['skip'] === '') {
                $check['in_file']++;
            } else {
                $check['reasons'][$ad['skip']] = ($check['reasons'][$ad['skip']] ?? 0) + 1;
                $check['enabled_problems'][] = [$product_id, $row['product'], $ad['skip']];
            }
            $title_len = mb_strlen(fn_gm_avito_xml_text($row['title']));
            if ($title_len > 50) {
                $check['long_titles'][] = [$product_id, $row['product'], fn_gm_avito_xml_text($row['title']), $title_len];
            }
            continue;
        }
        if (!empty($own['Category'])) {
            continue;
        }

        $template = fn_gm_avito_template_for_path($context['templates'], $path);
        if (!$template) {
            $key = $main;
            if (!isset($check['no_template'][$key])) {
                $check['no_template'][$key] = ['path' => $path_name($path), 'count' => 0, 'in_stock' => 0];
            }
            $check['no_template'][$key]['count']++;
            $check['no_template'][$key]['in_stock'] += $in_stock ? 1 : 0;
            continue;
        }
        if (!$template['required_fields']) {
            continue;
        }
        $fields = array_merge($context['defaults'], $template['fields'], $own);
        $features = null;
        $missing = [];
        foreach ($template['required_fields'] as $tag) {
            if (isset($fields[$tag]) && trim((string) $fields[$tag]) !== '') {
                continue;
            }
            if (in_array($tag, $template['auto_fields'], true) && array_key_exists($tag, GM_AVITO_FEATURE_FIELDS)) {
                $features = $features ?? fn_gm_avito_product_features($product_id);
                if (fn_gm_avito_feature_value($features, GM_AVITO_FEATURE_FIELDS[$tag]) !== '') {
                    continue;
                }
            }
            $missing[] = $tag;
        }
        if (!$missing) {
            continue;
        }
        $key = (int) $template['category_id'];
        if (!isset($check['missing'][$key])) {
            $check['missing'][$key] = ['path' => $path_name(db_get_field('SELECT id_path FROM ?:categories WHERE category_id = ?i', $key)), 'count' => 0, 'in_stock' => 0, 'fields' => [], 'examples' => []];
        }
        $check['missing'][$key]['count']++;
        $check['missing'][$key]['in_stock'] += $in_stock ? 1 : 0;
        foreach ($missing as $tag) {
            $check['missing'][$key]['fields'][$tag] = ($check['missing'][$key]['fields'][$tag] ?? 0) + 1;
        }
        if (count($check['missing'][$key]['examples']) < 15 && $in_stock) {
            $check['missing'][$key]['examples'][] = [$product_id, $row['product'], implode(', ', $missing)];
        }
    }

    arsort($check['reasons']);
    uasort($check['missing'], static function ($a, $b) {
        return $b['in_stock'] - $a['in_stock'];
    });
    uasort($check['no_template'], static function ($a, $b) {
        return $b['in_stock'] - $a['in_stock'];
    });
    uasort($check['by_category'], static function ($a, $b) {
        return [$b['off_in_stock'], $b['off']] <=> [$a['off_in_stock'], $a['off']];
    });
    fn_gm_avito_set_setting($company_id, 'check', json_encode($check, JSON_UNESCAPED_UNICODE));

    return $check;
}

/**
 * API Авито (этап 3, 2026-10-19). Ключи client_id/client_secret витрины – в закрытом файле вне папки сайта
 * (<папка пользователя сайта>/private/gm_avito.json, {"<company_id>": {...}}), в базу и репозиторий не попадают;
 * токен (живёт сутки) хранится в настройке api_token.
 */
function fn_gm_avito_api_credentials($company_id)
{
    $file = dirname(rtrim(DIR_ROOT, '/'), 2) . '/private/gm_avito.json';
    $all = is_readable($file) ? (array) json_decode((string) file_get_contents($file), true) : [];

    return $all[(string) $company_id] ?? null;
}

function fn_gm_avito_api_request($method, $url, array $headers = [], $body = null)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [$code, $response === false ? '' : (string) $response, $error];
}

/**
 * Токен API витрины (client_credentials), из настройки, пока не истёк
 */
function fn_gm_avito_api_token($company_id)
{
    $cached = (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'api_token', '{}'), true);
    if (!empty($cached['token']) && (int) ($cached['exp'] ?? 0) > TIME + 300) {
        return $cached['token'];
    }
    $credentials = fn_gm_avito_api_credentials($company_id);
    if (!$credentials) {
        throw new Exception('нет ключей API Авито для витрины ' . $company_id);
    }
    list($code, $response, $error) = fn_gm_avito_api_request('POST', 'https://api.avito.ru/token', ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $credentials['client_id'],
        'client_secret' => $credentials['client_secret'],
    ]));
    $data = (array) json_decode($response, true);
    if ($code !== 200 || empty($data['access_token'])) {
        throw new Exception('токен Авито: ответ ' . $code . ' ' . $error);
    }
    fn_gm_avito_set_setting($company_id, 'api_token', json_encode(['token' => $data['access_token'], 'exp' => TIME + (int) $data['expires_in']]));

    return $data['access_token'];
}

/**
 * GET к API Авито (только чтение), ответ – массив
 */
function fn_gm_avito_api_get($company_id, $path)
{
    // Авито ограничивает частоту (429): пауза между запросами и до трёх повторов с ожиданием
    static $last = 0.0;
    for ($attempt = 0; $attempt < 4; $attempt++) {
        $wait = 1.5 - (microtime(true) - $last);
        if ($wait > 0) {
            usleep((int) ($wait * 1000000));
        }
        list($code, $response, $error) = fn_gm_avito_api_request('GET', 'https://api.avito.ru' . $path, ['Authorization: Bearer ' . fn_gm_avito_api_token($company_id)]);
        $last = microtime(true);
        if ($code !== 429 && $code !== 0 && $code < 500) {
            break;
        }
        sleep(10 * ($attempt + 1));
    }
    if ($code !== 200) {
        throw new Exception('Авито ' . strtok($path, '?') . ': ответ ' . $code . ' ' . $error . ' ' . mb_substr($response, 0, 200));
    }

    return (array) json_decode($response, true);
}

/**
 * Отчёт о последней обработанной загрузке (текущая, если готова, иначе последняя успешная): сводка → настройка report; объявления из разделов с проблемами (всё, кроме
 * «успешно») – по разделам (постраничная выдача всего отчёта у Авито нестабильна: страницы перекрываются); номера
 * объявлений на Авито – /autoload/v2/items/avito_ids пачками по 100. В items: avito_id, status = раздел отчёта
 * (successful – есть номер и нет замечаний), status_text = JSON: раздел, статус на Авито, ссылка, срок, ошибки и
 * предупреждения. Возвращает сводку.
 */
function fn_gm_avito_sync_report($company_id)
{
    // Текущая загрузка, если уже обработана (last_successful у Авито отстаёт: 20.10 отдавал вчерашнюю при готовой ночной),
    // иначе – последняя успешная
    $base = 'current';
    $summary = fn_gm_avito_api_get($company_id, '/autoload/v4/uploads/current');
    if (in_array((string) ($summary['status'] ?? ''), ['processing', 'new', 'in_progress', ''], true)) {
        $base = 'last_successful';
        $summary = fn_gm_avito_api_get($company_id, '/autoload/v4/uploads/last_successful');
    }
    $profile = fn_gm_avito_api_get($company_id, '/autoload/v2/profile');
    fn_gm_avito_set_setting($company_id, 'profile', json_encode([
        'time' => TIME,
        'autoload_enabled' => !empty($profile['autoload_enabled']),
        'feeds' => array_values(array_filter(array_map(static function ($feed) {
            return (string) ($feed['feed_url'] ?? '');
        }, (array) ($profile['feeds_data'] ?? [])))),
        'schedule' => (array) ($profile['schedule'] ?? []),
    ], JSON_UNESCAPED_UNICODE));
    $by_ad = [];
    foreach ((array) ($summary['stats']['sections'] ?? []) as $section) {
        $slug = (string) ($section['slug'] ?? '');
        if ($slug === '' || $slug === 'successful' || (int) ($section['count'] ?? 0) === 0) {
            continue;
        }
        $page = 1;
        do {
            $data = fn_gm_avito_api_get($company_id, '/autoload/v4/uploads/' . $base . '/items?sections=' . rawurlencode($slug) . '&page=' . $page);
            foreach ((array) ($data['items'] ?? []) as $row) {
                $messages = [];
                foreach ((array) ($row['messages'] ?? []) as $message) {
                    if (in_array($message['type'] ?? '', ['error', 'alarm'], true)) {
                        $messages[] = [
                            'type' => $message['type'],
                            'title' => trim((string) ($message['title'] ?? '')),
                            'text' => trim(html_entity_decode(strip_tags((string) ($message['description'] ?? '')), ENT_QUOTES, 'UTF-8')),
                        ];
                    }
                }
                $by_ad[(string) $row['ad_id']] = [
                    'avito_id' => (int) ($row['avito_id'] ?? 0),
                    'status' => (string) ($row['section']['slug'] ?? $slug),
                    'info' => [
                        'section' => (string) ($row['section']['title'] ?? $section['title'] ?? ''),
                        'avito_status' => (string) ($row['avito_status'] ?? ''),
                        'url' => (string) ($row['url'] ?? ''),
                        'date_end' => substr((string) ($row['avito_date_end'] ?? ''), 0, 10),
                        'messages' => $messages,
                    ],
                ];
            }
            $pages = (int) ($data['meta']['pages'] ?? 1);
            $page++;
        } while ($page <= $pages && $page <= 50);
    }

    $items = db_get_hash_single_array('SELECT ad_id, product_id FROM ?:gm_avito_items WHERE company_id = ?i AND (enabled = ?s OR avito_id IS NOT NULL)', ['ad_id', 'product_id'], $company_id, 'Y');
    $avito_ids = [];
    foreach (array_chunk(array_keys($items), 100) as $chunk) {
        $data = fn_gm_avito_api_get($company_id, '/autoload/v2/items/avito_ids?query=' . rawurlencode(implode(',', $chunk)));
        foreach ((array) ($data['items'] ?? []) as $row) {
            if (!empty($row['avito_id'])) {
                $avito_ids[(string) $row['ad_id']] = (int) $row['avito_id'];
            }
        }
    }

    $stats = ['problems' => 0, 'published' => 0, 'none' => 0];
    foreach ($items as $ad_id => $product_id) {
        $ad_id = (string) $ad_id;
        if (isset($by_ad[$ad_id])) {
            $row = $by_ad[$ad_id];
            $avito_id = $row['avito_id'] ?: ($avito_ids[$ad_id] ?? 0);
            $status = $row['status'];
            $info = $row['info'];
            $stats['problems']++;
        } elseif (isset($avito_ids[$ad_id])) {
            $avito_id = $avito_ids[$ad_id];
            $status = 'successful';
            $info = ['section' => 'Опубликовано, замечаний нет', 'url' => 'https://www.avito.ru/items/' . $avito_id, 'messages' => []];
            $stats['published']++;
        } else {
            db_query('UPDATE ?:gm_avito_items SET status = ?s, status_text = NULL WHERE company_id = ?i AND ad_id = ?s', '', $company_id, $ad_id);
            $stats['none']++;
            continue;
        }
        if ($info['url'] === '' && $avito_id) {
            $info['url'] = 'https://www.avito.ru/items/' . $avito_id;
        }
        db_query('UPDATE ?:gm_avito_items SET ?u WHERE company_id = ?i AND ad_id = ?s', [
            'avito_id' => $avito_id ?: null,
            'status' => $status,
            'status_text' => fn_gm_avito_db_text(json_encode($info, JSON_UNESCAPED_UNICODE)),
        ], $company_id, $ad_id);
    }

    $report = [
        'time' => TIME,
        'upload_id' => $summary['upload_id'] ?? '',
        'started_at' => $summary['started_at'] ?? '',
        'upload_status' => $summary['status'] ?? '',
        'feed_urls' => $summary['feed_urls'] ?? [],
        'stats' => $summary['stats'] ?? [],
        'ads' => count($items),
        'with_problems' => $stats['problems'],
        'published' => $stats['published'],
        'not_on_avito' => $stats['none'],
        'unknown_count' => count(array_diff(array_keys($by_ad), array_map('strval', array_keys($items)))),
    ];
    fn_gm_avito_set_setting($company_id, 'report', json_encode($report, JSON_UNESCAPED_UNICODE));

    return $report;
}

/**
 * Объявления с ошибками и предупреждениями по последнему отчёту – для страницы проблем
 */
function fn_gm_avito_report_problems($company_id)
{
    $rows = db_get_array(
        'SELECT i.product_id, i.ad_id, i.avito_id, i.status, i.status_text, i.enabled, pd.product FROM ?:gm_avito_items AS i'
        . ' LEFT JOIN ?:product_descriptions AS pd ON pd.product_id = i.product_id AND pd.lang_code = ?s'
        . ' WHERE i.company_id = ?i AND i.status <> ?s AND i.status <> ?s ORDER BY i.status, pd.product',
        'ru',
        $company_id,
        '',
        'successful'
    );
    $result = [];
    foreach ($rows as $row) {
        $info = (array) json_decode(fn_gm_avito_xml_text((string) $row['status_text']), true);
        $result[] = [
            'product_id' => $row['product_id'],
            'product' => $row['product'],
            'enabled' => $row['enabled'],
            'section' => $info['section'] ?? $row['status'],
            'status' => $row['status'],
            'avito_status' => $info['avito_status'] ?? '',
            'url' => $info['url'] ?? '',
            'messages' => $info['messages'] ?? [],
        ];
    }

    return $result;
}
