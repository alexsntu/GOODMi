<?php
/**
 * GOODMi: выгрузка на Авито – функции (этап 1, 2026-10-19).
 *
 * Файл автозагрузки (XML, formatVersion 3) собирается из cscart_gm_avito_items (заголовок, «описание для Авито», поля
 * категории – перенесены из UnitXML, правятся в модуле) и живых данных сайта: цена витрины (иначе общая цена товара) и
 * фото карточки (основное + дополнительные, полные адреса на сайте). Файл – var/files/<company>/gm_avito/feed.xml,
 * отдаёт его controllers/frontend/gm_avito.php по секретному ключу. Требования Авито: Id менять нельзя (опубликует заново),
 * фото обновляется только по новому адресу, файл забирает Avitobot-Autoload с адресов Авито (не блокировать фильтрами).
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/** Поля объявления, которые генератор пишет сам; остальные берутся из items.fields как есть */
const GM_AVITO_OWN_TAGS = ['Id', 'Title', 'Description', 'Price', 'Images'];

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
    return preg_replace_callback('/[\x{10000}-\x{10FFFF}]/u', static function ($m) {
        return '&#' . mb_ord($m[0], 'UTF-8') . ';';
    }, (string) $text);
}

/**
 * Обратно для файла Авито: числовые ссылки на 4-байтовые символы → сами символы (остальной текст не трогаем)
 */
function fn_gm_avito_xml_text($text)
{
    return preg_replace_callback('/&#(\d{5,7});/', static function ($m) {
        $code = (int) $m[1];

        return $code >= 0x10000 && $code <= 0x10FFFF ? mb_chr($code, 'UTF-8') : $m[0];
    }, (string) $text);
}

/**
 * Путь к файлу автозагрузки витрины
 */
function fn_gm_avito_feed_path($company_id)
{
    return rtrim(fn_get_files_dir_path($company_id), '/') . '/gm_avito/feed.xml';
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
 * Собирает файл автозагрузки витрины. Возвращает ['ads' => число объявлений, 'skipped' => [product_id => причина], 'path' => файл]
 */
function fn_gm_avito_build_feed($company_id)
{
    $site_url = fn_gm_avito_get_setting($company_id, 'site_url', 'https://' . db_get_field('SELECT url FROM ?:storefronts WHERE storefront_id = ?i', $company_id));
    $images_limit = (int) fn_gm_avito_get_setting($company_id, 'images_limit', 10);
    $defaults = (array) json_decode((string) fn_gm_avito_get_setting($company_id, 'defaults', '{}'), true);
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

    $result = ['ads' => 0, 'skipped' => [], 'path' => $path];
    $items = db_get_array('SELECT * FROM ?:gm_avito_items WHERE company_id = ?i AND enabled = ?s ORDER BY ad_id', $company_id, 'Y');
    foreach ($items as $item) {
        $product_id = (int) $item['product_id'];
        $price = fn_gm_avito_product_price($product_id, $company_id);
        $images = fn_gm_avito_product_images($product_id, $site_url, $images_limit);
        $fields = array_merge($defaults, (array) json_decode((string) $item['fields'], true));
        if ($price <= 0) {
            $result['skipped'][$product_id] = 'нет цены';
            continue;
        }
        if (!$images) {
            $result['skipped'][$product_id] = 'нет фото';
            continue;
        }
        if ($item['title'] === '' || empty($fields['Category'])) {
            $result['skipped'][$product_id] = 'нет заголовка или категории Авито';
            continue;
        }

        $xml->startElement('Ad');
        $xml->writeElement('Id', $item['ad_id']);
        foreach ($fields as $tag => $value) {
            if (in_array($tag, GM_AVITO_OWN_TAGS, true) || !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', (string) $tag)) {
                continue;
            }
            $xml->writeElement($tag, fn_gm_avito_xml_text($value));
        }
        $xml->writeElement('Title', fn_gm_avito_xml_text($item['title']));
        $xml->startElement('Description');
        $xml->writeCdata(str_replace(']]>', ']]&gt;', fn_gm_avito_xml_text($item['description'])));
        $xml->endElement();
        $xml->writeElement('Price', (string) (floor($price) == $price ? (int) $price : $price));
        $xml->startElement('Images');
        foreach ($images as $url) {
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
        'time' => TIME, 'ads' => $result['ads'], 'skipped' => count($result['skipped']),
    ]));

    return $result;
}
