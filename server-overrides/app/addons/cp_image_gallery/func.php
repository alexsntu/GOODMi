<?php
/*****************************************************************************
*                                                        © 2013 Cart-Power   *
*           __   ______           __        ____                             *
*          / /  / ____/___ ______/ /_      / __ \____ _      _____  _____    *
*      __ / /  / /   / __ `/ ___/ __/_____/ /_/ / __ \ | /| / / _ \/ ___/    *
*     / // /  / /___/ /_/ / /  / /_/_____/ ____/ /_/ / |/ |/ /  __/ /        *
*    /_//_/   \____/\__,_/_/   \__/     /_/    \____/|__/|__/\___/_/         *
*                                                                            *
*                                                                            *
* -------------------------------------------------------------------------- *
* This is commercial software, only users who have purchased a valid license *
* and  accept to the terms of the License Agreement can install and use this *
* program.                                                                   *
* -------------------------------------------------------------------------- *
* website: https://store.cart-power.com                                      *
* email:   sales@cart-power.com                                              *
******************************************************************************/

if (!defined('BOOTSTRAP')) { die('Access denied'); }

use Tygh\Registry;
use Tygh\BlockManager\Block;

define('CP_IG_SEO_PATH', Registry::get('addons.cp_image_gallery.seo_path'));
define('CP_IG_IMAGE_TYPE', 'cp_image_gallery');
//
// Common functions
//

function fn_cp_get_image_galleries($params, $items_per_page = 0, $lang_code = DESCR_SL)
{
    $default_params = array(
        'items_per_page' => $items_per_page,
        'page' => 1
    );
    $params = array_merge($default_params, $params);
    $fields = array(
        'galleries.*',
        'descr.name',
        'descr.description'
    );
    $params['company_id'] = isset($params['company_id']) ? $params['company_id'] : fn_get_runtime_company_id();
    if (AREA == 'C') {
        $params['status'] = 'A';
        $params['placeholder_only'] = 'N';
    }
    $join = $condition = $sortings = $group = $limit = '';
    $join .= db_quote(
        ' LEFT JOIN ?:cp_image_gallery_descriptions as descr'
        . ' ON descr.gallery_id = galleries.gallery_id AND descr.lang_code = ?s', $lang_code
    );
    $condition .= db_quote(' AND galleries.dependent = ?s', 'N');

    if (!empty($params['name'])) {
        $condition .= db_quote(' AND descr.name LIKE ?l', '%' . $params['name'] . '%');
    }
    if (!empty($params['status'])) {
        $condition .= db_quote(' AND galleries.status = ?s', $params['status']);
    }
    if (!empty($params['display_type'])) {
        $condition .= db_quote(' AND galleries.display_type = ?s', $params['display_type']);
    }
    if (!empty($params['company_id'])) {
        $condition .= db_quote(' AND galleries.company_id = ?i', $params['company_id']);
    }
    if (!empty($params['placeholder_only'])) {
        $condition .= db_quote(' AND galleries.placeholder_only = ?s', $params['placeholder_only']);
    }

    fn_set_hook('cp_get_image_galleries', $params, $fields, $join, $condition, $group, $sortings, $lang_code);

    $sortings = array(
        'position' => 'galleries.position',
        'timestamp' => 'galleries.timestamp',
        'status' => 'galleries.status',
        'display_type' => 'galleries.display_type',
        'name' => 'descr.name'
    );
    $sorting = db_sort($params, $sortings, 'position', 'asc');

    if (!empty($params['get_conditions'])) {
        return array($fields, $join, $condition);
    }

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field(
            'SELECT COUNT(galleries.gallery_id) FROM ?:cp_image_galleries as galleries ?p WHERE 1 ?p',
            $join, $condition
        );
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }
    $galleries = db_get_hash_array(
        'SELECT ?p FROM ?:cp_image_galleries as galleries ?p WHERE 1 ?p ?p ?p ?p', 'gallery_id',
        implode(', ', $fields), $join, $condition, $group, $sorting, $limit
    );

    foreach ($galleries as $gallery_id => &$gallery) {
        $gallery['main_pair'] = fn_cp_ig_get_image_pairs($gallery_id, 'M', $lang_code);
    }

    fn_set_hook('cp_get_image_galleries_post', $galleries, $params, $lang_code);

    return array($galleries, $params);
}

