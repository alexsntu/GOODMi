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
        if ($tag !== '' && $value !== '' && preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $tag) && !in_array($tag, GM_AVITO_OWN_TAGS, true)) {
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
function fn_gm_avito_feed_path($company_id)
{
    return rtrim(fn_get_files_dir_path($company_id), '/') . '/gm_avito/feed.xml';
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
        'skip' => '',
    ];

    if (empty($product) || $product['status'] !== 'A') {
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
    ];
}

/**
 * Собирает файл автозагрузки витрины. Возвращает ['ads' => число объявлений, 'skipped' => [product_id => причина],
 * 'reasons' => [причина => сколько], 'path' => файл]
 */
function fn_gm_avito_build_feed($company_id)
{
    $path = fn_gm_avito_feed_path($company_id);
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
    $items = db_get_array('SELECT * FROM ?:gm_avito_items WHERE company_id = ?i AND enabled = ?s ORDER BY ad_id', $company_id, 'Y');
    $context = fn_gm_avito_context($company_id, array_column($items, 'product_id'));
    foreach ($items as $item) {
        $ad = fn_gm_avito_prepare_ad($company_id, $item, $context);
        if ($ad['skip'] !== '') {
            $result['skipped'][(int) $item['product_id']] = $ad['skip'];
            $result['reasons'][$ad['skip']] = ($result['reasons'][$ad['skip']] ?? 0) + 1;
            continue;
        }

        $xml->startElement('Ad');
        $xml->writeElement('Id', $ad['id']);
        foreach ($ad['fields'] as $tag => $value) {
            if (in_array($tag, GM_AVITO_OWN_TAGS, true) || !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', (string) $tag)) {
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
        'new_text' => $ad['new_text'],
        'preview' => $ad['description'],
        'price' => $ad['price'],
        'images' => count($ad['images']),
    ];
}
