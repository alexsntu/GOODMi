<?php
/*******************************************************************************************
*   ___  _          ______                     _ _                _                        *
*  / _ \| |         | ___ \                   | (_)              | |              © 2025   *
* / /_\ | | _____  _| |_/ /_ __ __ _ _ __   __| |_ _ __   __ _   | |_ ___  __ _ _ __ ___   *
* |  _  | |/ _ \ \/ / ___ \ '__/ _` | '_ \ / _` | | '_ \ / _` |  | __/ _ \/ _` | '_ ` _ \  *
* | | | | |  __/>  <| |_/ / | | (_| | | | | (_| | | | | | (_| |  | ||  __/ (_| | | | | | | *
* \_| |_/_|\___/_/\_\____/|_|  \__,_|_| |_|\__,_|_|_| |_|\__, |  \___\___|\__,_|_| |_| |_| *
*                                                         __/ |                            *
*                                                        |___/                             *
* ---------------------------------------------------------------------------------------- *
* This is commercial software, only users who have purchased a valid license and accept    *
* to the terms of the License Agreement can install and use this program.                  *
* ---------------------------------------------------------------------------------------- *
* website: https://cs-cart.alexbranding.com                                                *
*   email: info@alexbranding.com                                                           *
*******************************************************************************************/
use Tygh\Enum\Addons\Ab_intelligentAccessories\Operators;
use Tygh\Enum\ProductFilterStyles;
use Tygh\Languages\Languages;
use Tygh\Settings;
use Tygh\Registry;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\Addons\Ab_antibot\BotTypes;
use Tygh\Addons\Ab_intelligentAccessories\Cache;
if (!defined('BOOTSTRAP')) {
die('Access denied');
}
function fn_ab__ia_install()
{
$objects = [
[
'table' => '?:ab__ia_joins',
'field' => 'show_in_add_to_cart',
'sql' => 'ALTER TABLE ?p ADD ?p CHAR(1) NOT NULL DEFAULT \'N\'',
],
[
'table' => '?:ab__ia_joins',
'field' => 'show_in_cart',
'sql' => 'ALTER TABLE ?p ADD ?p CHAR(1) NOT NULL DEFAULT \'N\'',
],
[
'table' => '?:ab__ia_joins',
'field' => 'last_update_date',
'sql' => 'ALTER TABLE ?p ADD ?p TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
],
[
'table' => '?:ab__ia_joins',
'field' => 'group_id',
'sql' => 'ALTER TABLE ?p ADD ?p INT(11) NOT NULL DEFAULT 0',
],
[
'table' => '?:ab__ia_joins',
'field' => 'tab_url',
'sql' => 'ALTER TABLE ?p ADD ?p varchar(20) NOT NULL DEFAULT \'not_use\'',
],
[
'table' => '?:ab__ia_joins',
'field' => 'custom_url',
'sql' => 'ALTER TABLE ?p ADD ?p varchar(255) NOT NULL DEFAULT \'\'',
],
[
'table' => '?:ab__ia_joins',
'field' => 'start_update',
'sql' => 'ALTER TABLE ?p ADD ?p INT(11) NOT NULL DEFAULT 0',
],
[
'table' => '?:ab__ia_joins',
'field' => 'end_update',
'sql' => 'ALTER TABLE ?p ADD ?p INT(11) NOT NULL DEFAULT 0',
],
];
if (!empty($objects) && is_array($objects)) {
foreach ($objects as $object) {
$fields = db_get_fields('DESCRIBE ' . $object['table']);
if (!empty($fields) && is_array($fields)) {
$is_present_field = false;
foreach ($fields as $f) {
if ($f == $object['field']) {
$is_present_field = true;
break;
}
}
if (!$is_present_field) {
db_query($object['sql'], $object['table'], $object['field']);
if (!empty($object['add_sql'])) {
foreach ($object['add_sql'] as $sql) {
db_query($sql);
}
}
}
}
}
}
fn_ab__ia_migration_280_290();
}