function fn_cp_get_image_gallery_data($gallery_id, $params = [], $lang_code = DESCR_SL)
{
    $fields = array(
        'galleries.*',
        'descr.*'
    );
    $join = db_quote(
        ' LEFT JOIN ?:cp_image_gallery_descriptions as descr'
        . ' ON descr.gallery_id = galleries.gallery_id AND descr.lang_code = ?s', $lang_code
    );
    $company_id = isset($params['company_id']) ? $params['company_id'] : fn_get_runtime_company_id();
    $condition = db_quote('galleries.gallery_id = ?i', $gallery_id);
    if (AREA == 'C') {
        $condition .= db_quote(' AND galleries.status = ?s', 'A');
    }
    $gallery = db_get_row(
        'SELECT ?p FROM ?:cp_image_galleries as galleries ?p WHERE ?p',
        implode(', ', $fields), $join, $condition
    );
    if (!empty($gallery)) {
        $gallery['settings'] = !empty($gallery['settings']) ? unserialize($gallery['settings']) : [];
        $gallery['product_ids'] = fn_cp_get_image_gallery_objects($gallery_id, 'P');
        
        $gallery['main_pair'] = fn_cp_ig_get_image_pairs($gallery_id, 'M', $lang_code);
        
        $images_search = [];
        if (AREA == 'C' && $gallery['display_type'] == 'G'
            && (empty($gallery['settings']['no_pagination']) || $gallery['settings']['no_pagination'] != 'Y')
        ) {
            $images_search = $params;
            $images_search['items_per_page'] = !empty($gallery['settings']['count']) ? $gallery['settings']['count'] : 0;
        }
        $gallery['images'] = fn_cp_ig_get_image_pairs(
            $gallery_id, 'A', $lang_code, $images_search
        );
        $gallery['images_search'] = $images_search;
        if (!empty($gallery['block_id'])) {
            $block_data = Block::instance()->getById($gallery['block_id'], 0, [], $lang_code);
            $block_data['unique_id'] = Block::getUniqueIdByData($block_data);
            $gallery['block_code'] = '<b title="' . $block_data['name'] . '" class="wysiwyg-block-loader cm-block-loader cm-block-loader--' . $block_data['unique_id'] . '="></b>';
        }
    }

    fn_set_hook('cp_get_image_gallery_data_post', $gallery, $gallery_id, $lang_code);

    return $gallery;
}

function fn_cp_update_image_gallery($data, $gallery_id = 0, $lang_code = DESCR_SL)
{
    $is_dependent = (!empty($data['dependent']) && $data['dependent'] == 'Y') ? true : false;
    $default_data = array(
        'company_id' => isset($data['company_id']) ? $data['company_id'] : fn_get_runtime_company_id(),
        'position' => 0,
        'status' => 'A',
        'timestamp' => TIME,
        'display_type' => 'G'
    );
    $data = array_merge($default_data, $data);
    if (!fn_allowed_for('MULTIVENDOR') && empty($data['company_id'])) {
        $data['company_id'] = fn_get_default_company_id();
    }
    if (isset($data['settings'])) {
        $current_settings = $data['settings'];
        $data['settings'] = !empty($data['settings']) && is_array($data['settings']) ? serialize($data['settings']) : '';
    }
    if (empty($gallery_id)) { // Create
        $gallery_id = $data['gallery_id'] = db_query('INSERT INTO ?:cp_image_galleries ?e', $data);
        foreach (fn_get_translation_languages() as $data['lang_code'] => $_v) {
            db_query('REPLACE INTO ?:cp_image_gallery_descriptions ?e', $data);
        }
    } else { // Update
        db_query('UPDATE ?:cp_image_galleries SET ?u WHERE gallery_id = ?i', $data, $gallery_id);
        db_query(
            'UPDATE ?:cp_image_gallery_descriptions SET ?u WHERE gallery_id = ?i AND lang_code = ?s',
            $data, $gallery_id, $lang_code
        );
    }
    
    if (!empty($gallery_id)) {
        // To link main object 
        if ($is_dependent && !empty($data['object_type']) && !empty($data['object_id'])) {
            fn_cp_update_image_gallery_objects($gallery_id, $data['object_type'], $data['object_id'], 'M');
        }
        // To link advanced objects
        if (!empty($data['objects']) && is_array($data['objects'])) {
            foreach ($data['objects'] as $object_type => $object_ids) {
                fn_cp_update_image_gallery_objects($gallery_id, $object_type, $object_ids, 'A');
            }
        }

        // Attach gallery images
        fn_cp_ig_attach_image_pairs('gallery_main', $gallery_id, $lang_code);
        fn_cp_ig_attach_image_pairs('gallery_img', $gallery_id, $lang_code);
        $new_image_ids = fn_cp_ig_attach_image_pairs('gallery_add_img', $gallery_id, $lang_code);
        
        // Sort image pairs
        if (isset($current_settings)) {
            fn_cp_ig_sort_image_pairs(CP_IG_IMAGE_TYPE, $gallery_id, $current_settings, $new_image_ids);
        }
    }

    fn_set_hook('cp_update_image_gallery_post', $data, $gallery_id, $lang_code);

    return $gallery_id;
}

function fn_cp_delete_image_gallery($gallery_id)
{
    db_query('DELETE FROM ?:cp_image_galleries WHERE gallery_id = ?i', $gallery_id);
    db_query('DELETE FROM ?:cp_image_gallery_descriptions WHERE gallery_id = ?i', $gallery_id);
    db_query('DELETE FROM ?:cp_image_gallery_links WHERE gallery_id = ?i', $gallery_id);

    fn_delete_image_pairs($gallery_id, CP_IG_IMAGE_TYPE);
    /*
    $gallery_image_ids = db_get_fields('SELECT gallery_image_id FROM ?:cp_image_gallery_pairs WHERE gallery_id = ?i', $gallery_id);
    if (!empty($gallery_image_ids)) {
        foreach ($gallery_image_ids as $gallery_image_id) {
            fn_delete_image_pairs($gallery_image_id, CP_IG_IMAGE_TYPE);
        }
    }
    db_query('DELETE FROM ?:cp_image_gallery_pairs WHERE gallery_id = ?i', $gallery_id);
    */
    fn_set_hook('cp_delete_image_gallery_post', $gallery_id);
}

