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
