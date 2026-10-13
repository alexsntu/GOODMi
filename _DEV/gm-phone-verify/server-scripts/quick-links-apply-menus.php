<?php
// GOODMi: правки меню блока быстрых ссылок (блок 1284) по плану plan.json, 2026-10-13.
// Запускать от пользователя maxmobiles: php apply_menus.php plan.json [--apply]. Прежнее состояние – в before.json рядом.
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru"); define("AREA", "A"); define("ACCOUNT_TYPE", "admin");
$_SERVER["HTTP_HOST"] = "goodmi.ru"; $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; $_SERVER["REQUEST_METHOD"] = "GET"; $_SERVER["REQUEST_URI"] = "/";
require "init.php";
$plan = json_decode(file_get_contents($argv[1]), true); $apply = in_array("--apply", $argv, true); $dir = dirname($argv[1]);
if (!is_array($plan)) { echo "план не прочитан\n"; exit(1); }
$ids = array_merge(array_column($plan["disable"], "param_id"), array_column($plan["fix"], "param_id"));
$menus = array_values(array_unique(array_column($plan["add"], "menu")));
$before = [
    "time" => date("c"),
    "static_data" => db_get_array("SELECT param_id, param, status, position, param_5 FROM ?:static_data WHERE param_id IN (?n) OR (section = 'A' AND param_5 IN (?a))", $ids ?: [0], $menus ?: ['0']),
    "statuses" => db_get_array("SELECT * FROM ?:bm_block_statuses WHERE snapping_id = 4917"),
];
echo "пунктов к отключению: ", count($plan["disable"]), ", адресов к исправлению: ", count($plan["fix"]), ", к добавлению: ", count($plan["add"]), ", включить блок: ", count($plan["enable"]), ", выключить: ", count($plan["block_off"]), "\n";
// проверка: все пункты существуют и принадлежат витрине GOODMi
$own = db_get_fields("SELECT param_id FROM ?:static_data WHERE param_id IN (?n) AND company_id = 2 AND section = 'A'", $ids ?: [0]);
if (count($own) !== count(array_unique($ids))) { echo "ОШИБКА: не все пункты найдены у GOODMi (", count($own), " из ", count(array_unique($ids)), ")\n"; exit(1); }
if (!$apply) { echo "ПРОБНЫЙ ПРОГОН: проверки пройдены, изменений нет\n"; exit(0); }
if (file_put_contents("$dir/before.json", json_encode($before, JSON_UNESCAPED_UNICODE)) < 100) { echo "ОШИБКА: не записан файл отката\n"; exit(1); }
db_query("UPDATE ?:static_data SET status = 'D' WHERE param_id IN (?n)", array_column($plan["disable"], "param_id") ?: [0]);
foreach ($plan["fix"] as $f) { db_query("UPDATE ?:static_data SET param = ?s WHERE param_id = ?i", $f["new"], $f["param_id"]); }
$added = [];
$by_menu = []; foreach ($plan["add"] as $a) { $by_menu[$a["menu"]][] = $a; }
foreach ($by_menu as $menu => $list) {
    // новые пункты ставим перед ссылками «В наличии в <городе>», города сдвигаем
    $city_pos = db_get_field("SELECT MIN(s.position) FROM ?:static_data s JOIN ?:static_data_descriptions d ON d.param_id = s.param_id AND d.lang_code = 'ru' WHERE s.section = 'A' AND s.param_5 = ?s AND s.status = 'A' AND d.descr LIKE ?l", (string) $menu, 'В наличии%');
    $max_pos = (int) db_get_field("SELECT MAX(position) FROM ?:static_data WHERE section = 'A' AND param_5 = ?s", (string) $menu);
    if ($city_pos !== null && $city_pos !== '') {
        $start = (int) $city_pos;
        db_query("UPDATE ?:static_data s JOIN ?:static_data_descriptions d ON d.param_id = s.param_id AND d.lang_code = 'ru' SET s.position = s.position + ?i WHERE s.section = 'A' AND s.param_5 = ?s AND d.descr LIKE ?l", count($list), (string) $menu, 'В наличии%');
    } else {
        $start = $max_pos + 1;
    }
    foreach ($list as $n => $a) {
        $id = db_query("INSERT INTO ?:static_data ?e", ["param" => $a["url"], "param_5" => (string) $menu, "param_6" => "N", "section" => "A", "status" => "A", "position" => $start + $n, "parent_id" => 0, "company_id" => 2]);
        db_query("UPDATE ?:static_data SET id_path = ?s WHERE param_id = ?i", (string) $id, $id);
        db_query("INSERT INTO ?:static_data_descriptions ?e", ["param_id" => $id, "lang_code" => "ru", "descr" => $a["title"]]);
        $added[] = (int) $id;
    }
}
$row = db_get_row("SELECT * FROM ?:bm_block_statuses WHERE snapping_id = 4917 AND object_type = 'categories'");
$on = array_values(array_filter(array_map("intval", explode(",", (string) $row["object_ids"]))));
$on = array_values(array_unique(array_merge(array_diff($on, array_map("intval", $plan["block_off"])), array_map("intval", $plan["enable"]))));
sort($on);
db_query("UPDATE ?:bm_block_statuses SET object_ids = ?s WHERE snapping_id = 4917 AND object_type = 'categories'", implode(",", $on));
file_put_contents("$dir/added.json", json_encode($added));
\Tygh\Registry::cleanup();
echo "ВЫПОЛНЕНО: отключено ", count($plan["disable"]), ", исправлено ", count($plan["fix"]), ", добавлено ", count($added), ", блок включён в ", count($on), " категориях\n";