// Wrap for backend controllers
function fn_cp_update_object_image_gallery($data, $gallery_id)
{
    if (!function_exists('___cp')) {
        return;
    }
    if (!empty($data['object_enable']) && !empty($data['object_type']) && !empty($data['object_id'])) {
        if ($data['object_enable'] == 'Y') {
            fn_cp_update_image_gallery($data, $gallery_id);
        } else {
            $object_gallery_id = fn_cp_get_object_main_gallery_id($data['object_type'], $data['object_id']);
            if (!empty($object_gallery_id)) {
                $data['status'] = 'D';
                fn_cp_update_image_gallery($data, $gallery_id);
            }
        }    
    }

    if (isset($data['ext_gallery_ids'])
        && !empty($data['object_type']) && !empty($data['object_id'])
    ) {
        if (is_array($data['ext_gallery_ids'])) {
            $ext_gallery_ids = $data['ext_gallery_ids'];
        } else {
            $ext_gallery_ids = !empty($data['ext_gallery_ids']) ? explode(',', $data['ext_gallery_ids']) : [];
        }
        fn_cp_delete_object_image_galleries($data['object_type'], $data['object_id']);
        foreach ($ext_gallery_ids as $ext_gallery_id) {
            fn_cp_update_image_gallery_objects($ext_gallery_id, $data['object_type'], $data['object_id'], 'A');
        }
    }
}

function fn_cp_get_object_image_gallery($object_type, $object_id, $lang_code = DESCR_SL, $params = [])
{
    $gallery_id = fn_cp_get_object_main_gallery_id($object_type, $object_id);
    if (!empty($gallery_id)) {
        $gallery_data = fn_cp_get_image_gallery_data($gallery_id, $params, $lang_code);
    }
    $gallery_data['ext_gallery_ids'] = fn_cp_get_object_gallery_ids($object_type, $object_id);
    return $gallery_data;
}

// Wrap for frontend controllers
function fn_cp_get_display_image_galleries($object_type, $object_id, $params = [])
{
    $main_gallery_id = fn_cp_get_object_main_gallery_id($object_type, $object_id);
    $galleries_ids = fn_cp_get_object_gallery_ids($object_type, $object_id);
    if (!empty($main_gallery_id)) {
        array_unshift($galleries_ids, $main_gallery_id);
    }
    $image_galleries = [];
    foreach ($galleries_ids as $gallery_id) {
        $ext_gallery = fn_cp_get_image_gallery_data($gallery_id, $params);
        if (!empty($ext_gallery['placeholder_only']) && $ext_gallery['placeholder_only'] == 'Y') {
            continue;
        }
        $image_galleries[] = $ext_gallery;
    }
    return $image_galleries;
}
// !

function fn_cp_get_object_main_gallery_id($object_type, $object_id)
{
    $gallery_id = db_get_field(
        'SELECT gallery_id FROM ?:cp_image_gallery_links'
        . ' WHERE object_type = ?s AND object_id = ?i AND link_type = ?s',
        $object_type, $object_id, 'M'
    );
    return $gallery_id;
}

function fn_cp_get_object_gallery_ids($object_type, $object_id)
{
    $gallery_ids = db_get_fields(
        'SELECT gallery_id FROM ?:cp_image_gallery_links'
        . ' WHERE object_type = ?s AND object_id = ?i AND link_type = ?s',
        $object_type, $object_id, 'A'
    );
    return $gallery_ids;
}

function fn_cp_get_image_gallery_objects($gallery_id, $object_type)
{
    $object_ids = db_get_fields(
        'SELECT object_id FROM ?:cp_image_gallery_links WHERE gallery_id = ?i AND object_type = ?s'
        . ' ORDER BY position ASC', $gallery_id, $object_type
    );
    return $object_ids;
}

function fn_cp_update_image_gallery_objects($gallery_id, $object_type, $object_ids = [], $link_type = 'A')
{
    $object_ids = is_array($object_ids) ? $object_ids : explode(',', $object_ids);
    $data = array(
        'gallery_id' => $gallery_id,
        'object_type' => $object_type,
        'link_type' => $link_type
    );
    foreach ($object_ids as $data['object_id']) {
        db_query('REPLACE INTO ?:cp_image_gallery_links ?e', $data);
    }
    return $object_ids;
}

function fn_cp_delete_image_gallery_objects($gallery_id, $object_type)
{
    db_query(
        'DELETE FROM ?:cp_image_gallery_links WHERE gallery_id = ?i AND object_type = ?s',
        $gallery_id, $object_type
    );
}

function fn_cp_delete_object_image_galleries($object_type, $object_id, $link_type = 'A')
{
    $condition = !empty($link_type) ? db_quote(' AND link_type = ?s', $link_type) : '';
    db_query(
        'DELETE FROM ?:cp_image_gallery_links WHERE object_type = ?s AND object_id = ?i ?p',
        $object_type, $object_id, $condition
    );
}