function fn_ab__ia_migration_280_290()
{
$addon = 'ab__intelligent_accessories';
if (db_get_field('SHOW TABLES LIKE \'?:abt__ut2_settings\'')) {
$old_settings = db_get_array("SELECT * FROM ?:abt__ut2_settings WHERE `section` = '{$addon}'");
if ($old_settings) {
foreach ($old_settings as $setting) {
db_query("REPLACE INTO ?:abt__ut2_settings (`section`, `name`, `company_id`, `lang_code`, `value`)
VALUES ('addons', '{$addon}.{$setting['name']}', {$setting['company_id']}, '{$setting['lang_code']}', '{$setting['value']}')");
}
db_query("DELETE FROM ?:abt__ut2_settings WHERE `section` = '{$addon}'");
$settings = db_get_array("SELECT * FROM ?:abt__ut2_settings WHERE name in ('{$addon}.show_price', '{$addon}.outside_navigation')");
if (!empty($settings)) {
foreach ($settings as $setting) {
$p = [];
$p['mobile'] = $p['tablet'] = $p['desktop'] = unserialize($setting['value']);
$p = serialize($p);
db_query("REPLACE INTO ?:abt__ut2_settings (`section`, `name`, `company_id`, `lang_code`, `value`)
VALUES ('addons', '{$setting['name']}', {$setting['company_id']}, '{$setting['lang_code']}', '{$p}')");
}
}
$settings = db_get_array("SELECT * FROM ?:abt__ut2_settings WHERE name in ('{$addon}.item_quantity')");
if (!empty($settings)) {
foreach ($settings as $setting) {
$p = unserialize($setting['value'])['desktop'];
$p = serialize($p);
db_query("REPLACE INTO ?:abt__ut2_settings (`section`, `name`, `company_id`, `lang_code`, `value`)
VALUES ('addons', '{$setting['name']}', {$setting['company_id']}, '{$setting['lang_code']}', '{$p}')");
}
}
}
}
}
function fn_ab__ia_get_joins($params = [], $lang_code = CART_LANGUAGE)
{
$default_params = [
'company_id' => 0,
'base_category_id' => 0,
'joined_category_id' => 0,
'amount_of_rules' => false,
'products_sorting' => 'price',
'with_rules' => false,
'status' => ['A', 'D'],
'items_per_page' => 0,
'calc_rules' => false,
];
$params = array_merge($default_params, $params);
$fields = [
'j.join_id',
'j.base_category_id',
'j.joined_category_id',
'j.price_from',
'j.price_to',
'j.shuffle_products',
'j.only_in_stock',
'j.show_in_cart',
'j.show_in_add_to_cart',
'j.products_sorting',
'j.status',
'j.position',
'j.max_view_products',
'jd.lang_code',
'jd.name',
'j.group_id',
'j.tab_url',
'j.custom_url',
'j.start_update',
'j.end_update',
];
$sortings = [
'name' => [
'j.status',
'j.position',
'jd.name',
],
'group_id' => [
'j.group_id',
],
];
$condition = $limit = $join = '';
if (isset($params['join_id']) && intval($params['join_id']) > 0) {
$condition .= db_quote(' AND j.join_id = ?i ', $params['join_id']);
}
if (isset($params['status']) && in_array($params['status'], ['A', 'D'])) {
$condition .= db_quote(' AND j.status in (?a) ', $params['status']);
}
if (isset($params['base_category_id']) && intval($params['base_category_id']) > 0) {
$condition .= db_quote(' AND j.base_category_id = ?i ', $params['base_category_id']);
}
if (!empty($params['limit']) && intval($params['limit']) > 0) {
$limit = db_quote(' LIMIT 0, ?i', $params['limit']);
}
if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
$join .= db_quote(' INNER JOIN ?:categories ON (j.base_category_id = ?:categories.category_id)');
$condition .= fn_get_company_condition('?:categories.company_id');
}
$sorting = db_sort($params, $sortings, 'name', 'asc');
$join .= db_quote(' LEFT JOIN ?:ab__ia_join_descriptions as jd ON (jd.join_id = j.join_id AND jd.lang_code = ?s ) ', $lang_code);
fn_set_hook('ab__ia_get_joins_pre', $params, $fields, $sortings, $sorting, $join, $condition);
if (!empty($params['items_per_page'])) {
$params['total_items'] = db_get_field("SELECT count(*) FROM ?:ab__ia_joins as j $join WHERE 1 ?p", $condition);
$limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
}
$data = db_get_hash_array('SELECT ' . implode(',', $fields) . " FROM ?:ab__ia_joins as j $join WHERE 1 ?p $sorting ?p", 'join_id', $condition, $limit);
if (empty($data)) {
return false;
}
if ($params['amount_of_rules']) {
$amount_of_rules = db_get_hash_array('SELECT count(rule_id) as amount, join_id FROM ?:ab__ia_rules WHERE status = \'A\' and join_id IN (?n) GROUP BY join_id', 'join_id', array_keys($data));
if (fn_is_not_empty($amount_of_rules)) {
foreach ($amount_of_rules as $id => $v) {
$data[$id]['amount_of_rules'] = $v['amount'];
}
}
}
if ($params['with_rules']) {
$rules = db_get_array('SELECT * FROM ?:ab__ia_rules WHERE join_id IN (?n) ORDER BY status asc, position asc, name asc', array_keys($data));
if (fn_is_not_empty($rules)) {
foreach ($rules as $r) {
$r['conditions'] = unserialize($r['conditions']);
$data[$r['join_id']]['rules'][$r['rule_id']] = $r;
}
}
}
return [$data, $params];
}
function fn_ab__ia_upd_joins($_join_data, $join_id = 0, $lang_code = CART_LANGUAGE)
{
$join_data = $_join_data;
if (strlen(trim($join_data['name']))
&& (intval($join_data['base_category_id']))
&& (intval($join_data['joined_category_id']))
&& (intval($join_data['max_view_products']))
&& (in_array($join_data['shuffle_products'], ['Y', 'N']))
&& (in_array($join_data['only_in_stock'], ['Y', 'N']))
&& (in_array($join_data['show_in_cart'], ['Y', 'N']))
&& (in_array($join_data['show_in_add_to_cart'], ['Y', 'N']))
&& (strlen($join_data['products_sorting']))
) {
$_data = $join_data;
if (empty($join_id)) {
$join_id = db_query('INSERT INTO ?:ab__ia_joins ?e', $_data);
$_data['join_id'] = $join_id;
foreach (fn_get_translation_languages() as $_data['lang_code'] => $_v) {
db_query('INSERT INTO ?:ab__ia_join_descriptions ?e', $_data);
}
} else {
db_query('UPDATE ?:ab__ia_joins SET ?u WHERE join_id = ?i', $_data, $join_id);
db_query('UPDATE ?:ab__ia_join_descriptions SET ?u WHERE join_id = ?i AND lang_code = ?s', $_data, $join_id, $lang_code);
}
if (isset($_join_data['rules']) && is_array($_join_data['rules'])) {
$rule_ids = null;
foreach ($_join_data['rules'] as $r) {
$conditions = null;
if (isset($r['conditions']) && is_array($r['conditions'])) {
foreach ($r['conditions'] as $t => $cond) {
$type = (in_array($cond['type'], ['o', 'c'])) ? $cond['type'] : 'o';
$conditions[$t]['type'] = $type;
if (is_array($cond)) {
foreach ($cond['items'] as $c) {
if ($type == 'o' && intval($c['f']) > 0 && in_array($c['o'], fn_ab__ia_get_available_operators()) && (intval($c['v']) || $c['v'] == 'corresponding-base')) {
$conditions[$t]['items'][] = $c;
}
}
}
}
}
if ($conditions['B']['type'] == 'o' && $conditions['J']['type'] == 'o' && !isset($conditions['B']['items']) && !isset($conditions['J']['items'])) {
continue;
}
$d = [
'join_id' => $join_id,
'name' => (isset($r['name']) && strlen(trim($r['name'])) > 0) ? trim($r['name']) : __('ab__ia_new_rule'),
'conditions' => serialize($conditions),
'status' => (isset($r['status']) && in_array($r['status'], ['A', 'D'])) ? $r['status'] : 'A',
'position' => (isset($r['position']) && intval($r['position']) > 0) ? intval($r['position']) : 0,
'max_view_products' => (isset($r['max_view_products']) && intval($r['max_view_products']) > 0 && intval($r['max_view_products']) <= AB__IA_RULE_MAX_VIEW_PRODUCTS) ? intval($r['max_view_products']) : AB__IA_RULE_MAX_VIEW_PRODUCTS_DEF,
];
if (isset($r['rule_id']) && intval($r['rule_id']) > 0) {
db_query('UPDATE ?:ab__ia_rules SET ?u WHERE rule_id = ?i', $d, $r['rule_id']);
$rule_ids[] = $r['rule_id'];
} elseif ($r['rule_id'] == 0) {
$rule_ids[] = db_query('INSERT INTO ?:ab__ia_rules ?e', $d);
}
}
if (fn_is_not_empty($rule_ids)) {
db_query('DELETE FROM ?:ab__ia_rules WHERE join_id = ?i AND rule_id not in (?n) ', $join_id, $rule_ids);
} else {
db_query('DELETE FROM ?:ab__ia_rules WHERE join_id = ?i ', $join_id);
}
}
} else {
if (strlen(trim($join_data['name'])) == 0) {
fn_set_notification('E', __('error'), __('ab__ia_error_join_name_is_empty'));
}
if (intval(trim($join_data['joined_category_id'])) == 0) {
fn_set_notification('E', __('error'), __('ab__ia_error_joined_category_is_empty'));
}
return false;
}
return $join_id;
}
function fn_ab__ia_get_features($base_category_id = 0, $joined_category_id = 0)
{
$features_of_base_category = fn_ab__ia_get_features_by_category($base_category_id);
$features_of_joined_category = fn_ab__ia_get_features_by_category($joined_category_id);
return [
'B' => $features_of_base_category,
'J' => $features_of_joined_category,
];
}
function fn_ab__ia_get_features_by_category($category_id = 0)
{
$res = false;
if (intval($category_id) > 0) {
$params = [
'category_ids' => fn_get_category_ids_with_parent($category_id),
'get_descriptions' => true,
'search_in_subcats' => true,
'variants' => true,
'feature_types' => [ProductFeatures::TEXT_SELECTBOX, ProductFeatures::MULTIPLE_CHECKBOX, ProductFeatures::EXTENDED, ProductFeatures::NUMBER_SELECTBOX],
'plain' => false,
'variant_images' => false,
'variants_selected_only' => true,
];
list($features) = fn_get_product_features($params, 0, DESCR_SL);
if (is_array($features)) {
foreach ($features as $k => $v) {
if ($v['feature_type'] == 'G') {
$res[$k]['is_group'] = true;
$res[$k]['group'] = $v['internal_name'];
$res[$k]['items'] = [];
if (!empty($v['subfeatures'])) {
foreach ($v['subfeatures'] as $_k => $_v) {
$res[$k]['items'][$_k]['value'] = $_v['internal_name'];
$res[$k]['items'][$_k]['status'] = $_v['status'];
$res[$k]['items'][$_k]['is_numeric'] = $_v['feature_type'] === ProductFeatures::NUMBER_SELECTBOX && $_v['filter_style'] === ProductFilterStyles::SLIDER;
$res[$k]['items'][$_k]['variants'] = fn_ab__ia_get_feature_variants($_k, $category_id);
}
}
} else {
$res[$k]['value'] = $v['internal_name'];
$res[$k]['status'] = $v['status'];
$res[$k]['is_numeric'] = $v['feature_type'] === ProductFeatures::NUMBER_SELECTBOX && $v['filter_style'] === ProductFilterStyles::SLIDER;
$res[$k]['variants'] = fn_ab__ia_get_feature_variants($k, $category_id);
}
}
}
}
return $res;
}
function fn_ab__ia_get_feature_variants($feature_id = 0, $category_id = 0)
{
$res = [];
if (intval($feature_id) > 0 && intval($category_id) > 0) {
$res = db_get_hash_single_array('SELECT DISTINCTROW pfvd.variant_id, pfvd.variant
FROM ?:product_feature_variant_descriptions AS pfvd
INNER JOIN ?:product_features_values AS pfv ON (pfvd.variant_id = pfv.variant_id)
INNER JOIN ?:products_categories AS pc ON (pc.product_id = pfv.product_id)
WHERE pfvd.lang_code = ?s
AND pfv.lang_code = ?s
AND pfv.feature_id = ?i
AND pc.category_id in (SELECT category_id FROM ?:categories WHERE category_id = ?i OR id_path like ?s OR id_path like ?s OR id_path like ?s)
ORDER BY pfvd.variant ASC', ['variant_id', 'variant'], DESCR_SL, DESCR_SL, $feature_id, $category_id, "$category_id/%", "%/$category_id/%", "%/$category_id");
}
return $res;
}
function fn_ab__ia_get_products_by_variants($variants = [], $category_id = 0, $only_in_stock = 'N', $price_from = '', $price_to = '', $exclude_pid = [])
{
if (empty($variants) || empty($category_id)) {
return false;
}
$slider_features_as_selectbox = [];
$feature_variants = [];
$feature_variants_exclude = [];
$neq_feature_variants = [];
$cache = Cache::class;
if(!empty($variants['in'])){
$in_cache_key = Cache::generateKey($variants['in'], 'in');
if(!$cache::has($in_cache_key)){
$variants_in = db_get_array('SELECT pfv.feature_id, pfv.variant_id, pfval.value_int, pf.filter_style
FROM ?:product_feature_variants pfv
LEFT JOIN ?:product_features pf ON pf.feature_id=pfv.feature_id
LEFT JOIN ?:product_features_values pfval ON pfval.variant_id = pfv.variant_id AND pfval.lang_code = ?s
WHERE pfv.variant_id in (?n) GROUP BY pfv.variant_id',CART_LANGUAGE, $variants['in']);
$cache::set($in_cache_key, $variants_in);
}
$fvs = $cache::get($in_cache_key);
if (!empty($fvs)) {
foreach ($fvs as $i) {
if($i['filter_style'] === 'slider'){
$slider_features_as_selectbox[] = $i['feature_id'];
}
$feature_variants[$i['feature_id']][] = $i['variant_id'];
}
}
}
if(!empty($variants['nin'])) {
$nin_cache_key = Cache::generateKey($variants['nin'], 'nin');
if(!$cache::has($nin_cache_key)){
$variants_nin = db_get_array('SELECT pfv.feature_id, pfv.variant_id, pfval.value_int, pf.filter_style
FROM ?:product_feature_variants pfv
LEFT JOIN ?:product_features pf ON pf.feature_id=pfv.feature_id
LEFT JOIN ?:product_features_values pfval ON pfval.variant_id = pfv.variant_id AND pfval.lang_code = ?s
WHERE pfv.variant_id in (?n) GROUP BY pfv.variant_id', CART_LANGUAGE, $variants['nin']);
$cache::set($nin_cache_key, $variants_nin);
}
$nin_variants = $cache::get($nin_cache_key);
if (!empty($nin_variants)) {
foreach ($nin_variants as $i) {
if($i['filter_style'] === 'slider'){
$slider_features_as_selectbox[] = $i['feature_id'];
}
$feature_variants_exclude[$i['feature_id']][] = $i['variant_id'];
}
}
}
$arithmetic_features = array_merge(
array_keys($variants['gt'] ?? []),
array_keys($variants['gte'] ?? []),
array_keys($variants['lt'] ?? []),
array_keys($variants['lte'] ?? []),
array_keys($variants['eq'] ?? [])
);
if(!empty($arithmetic_features)){
$minmax_features = db_get_hash_array('SELECT feature_id, MAX(value_int) as max, MIN(value_int) as min FROM ?:product_features_values WHERE feature_id IN(?n) and lang_code = ?s AND value_int IS NOT NULL GROUP BY feature_id ', 'feature_id', $arithmetic_features, CART_LANGUAGE);
}
foreach (Operators::getArithmeticAll() as $operator) {
if(!empty($variants[$operator])){
$f_id = array_keys($variants[$operator])[0];
$f_value = $variants[$operator][$f_id];
$feature_variants[$f_id] = [];
switch ($operator){
case Operators::EQUAL:
$feature_variants[$f_id][] = $f_value;
$feature_variants[$f_id][] = $f_value;
break;
case Operators::NOT_EQUAL:
$neq_feature_variants[$f_id] = $f_value;
break;
case Operators::GREATER_OR_EQUAL:
if(isset($minmax_features[$f_id])){
$feature_variants[$f_id][] = $f_value;
$feature_variants[$f_id][] = $minmax_features[$f_id]['max'];
}
break;
case Operators::LESS_OR_EQUAL:
if(isset($minmax_features[$f_id])){
$feature_variants[$f_id][] = $minmax_features[$f_id]['min'];
$feature_variants[$f_id][] = $f_value;
}
break;
}
}
}
$unique_slider_features_as_selectbox = array_unique($slider_features_as_selectbox);
Registry::set('ab__intelligent_accessories.slider_features_as_selectbox', $unique_slider_features_as_selectbox);
$p = [
'cid' => $category_id,
'subcats' => 'Y',
'only_short_fields' => true,
'status' => Registry::ifGet('ab__intelligent_accessories.type', '') === 'base' ? ['A','H'] : 'A',
'sort_by' => 'null',
'price_from' => ((float)$price_from > 0) ? (float)$price_from : '',
'price_to' => ((float)$price_to > 0) ? (float)$price_to : '',
'feature_variants' => $feature_variants,
'feature_variants_exclude' => $feature_variants_exclude,
'neq_feature_variants' => $neq_feature_variants,
'get_query' => true,
'ab__ia_get_products' => true,
];
if ($only_in_stock == 'Y') {
$p['amount_from'] = 1;
}
$products_cache_key = Cache::generateKey(array_merge(
['unique_slider_features_as_selectbox' => $unique_slider_features_as_selectbox],
$p
), 'p');
if(!$cache::has($products_cache_key)){
$sql = fn_get_products($p, 0, DESCR_SL);
if (fn_allowed_for('MULTIVENDOR') && Registry::get('addons.master_products.status') == 'A') {
$sql = str_replace( ' WHERE 1 ', ' WHERE 1 AND products.company_id <> 0 ', $sql);
}
$cache::set($products_cache_key, db_get_fields($sql));
}
$ps = $cache::get($products_cache_key);
if (!empty($exclude_pid)) {
$ps = array_diff($ps, $exclude_pid);
}
return (!empty($ps) ? $ps : false);
}
function fn_ab__ia_get_products_by_category($category_id = 0, $only_in_stock = 'N', $price_from = '', $price_to = '', $exclude_pid = [])
{
$cache = Cache::class;
if (empty($category_id)) {
return false;
}
$p = [
'cid' => $category_id,
'subcats' => 'Y',
'only_short_fields' => true,
'sort_by' => 'null',
'status' => Registry::ifGet('ab__intelligent_accessories.type', '') === 'base' ? ['A','H'] : 'A',
'sort_order' => 'asc',
'price_from' => ((float)$price_from > 0) ? (float)$price_from : '',
'price_to' => ((float)$price_to > 0) ? (float)$price_to : '',
'get_query' => true,
'ab__ia_get_products' => true,
];
if ($only_in_stock == 'Y') {
$p['amount_from'] = 1;
}
$cache_key = $cache::generateKey($p, 'pc');
if(!$cache::has($cache_key)){
$sql = fn_get_products($p, 0, DESCR_SL);
if (fn_allowed_for('MULTIVENDOR') && Registry::get('addons.master_products.status') == 'A') {
$sql = str_replace(' WHERE 1 ', ' WHERE 1 AND products.company_id <> 0 ', $sql);
}
$cache::set($cache_key, db_get_fields($sql));
}
$ps = $cache::get($cache_key);
if (!empty($exclude_pid)) {
$ps = array_diff($ps, $exclude_pid);
}
return (!empty($ps) ? $ps : false);
}
function fn_ab__ia_get_full_set_products_by_rule($rule_id, $info = false)
{
$products = [];
if (intval($rule_id) > 0) {
if ($info) {
$time_start = microtime(true);
}
$rule = db_get_row('SELECT r.rule_id,
r.conditions,
r.max_view_products,
j.base_category_id,
j.joined_category_id,
j.only_in_stock,
j.price_from,
j.price_to,
j.products_sorting
FROM ?:ab__ia_rules as r
LEFT JOIN ?:ab__ia_joins as j ON (j.join_id = r.join_id)
WHERE r.rule_id = ?i', $rule_id);
if (!empty($rule) && is_array($rule)) {
$rule['conditions'] = unserialize($rule['conditions']);
Registry::set('ab__intelligent_accessories.type', 'base');
if ($rule['conditions']['B']['type'] == 'o') {
$base_variants = [];
if (!empty($rule['conditions']['B']['items'])) {
list($base_variants) = fn_ab__ia_process_variants($rule['conditions']['B']['items']);
}
$base_products = fn_ab__ia_get_products_by_variants($base_variants, $rule['base_category_id']);
} elseif ($rule['conditions']['B']['type'] == 'c') {
$base_products = fn_ab__ia_get_products_by_category($rule['base_category_id']);
}
if ($info) {
fn_set_notification('N', 'BASE PRODUCTS', print_r(sizeof($base_products) ? 'Total: ' . sizeof($base_products) : '', 1));
}
$joined_products = [];
if (!empty($base_products)) {
Registry::set('ab__intelligent_accessories.type', 'join');
foreach ($base_products as $base_product) {
$join_key = $rule['conditions']['J']['type'] . $base_product;
$search_join_products_status = true;
if ($rule['conditions']['J']['type'] == 'c') {
if (!isset($joined_products[$join_key])) {
$joined_products[$join_key] = fn_ab__ia_get_products_by_category($rule['joined_category_id'], $rule['only_in_stock'], $rule['price_from'], $rule['price_to'], [$base_product]);
}
} elseif ($rule['conditions']['J']['type'] == 'o') {
$joined_variants = [];
if (!empty($rule['conditions']['J']['items'])) {
list($joined_variants, $search_join_products_status) = fn_ab__ia_process_variants($rule['conditions']['J']['items'], $base_product);
}
$join_key .= md5(json_encode($joined_variants));
if ($search_join_products_status && !isset($joined_products[$join_key])) {
$joined_products[$join_key] = fn_ab__ia_get_products_by_variants($joined_variants, $rule['joined_category_id'], $rule['only_in_stock'], $rule['price_from'], $rule['price_to'], [$base_product]);
}
}
if ($search_join_products_status && !empty($joined_products[$join_key])) {
$products[$base_product][$rule_id] = array_slice(fn_ab__ia_sorting_products($rule['products_sorting'], $joined_products[$join_key], $rule['max_view_products'], $rule['joined_category_id']), 0, $rule['max_view_products']);
$joined_products[$join_key] = null;
unset($joined_products[$join_key]);
}
}
}
if ($info) {
if (empty($base_products)) {
fn_set_notification('N', __('ab__ia.test_rule.result.title', ['[time]' => sprintf('%.6F', microtime(true) - $time_start)]), __('ab__ia.test_rule.result.text_empty'), 'S');
} elseif (!empty($products)) {
$result = [];
$max = 20;
$i = 0;
foreach ($products as $base_product_id => $join_products) {
$result[$i++] = '<a target=\'_blank\' href=\'' . fn_url('products.view?product_id=' . $base_product_id, 'C') . '\'>' . $base_product_id . '</a>: ' . implode(', ', $join_products[$rule_id]) . ';';
if ($i > $max) {
break;
}
}
fn_set_notification('N', __('ab__ia.test_rule.result.title', ['[time]' => sprintf('%.6F', microtime(true) - $time_start)]), __('ab__ia.test_rule.result.text', ['[count]' => count($products), '[max]' => $max, '[list]' => implode('<br>', $result)]), 'S');
}
}
}
}
return $products;
}
function fn_ab__ia_process_variants($stored_conditions = [], $base_product = 0){
$search_join_products_status = true;
$prepared_variants = [];
foreach ($stored_conditions as $cond) {
$v = $cond['v'];
switch($cond['o']){
case 'in':
case 'nin':
$prepared_variants[$cond['o']] = array_merge($prepared_variants[$cond['o']] ?? [], explode(',',$v));
break;
case 'gt':
case 'lt':
case 'gte':
case 'lte':
case 'eq':
if ($cond['v'] == 'corresponding-base') {
$v = db_get_field('SELECT variant_id FROM ?:product_features_values WHERE feature_id = ?i AND product_id = ?i', $cond['f'], $base_product);
if (empty($v)) {
$search_join_products_status = false;
break;
}
$prepared_variants['in'] = array_merge($prepared_variants['in'] ?? [], (array) $v);
break;
}
case 'neq':
$prepared_variants[$cond['o']][$cond['f']] = $v;
break;
}
}
array_walk($prepared_variants, function(&$el, $key){
if(in_array($key, Operators::getArithmeticAll())){
return $el;
}
$el = array_unique($el);
sort($el, SORT_NATURAL);
return array_values($el);
});
return [$prepared_variants, $search_join_products_status];
}
function fn_ab__ia_get_join_list_by_product($params = [], $target = '', $limit = 0)
{
if (!fn_ab__ia_antibot_hide_content() && isset($params['product_id']) && intval($params['product_id']) > 0) {
$group = '';
if (Registry::get('addons.ab__intelligent_accessories.exclude_duplication_of_tabs') == 'Y') {
$group = db_quote('GROUP BY jd.name');
}
$condition = '';
if (isset($params['links_group']) && fn_ab__ia_group_is_available($params['links_group'])) {
$condition .= db_quote(' AND j.group_id = ?i', $params['links_group']);
}
$fields = [
'join_id' => 'jd.join_id',
'name' => 'jd.name',
'tab_url' => 'j.tab_url',
'custom_url' => 'j.custom_url',
'joined_category_id' => 'j.joined_category_id'
];
fn_set_hook('ab__ia_get_join_list_by_product_pre', $fields, $condition, $params, $target, $limit, $group);
$joins = db_get_hash_array('SELECT ?p
FROM ?:ab__ia_data d
INNER JOIN ?:ab__ia_join_descriptions AS jd ON (jd.join_id = d.join_id)
INNER JOIN ?:ab__ia_joins AS j ON (jd.join_id = j.join_id)
INNER JOIN ?:categories AS c ON (c.category_id = j.base_category_id ?p)
WHERE d.product_id = ?i AND jd.lang_code = ?s AND j.status = \'A\' ?p ?p ?p
ORDER BY j.position asc, jd.name asc ?p', 'join_id',implode(',', $fields), fn_get_company_condition('c.company_id', true, $params['block_data']['company_id']), intval($params['product_id']), DESCR_SL, $condition, (in_array($target, ['in_add_to_cart', 'in_cart']) ? "AND j.show_{$target} = 'Y'" : ''), $group, ($limit > 0) ? "LIMIT {$limit}" : '');
fn_set_hook('ab__ia_get_join_list_by_product', $joins, $condition, $params, $target, $limit, $group);
if (!empty($joins) && is_array($joins)) {
return [$joins, $params];
}
}
return false;
}
function fn_ab__ia_get_products_by_join($params = [], $limit = 0)
{
if (isset($params['product_id']) && intval($params['product_id']) > 0 && isset($params['join_id']) && intval($params['join_id']) > 0) {
$product_list = fn_ab__ia_check_products_by_join(intval($params['join_id']), intval($params['product_id']));
if (!empty($product_list) && is_array($product_list)) {
list($join) = fn_ab__ia_get_joins(['join_id' => $params['join_id'], 'with_rules' => false]);
$p = [
'pid' => $product_list,
'exclude_pid' => !empty($params['exclude_pid']) ? $params['exclude_pid'] : [],
'limit' => $limit,
'ab__ia_get_joined_products' => true
];
list($p['sort_by'], $p['sort_order']) = explode('-', $join[$params['join_id']]['products_sorting']);
list($products) = fn_get_products($p, 0, DESCR_SL);
$products = array_replace(array_flip($product_list), $products);
$products = array_filter($products, function ($value) {
return is_array($value);
});
fn_gather_additional_products_data($products, ['get_icon' => true, 'get_detailed' => true, 'get_options' => true, 'get_discounts' => true]);
fn_set_hook('ab__ia_get_products_by_join_post', $products, $params, $limit);
return $products;
}
}
return false;
}
function fn_ab__ia_get_products_by_joins($params = [], $limit = 0)
{
if (isset($params['product_id']) && intval($params['product_id']) > 0 && !empty($params['join_ids'])) {
$res = [];
foreach ($params['join_ids'] as $j) {
$ps = fn_ab__ia_get_products_by_join(['product_id' => $params['product_id'], 'join_id' => $j, 'exclude_pid' => !empty($params['exclude_pid']) ? $params['exclude_pid'] : []], $limit);
if (!empty($ps)) {
$res = array_merge($res, $ps);
}
}
if (!empty($res)) {
shuffle($res);
if ($limit) {
$res = array_slice($res, 0, $limit);
}
}
return $res;
}
return false;
}
function fn_ab__ia_check_products_by_join($join_id, $product_id)
{
$product_list = [];
if ((int)$join_id > 0 && (int)$product_id > 0) {
$addon_settings = Registry::get('addons.ab__intelligent_accessories');
if ((int)$addon_settings['update_cache_every'] > 0) {
$min_timestamp = (int)db_get_field('SELECT IFNULL(min(timestamp), 0) min_timestamp FROM ?:ab__ia_data WHERE product_id = ?i and join_id = ?i GROUP BY product_id, join_id', (int)$product_id, (int)$join_id);
if ($min_timestamp > 0 && ($min_timestamp + $addon_settings['update_cache_every'] * SECONDS_IN_DAY) < TIME) {
fn_ab__ia_update_cache(['join_id' => $join_id]);
}
}
$product_list = unserialize(db_get_field('SELECT list FROM ?:ab__ia_data WHERE product_id = ?i and join_id = ?i', (int)$product_id, (int)$join_id), ['allowed_classes' => false]) ?: [];
}
fn_set_hook('ab__ia_check_products_by_join_post', $product_list, $join_id, $product_id);
return $product_list;
}
function fn_ab__ia_sorting_products($products_sorting = 'null-asc', $products = [], $limit = 0, $category_id = 0)
{
$cache = Cache::class;
if (!empty($products) && is_array($products)) {
if ($products_sorting == 'null-asc') {
return $products;
}
$p = [
'pid' => $products,
'only_short_fields' => true,
'limit' => $limit,
'load_products_extra_data' => false,
];
list($p['sort_by'], $p['sort_order']) = explode('-', $products_sorting);
if($category_id && $p['sort_by'] === 'position'){
$p['cid'] = $category_id;
$p['subcats'] = true;
}
$cache_key = $cache::generateKey($p, 'ps');
if(!$cache::has($cache_key)){
[$_products] = fn_get_products($p, 0, DESCR_SL);
$cache::set($cache_key, $_products);
}
$ps = $cache::get($cache_key);
if (!empty($ps)) {
return array_keys($ps);
}
}
return $products;
}
function fn_ab__ia_update_cache($params = [], $info = false, $limit = 0)
{
if ($info) {
$time_start = microtime(true);
}
set_time_limit(3600);
$cond = $sql_limit = '';
if (isset($params['base_category_id']) && intval($params['base_category_id']) > 0) {
$cond .= db_quote(' AND j.base_category_id = ?i ', intval($params['base_category_id']));
}
if (isset($params['join_id']) && intval($params['join_id']) > 0) {
$cond .= db_quote(' AND j.join_id = ?i ', intval($params['join_id']));
}
if ($limit) {
$sql_limit = db_quote('ORDER BY j.last_update_date ASC, j.join_id ASC LIMIT ?i', $limit);
}
if (fn_allowed_for('ULTIMATE')) {
$cond .= fn_get_company_condition('cb.company_id');
}
$joins = db_get_fields(' SELECT j.join_id
FROM ?:ab__ia_joins j
LEFT JOIN ?:categories cb ON (cb.category_id = j.base_category_id)
LEFT JOIN ?:categories cj ON (cj.category_id = j.joined_category_id)
WHERE j.status IN (?a)
?p
AND cb.status != ?s
AND cj.status != ?s ?p', ['A','H'], $cond, 'D', 'D', $sql_limit);
foreach ($joins as $join_id) {
fn_ab__ia_update_join_cache($join_id);
}
if ($info) {
fn_set_notification('N', 'Time of calculation', sprintf('%.3F', microtime(true) - $time_start) . ' sec');
}
return true;
}
function fn_ab__ia_update_join_cache($join_id){
$time_start = time();
list($join) = fn_ab__ia_get_joins(['join_id' => $join_id, 'with_rules' => true]);
$join = $join[$join_id];
$products = [];
if (isset($join['rules']) && is_array($join['rules'])) {
foreach ($join['rules'] as $r) {
if ($r['status'] != 'A') {
continue;
}
$ps = fn_ab__ia_get_full_set_products_by_rule($r['rule_id']);
if (!empty($ps)) {
$products = fn_array_merge($products, $ps);
}
}
}
db_query('DELETE FROM ?:ab__ia_data WHERE join_id = ?i ', $join_id);
if (empty($products) || !is_array($products)) {
$products = [];
}
foreach ($products as $p_id => $rule_products) {
$list = [];
foreach ($rule_products as $p) {
$list = array_merge($list, $p);
}
$list = array_unique($list);
if ($join['shuffle_products'] == 'Y') {
shuffle($list);
$list = fn_ab__ia_sorting_products($join['products_sorting'], $list, $join['max_view_products']);
} else {
$list = array_slice($list, 0, $join['max_view_products']);
}
$products[$p_id]['list'] = $list;
}
foreach (array_chunk($products, 200, true) as $ps) {
$data = [];
foreach ($ps as $p_id => $p) {
$data[] = [
'product_id' => $p_id,
'join_id' => $join_id,
'timestamp' => TIME,
'list' => serialize($p['list']),
];
}
db_query('INSERT INTO ?:ab__ia_data ?m', $data);
}
$time_end = time();
db_query('UPDATE ?:ab__ia_joins SET last_update_date = ?s, start_update = ?i, end_update = ?i WHERE join_id = ?i', date('Y-m-d H:i:s'), $time_start, $time_end, $join_id);
}
function fn_ab__ia_base_category_list()
{
$company_condition = fn_get_company_condition('c.company_id');
$categories = db_get_array('
SELECT j.base_category_id, j.joined_category_id
FROM ?:ab__ia_joins j
LEFT JOIN ?:categories c on c.category_id = j.base_category_id
WHERE j.status = \'A\' ?p', $company_condition);
if (empty($categories) || !is_array($categories)) {
return false;
}
$categories_joins = [];
foreach ($categories as $item) {
if (!isset($categories_joins[$item['base_category_id']]['name'])) {
$categories_joins[$item['base_category_id']]['name'] = fn_get_category_name($item['base_category_id']);
}
if (!isset($categories_joins[$item['base_category_id']]['items'][$item['joined_category_id']])) {
$categories_joins[$item['base_category_id']]['items'][$item['joined_category_id']] = fn_get_category_name($item['joined_category_id']);
}
}
return $categories_joins;
}
function fn_ab__ia_del_join($join_id = 0)
{
if (intval($join_id) > 0) {
db_query('DELETE FROM ?:ab__ia_joins WHERE join_id = ?i', $join_id);
db_query('DELETE FROM ?:ab__ia_join_descriptions WHERE join_id = ?i', $join_id);
db_query('DELETE FROM ?:ab__ia_rules WHERE join_id = ?i', $join_id);
db_query('DELETE FROM ?:ab__ia_data WHERE join_id = ?i', $join_id);
}
}
function fn_ab__intelligent_accessories_delete_category_post($category_id, $recurse, $category_ids)
{
if (is_array($category_ids)) {
db_query('DELETE FROM ?:ab__ia_data
WHERE join_id in
(SELECT j.join_id
FROM ?:ab__ia_joins j
WHERE j.base_category_id in (?n) OR j.joined_category_id in (?n)
)', $category_ids, $category_ids);
db_query('DELETE FROM ?:ab__ia_rules
WHERE join_id in
(SELECT j.join_id
FROM ?:ab__ia_joins j
WHERE j.base_category_id in (?n) OR j.joined_category_id in (?n)
)', $category_ids, $category_ids);
db_query('DELETE FROM ?:ab__ia_join_descriptions
WHERE join_id in
(SELECT j.join_id
FROM ?:ab__ia_joins j
WHERE j.base_category_id in (?n) OR j.joined_category_id in (?n)
)', $category_ids, $category_ids);
db_query('DELETE FROM ?:ab__ia_joins
WHERE base_category_id in (?n) OR joined_category_id in (?n)
', $category_ids, $category_ids);
}
}
function fn_ab__intelligent_accessories_delete_product_post($product_id, $product_deleted)
{
if ($product_deleted && intval($product_id)) {
db_query('DELETE FROM ?:ab__ia_data WHERE product_id = ?i', $product_id);
}
}
function fn_ab__ia_get_list_products_sorting()
{
$default_products_sorting = Settings::instance()->getSettingDataByName('default_products_sorting');
return $default_products_sorting['variants'];
}
function fn_settings_variants_addons_ab__intelligent_accessories_products_sorting_default()
{
return fn_ab__ia_get_list_products_sorting();
}
function fn_settings_variants_addons_ab__intelligent_accessories_join_max_view_products_default()
{
return range(0, (defined('AB__IA_JOIN_MAX_VIEW_PRODUCTS')) ? AB__IA_JOIN_MAX_VIEW_PRODUCTS : 50);
}
function fn_settings_variants_addons_ab__intelligent_accessories_rule_max_view_products_default()
{
return range(0, (defined('AB__IA_RULE_MAX_VIEW_PRODUCTS')) ? AB__IA_RULE_MAX_VIEW_PRODUCTS : 20);
}
function fn_settings_variants_addons_ab__intelligent_accessories_update_cache_every()
{
return [__('ab__ia_off'), 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];
}
function fn_settings_variants_addons_ab__intelligent_accessories_max_ia_tabs_in_added_to_cart()
{
return range(0, 3);
}
function fn_settings_variants_addons_ab__intelligent_accessories_max_products_of_ia_tab_in_added_to_cart()
{
return range(0, 3);
}
function fn_settings_variants_addons_ab__intelligent_accessories_max_ia_products_in_cart()
{
return range(0, 3);
}
function fn_ab__intelligent_accessories_get_products($params, $fields, $sortings, &$condition, &$join, $sorting, $group_by, $lang_code, $having)
{
if (!empty($params['ab__ia_get_products'])) {
if( Registry::get('addons.product_variations.status') == 'A'
&& Registry::get('ab__intelligent_accessories.type') == 'join'
&& Registry::get('addons.ab__intelligent_accessories.exclude_product_variations') == 'Y'){
$condition .= ' AND products.parent_product_id = 0 AND products.product_type = \'P\' ';
}
if (!empty($params['feature_variants_exclude'])) {
$feature_params = array(
'plain' => true,
'variants' => false,
'exclude_group' => true,
'feature_id' => array_keys($params['feature_variants_exclude'])
);
list($features, ) = fn_get_product_features($feature_params, PRODUCT_FEATURES_THRESHOLD);
list($_join, $_condition) = fn_generate_feature_conditions($features, $params['feature_variants_exclude'], '', '', $lang_code);
$_join = str_replace(['var_val_', 'ch_features_'], ['exclude_var_val_', 'exclude_ch_features_'], $_join);
$_condition = str_replace(['var_val_', 'ch_features_', 'IN', '='], ['exclude_var_val_', 'exclude_ch_features_', 'NOT IN', '<>'], $_condition);
$condition .= $_condition;
$join .= $_join;
}
if(!empty($params['neq_feature_variants'])){
foreach ($params['neq_feature_variants'] as $fid => $value) {
$join .= db_quote(" LEFT JOIN ?:product_features_values as neq_var_val_$fid ON neq_var_val_$fid.product_id = products.product_id AND neq_var_val_$fid.lang_code = ?s AND neq_var_val_$fid.feature_id = ?i", $lang_code, $fid);
$condition .= db_quote(" AND neq_var_val_$fid.value_int <> ?d", $value);
}
}
}
}
function fn_ab__ia_update_group($group_data, $group_id = 0, $lang_code = DESCR_SL)
{
if (!$group_id) {
$group_id = db_query('INSERT INTO ?:ab__ia_groups set group_id = NULL');
$group_data['group_id'] = $group_id;
foreach (Languages::getAll() as $group_data['lang_code'] => $item) {
db_replace_into('ab__ia_group_descriptions', $group_data);
}
} else {
$group_data['group_id'] = $group_id;
$group_data['lang_code'] = $lang_code;
db_replace_into('ab__ia_group_descriptions', $group_data);
}
return $group_id;
}
function fn_ab__ia_delete_group($group_id)
{
if ($group_id) {
db_query('DELETE FROM ?:ab__ia_group_descriptions WHERE group_id = ?i', $group_id);
db_query('DELETE FROM ?:ab__ia_groups WHERE group_id = ?i', $group_id);
db_query('UPDATE ?:ab__ia_joins SET group_id = ?i WHERE group_id = ?i', 0, $group_id);
}
}
function fn_ab__ia_get_groups($params = [], $lang_code = DESCR_SL)
{
$default_params = [
'group_id' => '',
];
$join = $condition = $limit = '';
$params = array_merge($default_params, $params);
$fields = [
'g.group_id',
'gd.name',
];
$join .= db_quote(' LEFT JOIN ?:ab__ia_group_descriptions gd ON g.group_id = gd.group_id AND gd.lang_code = ?s ', $lang_code);
if (!empty($params['group_id'])) {
$condition .= db_quote(' AND g.group_id = ?i', $params['group_id']);
}
if (!empty($params['items_per_page'])) {
$params['total_items'] = db_get_field("SELECT COUNT(distinct g.group_id) FROM ?:ab__ia_groups as g $join WHERE 1 $condition");
$limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
}
$groups = db_get_hash_array('SELECT ' . implode(',', $fields) . ' FROM ?:ab__ia_groups as g ' . $join . ' WHERE 1 ' . $condition . ' ' . $limit, 'group_id');
return [$groups, $params];
}
function fn_ab__ia_get_groups_for_block()
{
$retval[0] = '-- ' . __('ab__ia.groups.without_group') . ' --';
$retval[-1] = '-- ' . __('all') . ' --';
[$groups, $search] = fn_ab__ia_get_groups();
foreach ($groups as $group_id => $group) {
$retval[$group_id] = $group['name'];
}
return $retval;
}
function fn_ab__ia_group_is_available($group_id)
{
static $groups;
if (!$groups) {
list($groups, $search) = fn_ab__ia_get_groups();
$groups[0] = true;
$search = null;
}
return isset($groups[$group_id]);
}
function fn_ab__ia_antibot_hide_content()
{
$hide_content = false;
if (Registry::get('addons.ab__antibot.status') == 'A') {
$bot = fn_ab__ab_is_bot();
if ($bot != BotTypes::NOT_BOT) {
$hide_content = true;
}
}
return $hide_content;
}
function fn_ab__ia_clone_join($join_id)
{
if(!$join_id){
return false;
}
$old_join_data = db_get_row('SELECT * FROM ?:ab__ia_joins WHERE join_id = ?i', $join_id);
unset($old_join_data['join_id']);
$old_join_data['status'] = 'D';
$old_join_descriptions = db_get_array('SELECT * FROM ?:ab__ia_join_descriptions WHERE join_id = ?i', $join_id);
$old_rules = db_get_array('SELECT * FROM ?:ab__ia_rules WHERE join_id = ?i', $join_id);
$new_join_id = db_query('INSERT INTO ?:ab__ia_joins ?e', $old_join_data);
foreach ($old_join_descriptions as &$old_join_description) {
$old_join_description['join_id'] = $new_join_id;
$old_join_description['name'] .= '_CLONE';
}
db_replace_into('ab__ia_join_descriptions',$old_join_descriptions,true);
foreach ($old_rules as &$old_rule) {
$old_rule['join_id'] = $new_join_id;
unset($old_rule['rule_id']);
}
db_replace_into('ab__ia_rules',$old_rules,true);
return $new_join_id;
}
function fn_ab__ia_get_available_operators(){
return Operators::getAll();
}
function fn_ab__ia_get_logical_operators(){
return Operators::getLogical();
}
function fn_ab__intelligent_accessories_render_block_pre(&$block, &$block_schema, $params, $block_content){
if($block['type'] === 'ab__intelligent_accessories'){
$block_schema['templates']['addons/ab__intelligent_accessories/blocks/ab__ia_products_scroller.tpl'] = [];
$block['properties']['template'] = 'addons/ab__intelligent_accessories/blocks/ab__ia_products_scroller.tpl';
}
}

function fn_ab__intelligent_accessories_get_products_post(&$products, $params){
if(!empty($params['ab__ia_get_joined_products']) && Registry::get('addons.master_products.status') === 'A'){
foreach ($products as &$product) {
$product['best_product_offer_id'] = $product['product_id'];
}
}
}

function fn_ab__intelligent_accessories_get_product_features_post(&$data, $params, $has_ungroupped){
if(Registry::ifGet('ab__intelligent_accessories.type', 'false') && $slider_features_as_selectbox = Registry::get('ab__intelligent_accessories.slider_features_as_selectbox')){
foreach ($slider_features_as_selectbox as $feature_id) {
if(isset($data[$feature_id])){
$data[$feature_id]['filter_style'] = ProductFilterStyles::CHECKBOX;
$data[$feature_id]['feature_type'] = ProductFeatures::TEXT_SELECTBOX;
}
}
}
}
