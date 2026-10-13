<?php
// GOODMi: блок быстрых ссылок (1284) – новые меню для категорий без блока и дополнение меню смартфонов, 2026-10-13.
// php apply_menus2.php plan3.json [--apply] – от пользователя maxmobiles. Что создано – в created.json рядом.
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru"); define("AREA", "A"); define("ACCOUNT_TYPE", "admin");
$_SERVER["HTTP_HOST"] = "goodmi.ru"; $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; $_SERVER["REQUEST_METHOD"] = "GET"; $_SERVER["REQUEST_URI"] = "/";
require "init.php";
$plan = json_decode(file_get_contents($argv[1]), true); $apply = in_array("--apply", $argv, true); $dir = dirname($argv[1]);
if (!is_array($plan)) { echo "план не прочитан\n"; exit(1); }
$created = ["menus" => [], "items" => [], "content" => [], "statuses_before" => db_get_field("SELECT object_ids FROM ?:bm_block_statuses WHERE snapping_id = 4917 AND object_type = 'categories'")];
$enable = [];
$add_item = function ($menu, $title, $url, $pos) use (&$created) {
    $id = db_query("INSERT INTO ?:static_data ?e", ["param" => $url, "param_5" => (string) $menu, "param_6" => "N", "section" => "A", "status" => "A", "position" => $pos, "parent_id" => 0, "company_id" => 2]);
    db_query("UPDATE ?:static_data SET id_path = ?s WHERE param_id = ?i", (string) $id, $id);
    db_query("INSERT INTO ?:static_data_descriptions ?e", ["param_id" => $id, "lang_code" => "ru", "descr" => $title]);
    $created["items"][] = (int) $id;
};
foreach ($plan["new_menus"] as $p) {
    $cat = (int) $p["cat"];
    $ok = db_get_field("SELECT 1 FROM ?:categories WHERE category_id = ?i AND company_id = 2 AND status = 'A'", $cat);
    $has = db_get_field("SELECT 1 FROM ?:bm_blocks_content WHERE block_id = 1284 AND object_id = ?i AND object_type = 'categories'", $cat);
    if (!$ok || $has) { echo "пропуск: ", $p["name"], $has ? " (меню уже привязано)" : " (категория не найдена)", "\n"; continue; }
    echo "новое меню: ", $p["name"], " – пунктов ", count($p["items"]), "\n";
    if (!$apply) { continue; }
    $menu = db_query("INSERT INTO ?:menus ?e", ["status" => "A", "company_id" => 2]);
    db_query("INSERT INTO ?:menus_descriptions ?e", ["menu_id" => $menu, "lang_code" => "ru", "name" => "SEO-меню " . $p["name"]]);
    foreach ($p["items"] as $n => $i) { $add_item($menu, $i["title"], $i["url"], ($n + 1) * 10); }
    db_query("INSERT INTO ?:bm_blocks_content ?e", ["snapping_id" => 0, "object_id" => $cat, "object_type" => "categories", "block_id" => 1284, "lang_code" => "ru", "content" => serialize(["menu" => (string) $menu])]);
    $created["menus"][] = (int) $menu; $created["content"][] = $cat; $enable[] = $cat;
}
foreach ($plan["adds"] as $a) {
    $menu = (int) $a["menu"];
    $own = db_get_field("SELECT 1 FROM ?:menus WHERE menu_id = ?i AND company_id = 2", $menu);
    $have = db_get_fields("SELECT param FROM ?:static_data WHERE section = 'A' AND param_5 = ?s AND status = 'A'", (string) $menu);
    $have = array_map(function ($u) { return rtrim(preg_replace('#^https?://goodmi\.ru#', '', $u), '/'); }, $have);
    $max = (int) db_get_field("SELECT MAX(position) FROM ?:static_data WHERE section = 'A' AND param_5 = ?s", (string) $menu);
    $n = 0;
    foreach ($a["items"] as $i) {
        if (!$own || in_array(rtrim(preg_replace('#^https?://goodmi\.ru#', '', $i["url"]), '/'), $have, true)) { continue; }
        $n++;
        if ($apply) { $add_item($menu, $i["title"], $i["url"], $max + $n * 10); }
    }
    echo "дополнение: ", $a["name"], " (меню №$menu) – добавляется ", $n, " из ", count($a["items"]), "\n";
    if ($a["enable"] && $own) { $enable[] = (int) $a["cat"]; }
}
if (!$apply) { echo "ПРОБНЫЙ ПРОГОН: изменений нет\n"; exit(0); }
$on = array_values(array_unique(array_merge(array_filter(array_map("intval", explode(",", (string) $created["statuses_before"]))), $enable)));
sort($on);
db_query("UPDATE ?:bm_block_statuses SET object_ids = ?s WHERE snapping_id = 4917 AND object_type = 'categories'", implode(",", $on));
file_put_contents("$dir/created.json", json_encode($created, JSON_UNESCAPED_UNICODE));
\Tygh\Registry::cleanup();
echo "ВЫПОЛНЕНО: меню создано ", count($created["menus"]), ", пунктов добавлено ", count($created["items"]), ", блок включён в ", count($on), " категориях\n";