function fn_cp_get_image_gallery_name($gallery_id, $lang_code = DESCR_SL)
{
    return db_get_field(
        'SELECT name FROM ?:cp_image_gallery_descriptions WHERE gallery_id = ?i AND lang_code = ?s',
        $gallery_id, $lang_code
    );
}

function fn_cp_ig_attach_image_pairs($type, $gallery_id, $lang_code)
{
    return fn_attach_image_pairs($type, CP_IG_IMAGE_TYPE, $gallery_id, $lang_code);
    /*
    $gallery_image_id = fn_cp_ig_get_image_id($gallery_id, $lang_code, true);
    if (!empty($gallery_image_id)) {
        fn_attach_image_pairs($type, CP_IG_IMAGE_TYPE, $gallery_image_id, $lang_code);
    }
    */
}

function fn_cp_ig_get_image_id($gallery_id, $lang_code, $add_if_empty = false)
{
    $gallery_image_id = db_get_field(
        'SELECT gallery_image_id FROM ?:cp_image_gallery_pairs WHERE gallery_id = ?i AND lang_code = ?s',
        $gallery_id, $lang_code
    );
    if (empty($gallery_image_id) && $add_if_empty) {
        $data = array(
            'gallery_id' => $gallery_id,
            'lang_code' => $lang_code
        );
        $gallery_image_id = db_query('INSERT INTO ?:cp_image_gallery_pairs ?e', $data);
    }
    return $gallery_image_id;
}

function fn_cp_ig_get_image_pairs($object_ids, $pair_type, $lang_code = CART_LANGUAGE, &$params = [])
{
    $object_type = CP_IG_IMAGE_TYPE;
    $icon_pairs = $detailed_pairs = $pairs_data = [];

    $fields = array(
        '?:images.*',
        '?:images_links.*',
        '?:common_descriptions.description AS alt',
        '?:images.image_id AS images_image_id'
    );

    $join = db_quote(
        ' LEFT JOIN ?:images ON ?:images_links.detailed_id = ?:images.image_id'
        . ' LEFT JOIN ?:common_descriptions ON ?:common_descriptions.object_id = ?:images.image_id'
        . ' AND ?:common_descriptions.object_holder = ?s AND ?:common_descriptions.lang_code = ?s',
        'images', $lang_code
    );

    $condition = db_quote(
        ' AND ?:images_links.object_type = ?s AND ?:images_links.type = ?s', $object_type, $pair_type
    );

    if (is_array($object_ids)) {
        $condition .= $object_ids
            ? db_quote(' AND ?:images_links.object_id IN (?a)', $object_ids)
            : db_quote(' AND ?:images_links.object_id IS NULL'); // backward compatibility: prevents SQL empty array deprecation notice
    } else {
        $condition .= db_quote(' AND ?:images_links.object_id = ?s', $object_ids);
    }

    $sorting = ' ORDER BY ?:images_links.position, ?:images_links.pair_id';
    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field(
            'SELECT COUNT(?:images_links.detailed_id) FROM ?:images_links ?p WHERE 1 ?p',
            $join, $condition
        );
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $detailed_pairs = db_get_array(
        'SELECT ?p FROM ?:images_links ?p WHERE 1 ?p ?p ?p',
        implode(', ', $fields), $join, $condition, $sorting, $limit
    );

    foreach ((array) $object_ids as $object_id) {
        $pairs_data[$object_id] = [];
    }

    foreach ($detailed_pairs as $pair) {
        $pair_id = $pair['pair_id'];
        $object_id = $pair['object_id'];

        if (!empty($pairs_data[$object_id][$pair_id]['detailed_id'])) {
            $detailed = fn_attach_absolute_image_paths($pair, 'detailed');
            $pairs_data[$object_id][$pair_id]['detailed'] = array(
                'object_id' => $pair['object_id'],
                'object_type' => $pair['object_type'],
                'type' => $pair['type'],
                'image_path' => $detailed['image_path'],
                'alt' => $detailed['alt'],
                'image_x' => $detailed['image_x'],
                'image_y' => $detailed['image_y'],
                'http_image_path' => $detailed['http_image_path'],
                'https_image_path' => $detailed['https_image_path'],
                'absolute_path' => $detailed['absolute_path'],
                'relative_path' => $detailed['relative_path']
            );

        } elseif (empty($pairs_data[$object_id][$pair_id]['pair_id'])) {
            $pairs_data[$object_id][$pair_id] = array(
                'object_id' => $pair['object_id'],
                'object_type' => $pair['object_type'],
                'pair_id' => $pair['pair_id'],
                'image_id' => $pair['image_id'],
                'detailed_id' => $pair['detailed_id'],
                'position' => $pair['position'],
            );

            if (!empty($pair['images_image_id'])) { //get detailed data if exist
                $detailed = fn_attach_absolute_image_paths($pair, 'detailed');
                $pairs_data[$object_id][$pair_id]['detailed'] = array(
                    'object_id' => $pair['object_id'],
                    'object_type' => $pair['object_type'],
                    'image_path' => $detailed['image_path'],
                    'alt' => $detailed['alt'],
                    'image_x' => $detailed['image_x'],
                    'image_y' => $detailed['image_y'],
                    'http_image_path' => $detailed['http_image_path'],
                    'https_image_path' => $detailed['https_image_path'],
                    'absolute_path' => $detailed['absolute_path'],
                    'relative_path' => $detailed['relative_path']
                );
            }
        }

        if (!empty($pair['images_image_id'])) {
            $extra_descr = fn_cp_ig_get_image_descr($pair['images_image_id'], $lang_code);
            $pairs_data[$object_id][$pair_id]['detailed'] = array_merge(
                $pairs_data[$object_id][$pair_id]['detailed'],
                $extra_descr
            );
            $pairs_data[$object_id][$pair_id]['cp_ig_settings'] = !empty($pair['cp_ig_settings']) ? unserialize($pair['cp_ig_settings']) : [];
        }
    }

    if (is_array($object_ids)) {
        return $pairs_data;
    } else {
        if ($pair_type == 'A') {
            return $pairs_data[$object_ids];
        } else {
            return !empty($pairs_data[$object_ids])? reset($pairs_data[$object_ids]) : [];
        }
    }
}

