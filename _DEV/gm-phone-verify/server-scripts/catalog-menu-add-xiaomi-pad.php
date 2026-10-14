<?php
// GOODMi: «Xiaomi Pad» с моделями в разделе «Планшеты» меню №379 (2026-10-14).
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru"); define("AREA", "A"); define("ACCOUNT_TYPE", "admin");
$_SERVER["HTTP_HOST"] = "goodmi.ru"; $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; $_SERVER["REQUEST_METHOD"] = "GET"; $_SERVER["REQUEST_URI"] = "/";
require "init.php";
if (db_get_field("SELECT 1 FROM ?:static_data s JOIN ?:static_data_descriptions d ON d.param_id = s.param_id WHERE s.parent_id = 7138 AND d.descr = ?s", "Xiaomi Pad")) { echo "уже есть\n"; exit(1); }
$add = function ($parent, $path, $name, $url, $pos) {
    $id = db_query("INSERT INTO ?:static_data ?e", ["param" => $url, "param_2" => "", "param_3" => "", "param_4" => "", "param_5" => "379", "param_6" => "N", "class" => "", "section" => "A", "status" => "A", "position" => $pos, "parent_id" => $parent, "localization" => "", "company_id" => 2, "abt__ut2_mwi__status" => "N"]);
    db_query("UPDATE ?:static_data SET id_path = ?s WHERE param_id = ?i", "$path/$id", $id);
    db_query("INSERT INTO ?:static_data_descriptions ?e", ["param_id" => $id, "lang_code" => "ru", "descr" => $name]);
    return $id;
};
$x = $add(7138, "7138", "Xiaomi Pad", "/planshetyi/xiaomi-planshety/", 5);
$a = $add($x, "7138/$x", "Xiaomi Pad 8 Pro", "/planshetyi/xiaomi-pad-8-pro/", 10);
$b = $add($x, "7138/$x", "Xiaomi Pad 8", "/planshetyi/xiaomi-pad-8/", 20);
file_put_contents(__DIR__ . "/created-xiaomi-pad.json", json_encode([$x, $a, $b]));
echo "СОЗДАНО: $x, $a, $b\n";
