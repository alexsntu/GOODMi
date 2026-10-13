<?php
// GOODMi: страницы-подборки «Чехлы для <модель>» (модуль SEO-фильтров), 2026-10-13.
// Для каждой модели телефона, у которой в наличии 3+ чехла и страницы ещё нет. Одно значение одного фильтра
// (правило: не больше трёх значений одного фильтра в хеше). Создание – штатной функцией модуля ABSF::update_name.
// php create_sf_cases.php [--apply] [--menu=N] – от пользователя maxmobiles; что создано – created-sf.json рядом.
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru"); define("AREA", "C");
$_SERVER["HTTP_HOST"] = "goodmi.ru"; $_SERVER["SERVER_NAME"] = "goodmi.ru"; $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; $_SERVER["REQUEST_METHOD"] = "GET"; $_SERVER["REQUEST_URI"] = "/";
require "init.php";
use Tygh\Registry;
$apply = in_array("--apply", $argv, true); $dir = __DIR__;
$CAT = 675; $FILTER = 674; $MIN = 3; $TO_MENU = 8;
if ((int) Registry::get("runtime.company_id") !== 2) { echo "не витрина GOODMi\n"; exit(1); }
$feature = (int) db_get_field("SELECT feature_id FROM ?:product_filters WHERE filter_id = ?i", $FILTER);
$have = db_get_fields("SELECT SUBSTRING_INDEX(features_hash, '-', -1) FROM ?:ab__sf_names WHERE category_id = ?i AND company_id = 2 AND feature_count = 1 AND features_hash LIKE ?l", $CAT, $FILTER . '-%');
$slugs = db_get_fields("SELECT d.name FROM ?:ab__sf_names n JOIN ?:ab__sf_name_descriptions d ON d.sf_id = n.sf_id AND d.lang_code = 'ru' WHERE n.category_id = ?i AND n.company_id = 2", $CAT);
$rows = db_get_array(
    "SELECT v.variant_id, vd.variant, COUNT(DISTINCT pr.product_id) c FROM ?:product_features_values v"
    . " JOIN ?:product_feature_variant_descriptions vd ON vd.variant_id = v.variant_id AND vd.lang_code = 'ru'"
    . " JOIN ?:products pr ON pr.product_id = v.product_id AND pr.status = 'A' AND pr.amount > 0 AND pr.company_id = 2"
    . " JOIN ?:products_categories pc ON pc.product_id = pr.product_id AND pc.category_id = ?i"
    . " WHERE v.feature_id = ?i AND v.lang_code = 'ru' GROUP BY v.variant_id HAVING c >= ?i ORDER BY c DESC, vd.variant", $CAT, $feature, $MIN);
$created = []; $n = 0;
foreach ($rows as $r) {
    if (in_array($r["variant_id"], $have)) { continue; }
    $model = trim(preg_replace('/\s+/', ' ', str_replace(['(', ')'], '', $r["variant"])));
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(str_replace('+', ' plus', $model))), '-');
    if ($slug === '' || in_array($slug, $slugs, true)) { echo "пропуск (адрес занят или пуст): $model\n"; continue; }
    $title = "Чехлы для $model – купить в Севастополе, Крыму | GOODMi";
    if (mb_strlen($title) > 70) { $title = "Чехлы для $model – купить в Севастополе | GOODMi"; }
    $descr = "Чехлы для $model в Севастополе и Крыму – модели точно под телефон: вырезы под камеру, кнопки и разъёмы, 100% оригинальные товары. Выбирайте в каталоге чехлы и бамперы для $model. 7500+ оценок и отзывов, с 2016 года.";
    if (mb_strlen($descr) > 250) { $descr = "Чехлы для $model в Севастополе и Крыму – модели точно под телефон: вырезы под камеру, кнопки и разъёмы, 100% оригинальные товары. Выбирайте чехлы и бамперы в каталоге. 7500+ оценок и отзывов, с 2016 года."; }
    $data = [
        "category_id" => $CAT, "features_hash" => $FILTER . "-" . $r["variant_id"], "name" => $slug, "fixed" => "F",
        "show_in_breadcrumbs" => "N", "show_in_subcategory_block" => "N", "is_noindex" => "N", "position_in_subcategory_block" => 0,
        "name_in_subcategory_block" => "", "description" => "", "short_description" => "", "meta_keywords" => "",
        "meta_description" => $descr, "page_title" => $title, "tag_h1" => "Чехлы для $model", "breadcrumb" => "Для $model",
        "product_breadcrumb" => "", "canonical_id" => 0,
    ];
    $n++;
    if (!$apply) { if ($n <= 4) { echo "  $model ({$r['c']}) → /chehlyi/$slug/ | title ", mb_strlen($title), " зн., description ", mb_strlen($descr), " зн.\n"; } continue; }
    $id = \Tygh\ABSF::update_name($data, 0, "ru");
    if (!$id) { echo "ОШИБКА создания: $model\n"; continue; }
    $slugs[] = $slug;
    $created[] = ["sf_id" => (int) $id, "model" => $model, "slug" => $slug, "goods" => (int) $r["c"], "url" => "https://goodmi.ru/chehlyi/$slug/"];
}
echo $apply ? "создано страниц: " . count($created) . "\n" : "ПРОБНЫЙ ПРОГОН: к созданию $n страниц, изменений нет\n";
if (!$apply) { exit(0); }
// в блок быстрых ссылок категории – первые $TO_MENU новых (по числу чехлов), после имеющихся пунктов
$content = db_get_field("SELECT content FROM ?:bm_blocks_content WHERE block_id = 1284 AND object_id = ?i AND object_type = 'categories'", $CAT);
$u = @unserialize((string) $content); $menu = !empty($u["menu"]) ? (int) $u["menu"] : 0; $items = [];
if ($menu) {
    $pos = (int) db_get_field("SELECT MAX(position) FROM ?:static_data WHERE section = 'A' AND param_5 = ?s", (string) $menu);
    foreach (array_slice($created, 0, $TO_MENU) as $k => $c) {
        $pid = db_query("INSERT INTO ?:static_data ?e", ["param" => $c["url"], "param_5" => (string) $menu, "param_6" => "N", "section" => "A", "status" => "A", "position" => $pos + ($k + 1) * 10, "parent_id" => 0, "company_id" => 2]);
        db_query("UPDATE ?:static_data SET id_path = ?s WHERE param_id = ?i", (string) $pid, $pid);
        db_query("INSERT INTO ?:static_data_descriptions ?e", ["param_id" => $pid, "lang_code" => "ru", "descr" => "Для " . $c["model"]]);
        $items[] = (int) $pid;
    }
}
file_put_contents("$dir/created-sf.json", json_encode(["time" => date("c"), "pages" => $created, "menu" => $menu, "menu_items" => $items], JSON_UNESCAPED_UNICODE));
Registry::cleanup();
echo "в блок быстрых ссылок (меню №$menu) добавлено: ", count($items), "\n";