function fn_cp_ig_check_settings_change($current_settings)
{
    //$settings = fn_cp_ig_get_available_settings();
}


function fn_cp_ig_get_available_settings($type, $add_general = true)
{
    $settings_schema = fn_get_schema('cp_image_gallery', 'settings');
    $addon_settings = Registry::get('addons.cp_image_gallery');
    foreach ($settings_schema as $setting_type => &$settings) {
        $prefix = !empty($setting_type) ? $setting_type . '_' : '';
        foreach ($settings as $setting_name => &$setting) {
            if (isset($addon_settings[$prefix . $setting_name])) {
                $setting['default_value'] = $addon_settings[$prefix . $setting_name];
            }
        }
    }
    return $settings_schema;
}

function fn_cp_image_gallery_get_discussion($object_id, $object_type, $get_posts = false, $params = [])
{
    if (Registry::get('addons.discussion.status') == 'A') {
        return fn_get_discussion($object_id, $object_type, $get_posts, $params);
    }
    
    return false;
}

function fn_cp_ig_update_image_descr($img_id, $pair = [], $lang_code = CART_LANGUAGE)
{
    if (isset($pair['image_name'])) {
        $data = array(
            'object_id' => $img_id,
            'object_type' => '',
            'description' => $pair['image_name'],
            'lang_code' => $lang_code,
            'object' => '',
            'object_holder' => 'cp_img_name'
        );
        db_query('REPLACE INTO ?:common_descriptions ?e', $data);
    }
}

function fn_cp_ig_get_image_descr($img_id, $lang_code = DESCR_SL)
{
    $img_name = db_get_field(
        'SELECT description FROM ?:common_descriptions'
        . ' WHERE object_id = ?i AND object_holder = ?s AND lang_code = ?s',
        $img_id, 'cp_img_name', $lang_code
    );
    return array('image_name' => $img_name);
}

function fn_cp_ig_delete_image_descr($img_id)
{
    db_get_field(
        'DELETE FROM ?:common_descriptions WHERE object_id = ?i AND object_holder = ?s',
        $img_id, 'cp_img_name'
    );
}

function fn_cp_ig_sort_image_pairs($object_type, $object_id, $settings = [], $new_image_ids = [])
{
    if (empty($new_image_ids) || empty($settings['add_images'])) {
        return;
    }
    if ($settings['add_images'] == 'T') {
        $pair_ids = db_get_fields(
            'SELECT pair_id FROM ?:images_links WHERE object_type = ?s AND object_id = ?i AND type = ?s ORDER BY position',
            $object_type, $object_id, 'A'
        );
        $old_image_ids = array_diff($pair_ids, $new_image_ids);
        if (!empty($old_image_ids)) {
            $new_positions = array_merge($new_image_ids, $old_image_ids);
            foreach ($new_positions as $position => $pair_id) {
                db_query('UPDATE ?:images_links SET position = ?i WHERE pair_id = ?i', $position, $pair_id);
            }
        }
    } else {
        $max_position = db_get_field(
            'SELECT MAX(position) FROM ?:images_links WHERE object_type = ?s AND object_id = ?i AND type = ?s',
            $object_type, $object_id, 'A'
        );
        $max_position = !empty($max_position) ? $max_position : 0;
        foreach ($new_image_ids as $pair_id) {
            $max_position++;
            db_query('UPDATE ?:images_links SET position = ?i WHERE pair_id = ?i', $max_position, $pair_id);
        }
    }
    return true;
}

