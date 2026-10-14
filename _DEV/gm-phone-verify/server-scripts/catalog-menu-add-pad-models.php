<?php
// GOODMi: модели Redmi Pad и POCO Pad в разделе «Планшеты» меню №379 (2026-10-14).
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru"); define("AREA", "A"); define("ACCOUNT_TYPE", "admin");
$_SERVER["HTTP_HOST"] = "goodmi.ru"; $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; $_SERVER["REQUEST_METHOD"] = "GET"; $_SERVER["REQUEST_URI"] = "/";
require "init.php";
$plan = [
    "Redmi Pad" => [["Redmi Pad 2 Pro 5G", "/planshetyi/redmi-pad-2-pro-5g/"], ["Redmi Pad 2 Pro", "/planshetyi/redmi-pad-2-pro/"], ["Redmi Pad 2", "/planshetyi/redmi-pad-2/"], ["Redmi Pad 2 9.7 4G", "/planshetyi/redmi-pad-2-9.7-4g/"], ["Redmi Pad 2 9.7", "/planshetyi/redmi-pad-2-9.7/"]],
    "POCO Pad" => [["POCO Pad X1", "/planshetyi/poco-pad-x1/"], ["POCO Pad C1", "/planshetyi/poco-pad-c1/"], ["POCO Pad M1", "/planshetyi/poco-pad-m1/"], ["POCO Pad", "/planshetyi/poco-pad/"]],
];
$created = [];
foreach ($plan as $parent_name => $models) {
    $p = db_get_row("SELECT s.param_id, s.id_path FROM ?:static_data s JOIN ?:static_data_descriptions d ON d.param_id = s.param_id AND d.lang_code = \"ru\" WHERE s.parent_id = 7138 AND s.param_5 = \"379\" AND d.descr = ?s", $parent_name);
    if (!$p) { echo "нет пункта $parent_name\n"; exit(1); }
    if (db_get_field("SELECT COUNT(*) FROM ?:static_data WHERE parent_id = ?i", $p["param_id"])) { echo "у $parent_name уже есть подпункты\n"; exit(1); }
    foreach ($models as $n => $m) {
        $id = db_query("INSERT INTO ?:static_data ?e", ["param" => $m[1], "param_2" => "", "param_3" => "", "param_4" => "", "param_5" => "379", "param_6" => "N", "class" => "", "section" => "A", "status" => "A", "position" => ($n + 1) * 10, "parent_id" => $p["param_id"], "localization" => "", "company_id" => 2, "abt__ut2_mwi__status" => "N"]);
        db_query("UPDATE ?:static_data SET id_path = ?s WHERE param_id = ?i", $p["id_path"] . "/" . $id, $id);
        db_query("INSERT INTO ?:static_data_descriptions ?e", ["param_id" => $id, "lang_code" => "ru", "descr" => $m[0]]);
        $created[] = (int) $id;
    }
}
file_put_contents(__DIR__ . "/created-pad-models.json", json_encode($created));
echo "СОЗДАНО: ", implode(",", $created), "\n";