function fn_cp_ig_prepare_placeholder_data($text, $params = [], $lang_code = CART_LANGUAGE)
{
    static $placeholders = null;
    if (!isset($placeholders)) {
        $placeholders = db_get_hash_single_array(
            'SELECT gallery_id, placeholder FROM ?:cp_image_galleries'
            . ' WHERE status = ?s AND dependent != ?s AND placeholder != ""',
            array('gallery_id', 'placeholder'), 'A', 'Y'
        );
    }

    if (!empty($placeholders)) {
        Tygh::$app['view']->assign('cp_is_placeholder', true);
        
        foreach ($placeholders as $gallery_id => $placeholder) {
            $placeholder = '[' . $placeholder . ']';
            if (strpos($text, $placeholder) === false) {
                continue;
            }
            $gallery = fn_cp_get_image_gallery_data($gallery_id, $params, $lang_code);
            Tygh::$app['view']->assign('gallery', $gallery);
            if (empty($params['sector']) && !defined('AJAX_REQUEST')) {
                $sector = rand(0, 10000);
                
                // For several identical galleries
                while (strpos($text, $placeholder) !== false) {
                    Tygh::$app['view']->assign('sector', $sector);
                    $tpl = Tygh::$app['view']->fetch('addons/cp_image_gallery/common/image_gallery.tpl');
                    $text = preg_replace('/' . preg_quote($placeholder) . '/', $tpl, $text, 1); // Replace 1 placeholder
                    $sector++;
                }
                Tygh::$app['view']->assign('sector', $sector + rand(0, 100));
            } else {
                $sector = $params['sector'];
                Tygh::$app['view']->assign('sector', $sector);
                $tpl  = Tygh::$app['view']->fetch('addons/cp_image_gallery/common/image_gallery.tpl');
                $text = str_replace($placeholder, $tpl, $text);
            }
        }

        Tygh::$app['view']->assign('cp_is_placeholder', false);
    }
    return $text;
}

// Social buttons functions

function fn_cp_ig_get_sb_providers_meta_data($params)
{
    $addon_settings = Registry::get('settings.social_buttons');
    $providers_meta_data = [];

    if (fn_allowed_for('ULTIMATE')) {
        $company_id = Registry::ifGet('runtime.company_id', fn_get_default_company_id());
        $site_name = fn_get_company_name($company_id);
    }

    $gallery = Tygh::$app['view']->getTemplateVars('gallery_data');

    $image = !empty($gallery['main_pair']) ? $gallery['main_pair'] : reset($gallery['images']);
    
    $providers_meta_data['all'] = array(
        'title' => fn_sb_format_page_title(),
        'url' => fn_url('cp_image_galleries.view?gallery_id=' . $params['object_id']),
        'image' => !empty($image['detailed']['image_path']) ? $image['detailed']['image_path'] : '',
        'image:width' => !empty($image['detailed']['image_x']) ? $image['detailed']['image_x'] : '',
        'image:height' => !empty($image['detailed']['image_y']) ? $image['detailed']['image_y'] : '',
        'site_name' => !empty($site_name) ? $site_name : Registry::get('settings.Company.company_name'),
    );

    if (!empty($addon_settings)) {
        foreach ($addon_settings as $provider_name => $provider_data) {
            if ('facebook' == $provider_name) {
                $func_name = 'fn_cp_ig_facebook_prepare_meta_data';
            } else {
                $func_name = 'fn_' . $provider_name . '_prepare_meta_data';
            }

            if (is_callable($func_name)) {
                $providers_meta_data[$provider_name] = call_user_func($func_name, $provider_data, $params);
            }
        }
    }
    return $providers_meta_data;
}

function fn_cp_ig_facebook_prepare_meta_data($provider_data, $params)
{
    $addon_settings = Registry::get('settings.social_buttons');

    $return = array(
        'type' => 'activity',
        'app_id' => !empty($addon_settings['facebook']['facebook_app_id']) ? $addon_settings['facebook']['facebook_app_id'] : '',
    );
    return $return;
}

function fn_cp_ig_get_sb_provider_settings($params)
{
    $view = Tygh::$app['view'];
    $addon_settings = Registry::get('settings.social_buttons');

    $provider_settings = [];
    if (!empty($addon_settings)) {
        foreach ($addon_settings as $provider_name => $provider_data) {
            if (!empty($provider_data[$provider_name . '_enable']) && $provider_data[$provider_name . '_enable'] === 'Y') {
                $provider_data[$provider_name . '_display_on']['cp_image_galleries'] = 'Y';
                $func_name = 'fn_' . $provider_name . '_prepare_settings';

                if (is_callable($func_name)) {
                    $provider_settings[$provider_name]['data'] = call_user_func($func_name, $provider_data, $params);
                }

                $provider_settings[$provider_name]['template'] = $provider_name . '.tpl';
                if ($view->templateExists('addons/social_buttons/meta_templates/' . $provider_name . '.tpl')) {
                    $provider_settings[$provider_name]['meta_template'] = $provider_name . '.tpl';
                }
            }
        }
    }

    return $provider_settings;
}


//
// Hooks
//

function fn_cp_image_gallery_get_products($params, $fields, $sortings, &$condition, &$join, $sorting, $group_by, $lang_code, $having)
{
    if (!empty($params['is_for_img_gallery']) && !empty($params['gallery_id'])) {
        $join .= db_quote(" LEFT JOIN ?:cp_image_gallery_links ON ?:cp_image_gallery_links.object_id = products.product_id AND ?:cp_image_gallery_links.object_type = ?s", 'P');
        $condition .= db_quote(' AND ?:cp_image_gallery_links.gallery_id = ?i', $params['gallery_id']);
    }
}

function fn_cp_image_gallery_update_image_pairs($pair_ids, $icons, $detailed, $pairs_data, $object_id, $object_type, $object_ids, $update_alt_desc, $lang_code)
{
    if (empty($pairs_data) || $update_alt_desc != true || $object_type != CP_IG_IMAGE_TYPE) {
        return;
    }
    foreach ($pairs_data as $k => $p_data) {
        $pair_id = !empty($p_data['pair_id']) ? $p_data['pair_id'] : 0;
        if (empty($pair_id)) {
            continue;
        }
        $image_ids = db_get_row('SELECT image_id, detailed_id FROM ?:images_links WHERE pair_id = ?i', $pair_id);
        $fields = array('detailed', 'image');
        foreach ($fields as $field) {
            if (empty($image_ids[$field . '_id'])) {
                continue;
            }
            fn_cp_ig_update_image_descr($image_ids[$field . '_id'], $p_data, $lang_code);
        }
        $ig_settings = !empty($p_data['cp_ig_settings']) && is_array($p_data['cp_ig_settings']) ? serialize($p_data['cp_ig_settings']) : '';
        if (!empty($ig_settings)) {
            db_query('UPDATE ?:images_links SET cp_ig_settings = ?s WHERE pair_id = ?i', $ig_settings, $pair_id);
        }
    }
}

function fn_cp_image_gallery_delete_image_pair($pair_id, $object_type, $images)
{
    fn_cp_ig_delete_image_descr($pair_id);
}

function fn_cp_image_gallery_seo_get_name_post(&$name, $object_type, $object_id, $dispatch, $company_id, $lang_code)
{
    if (defined('CP_IG_SEO_PATH') && !empty(CP_IG_SEO_PATH) && $object_type == 'g') {
        $name = CP_IG_SEO_PATH . '/' . $name;
    }
}

function fn_cp_image_gallery_validate_sef_object($path, $seo, $vars, &$result, $objects)
{
    if (defined('CP_IG_SEO_PATH') && !empty(CP_IG_SEO_PATH)
        && !empty($seo['type']) && $seo['type'] == 'g' && $path == '/' . CP_IG_SEO_PATH
    ) {
        $result = true;
    }
}

function fn_cp_image_gallery_is_need_watermark_post($object_type, $is_detailed, $company_id, &$result)
{
    if ($object_type == CP_IG_IMAGE_TYPE) {
        $settings = fn_get_watermark_settings($company_id);

        if (empty($settings)) {
            return false;
        }
        if ($is_detailed && !empty($settings['image_pair']['detailed'])) {
            $result = true;
        } elseif (!$is_detailed && !empty($settings['image_pair']['icon'])) {
            $result = true;
        }
    }
}

function fn_cp_image_gallery_dispatch_before_display()
{
    if (AREA != 'C') {
        return;
    }
    $controller = Registry::get('runtime.controller');
    $mode = Registry::get('runtime.mode');
    $lang_code = CART_LANGUAGE;

    $params = $_REQUEST;
    $schema = fn_get_schema('cp_image_gallery', 'placeholder_templates');
    if (!empty($schema[$controller][$mode])) {
        foreach ($schema[$controller][$mode] as $template_var => $var_fields) {
            $var_data = Tygh::$app['view']->getTemplateVars($template_var);
            $is_ab_seo = false;
                if (defined('AJAX_REQUEST')) {
                    $ajax_vars = Registry::get('ajax')->getAssignedVar('ab__sf_data');
                    $is_ab_seo = true;
                    // GOODMi 2026-10-21: ab__sf_data есть не в каждом AJAX-запросе – без ?? Warning «array offset on null» (поведение то же)
                    $var_data['description'] = $ajax_vars['description'] ?? null;
                }
            if (!empty($var_data)) {
                foreach ($var_fields as $var_field) {
                    $var_data[$var_field] = fn_cp_ig_prepare_placeholder_data($var_data[$var_field], $params, $lang_code);
                }
                if ($is_ab_seo) {
                    $ajax_vars['description'] = $var_data['description'];
                    Registry::get('ajax')->assign('ab__sf_data', $ajax_vars);
                }
                Tygh::$app['view']->assign($template_var, $var_data);
            }
        }
    }
}


function fn_cp_image_gallery_render_blocks($grid, &$block, $obj, $content)
{
    if (AREA != 'C') {
        return;
    }

    $allowed_types = array('html_block', 'smarty_block');
    if (empty($block['type']) || !in_array($block['type'], $allowed_types)) {
        return;
    }
    $params = $_REQUEST;
    if (!empty($block['content']['content'])) {
        $block['content']['content'] = fn_cp_ig_prepare_placeholder_data($block['content']['content'], $params);
    }
}


function fn_cp_image_gallery_image_to_display_post(&$image_data, $images, $image_width, $image_height)
{
    if (AREA != 'C') {
        return;
    }
    $img_types = array('detailed', 'icon');
    foreach ($img_types as $img_type) {
        if (!empty($images[$img_type]['image_name'])) {
            $image_data['title'] = $images[$img_type]['image_name'];
        }
    }
}

function fn_cp_image_gallery_get_discussion_objects(&$discussion_object_types)
{
    $discussion_object_types['G'] = 'gallery';
}
function fn_cp_image_gallery_get_discussion_titles(&$discussion_object_titles)
{
    $discussion_object_titles['G'] = 'discussion_title_gallery';
}

function fn_cp_image_gallery_clone_product_post($product_id, $pid, $orig_name, $new_name)
{
    
}

function fn_cp_ig_get_block_galleries($params)
{
    if (!empty($params['item_ids'])) {
        $gallery_id = intval($params['item_ids']);
        if (!empty($gallery_id)) {
            $gallery_data = fn_cp_get_image_gallery_data($gallery_id, $params, CART_LANGUAGE);
            return [[$gallery_data], $params];
        }
    } elseif (!empty($params['company_id'])) {
        $image_galleries = fn_cp_get_display_image_galleries('Q', $params['company_id'], $params);
        return [$image_galleries, $params];
    } elseif (!empty($params['product_id'])) {
        $image_galleries = fn_cp_get_display_image_galleries('P', $params['product_id'], $params);
        return [$image_galleries, $params];
    }
    return [];
}

//
// Hooks of other addons
//

function fn_seo_cp_get_image_gallery_data_post(&$gallery_data, $gallery_id, $lang_code)
{
    if (!empty($gallery_id) && !empty($gallery_data)) {
        $seo_name = fn_seo_get_name('g', $gallery_id, '', null, $lang_code);
        $name_parts = explode('/', $seo_name);
        if (reset($name_parts) == CP_IG_SEO_PATH) {
            array_shift($name_parts);
        }
        $gallery_data['seo_name'] = implode('/', $name_parts);
    }
}

function fn_seo_cp_update_image_gallery_post($gallery_data, $gallery_id, $lang_code)
{
    if (!empty($gallery_data['name']) && !empty($gallery_id)) {
        if (Registry::get('runtime.company_id')) {
            $gallery_data['company_id'] = Registry::get('runtime.company_id');
        }
        fn_seo_update_object($gallery_data, $gallery_id, 'g', $lang_code);
    }
}

function fn_seo_cp_delete_image_gallery_post(&$gallery_id)
{
    return fn_delete_seo_name($gallery_id, 'g');
}

function fn_discussion_cp_delete_image_gallery_post(&$gallery_id)
{
    fn_delete_discussion($gallery_id, 'G');
}

function fn_discussion_cp_update_image_gallery_post(&$gallery_data, &$gallery_id, &$lang_code)
{
    if (!empty($gallery_data['discussion_type'])) {
        $discussion_type = $gallery_data['discussion_type'];
    } else {
        $discussion_type = 'D';
    }
    $discussion = [
        'object_type'   => 'G',
        'object_id'     => $gallery_id,
        'type'          => $discussion_type,
        'company_id'    => Registry::get('runtime.company_id')
    ];
    fn_update_discussion($discussion);
}

function fn_cp_generate_gallery_block($gallery_id, $lang_code = CART_LANGUAGE)
{
    $block_id = 0;
    if (!empty($gallery_id)) {
        $gallery_name = db_get_field("SELECT name FROM ?:cp_image_gallery_descriptions WHERE gallery_id = ?i AND lang_code = ?s", $gallery_id, $lang_code);
        $block = [
            'type'          => 'cp_image_gallery',
            'block_id'      => 0,
            'content_data'  => [
                'lang_code' => $lang_code,
                'content'   => [
                    'items' => [
                        'filling'   => 'manually',
                        'item_ids'  => $gallery_id
                    ]
                ]
            ],
            'apply_to_all_langs'=> 'Y',
            'description'       => [
                'name'      => __('cp_ig_gallery') . ' ' . $gallery_name,
                'lang_code' => $lang_code
            ],
            'properties'    => [
                'template'  => 'addons/cp_image_gallery/blocks/original.tpl'
            ],
            'content'   => [
                'items' => [
                    'filling'   => 'manually',
                    'item_ids'  => $gallery_id
                ]
            ]
        ];
        $description = [
            'name'      => __('cp_ig_gallery') . ' ' . $gallery_name,
            'lang_code' => $lang_code
        ];
        
        $block_id = Block::instance()->update($block, $description);
        if (!empty($block_id)) {
            db_query("UPDATE ?:cp_image_galleries SET block_id = ?i WHERE gallery_id = ?i", $block_id, $gallery_id);
        }
    }
    return $block_id;
}

//
// Addon.xml functions
//

function fn_cp_image_gallery_install()
{
    if (Registry::get('addons.seo.status') == 'A') {
        $companies = db_get_fields('SELECT company_id FROM ?:companies');
        foreach (fn_get_translation_languages() as $lc => $_v) {
            foreach ($companies as $company_id) {
                fn_create_seo_name(0, 's', 'image-galleries', 0, 'cp_image_galleries.view_all', $company_id, $lc);
            }
        }
    }
}

function fn_cp_image_gallery_uninstall()
{
    $gallery_ids = db_get_fields('SELECT gallery_id FROM ?:cp_image_galleries');
    foreach ($gallery_ids as $gallery_id) {
        fn_cp_delete_image_gallery($gallery_id);
    }
    if (Registry::get('addons.discussion.status') == 'A') {
        $object_ids = db_get_fields('SELECT object_id FROM ?:discussion WHERE object_type=?s', 'G');
        if (!empty($object_ids)) {
            foreach ($object_ids as $object_id) {
                fn_delete_discussion($object_id, 'G');
            }
        }
    }
}
