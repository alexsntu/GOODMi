<?php
// GOODMi: новое каталог-меню «Каталог GOODMi (2026)» по дереву категорий, 2026-10-14.
// Старое меню №26 не трогается. Каждый пункт 1-го уровня – категория с автоподменю (param_3 = C:<id>:Y):
// подуровни CS-Cart берёт из дерева категорий сам. CSS-класс пункта – для SVG-иконок в «Своём CSS».
// php catalog-menu-create.php [--apply] – от пользователя maxmobiles, из папки вне /root. Что создано – в created-catalog-menu.json рядом.
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru"); define("AREA", "A"); define("ACCOUNT_TYPE", "admin");
$_SERVER["HTTP_HOST"] = "goodmi.ru"; $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; $_SERVER["REQUEST_METHOD"] = "GET"; $_SERVER["REQUEST_URI"] = "/";
require "init.php";
$apply = in_array("--apply", $argv, true);
$menu_name = "Каталог GOODMi (2026)";

// порядок задан пользователем: по дереву категорий, «Уценка» – последней
$items = [
    [412, "phones"], [448, "tablets"], [449, "laptops"], [454, "tv"], [480, "watches"], [482, "audio"],
    [483, "power"], [553, "vacuum"], [484, "home"], [588, "kitchen"], [485, "beauty"], [622, "smarthome"],
    [469, "auto"], [2567, "hobby"], [460, "accessories"], [490, "gift"], [701, "sale"],
];

if (db_get_field("SELECT m.menu_id FROM ?:menus m JOIN ?:menus_descriptions d ON d.menu_id = m.menu_id AND d.lang_code = 'ru' WHERE m.company_id = 2 AND d.name = ?s", $menu_name)) {
    echo "меню «{$menu_name}» уже есть – ничего не делаю\n"; exit(1);
}
$langs = db_get_fields("SELECT lang_code FROM ?:languages");
echo "языки: ", implode(",", $langs), "\n";
foreach ($items as $n => $it) {
    list($cat, $slug) = $it;
    $c = db_get_row("SELECT c.status, c.company_id, d.category FROM ?:categories c JOIN ?:category_descriptions d ON d.category_id = c.category_id AND d.lang_code = 'ru' WHERE c.category_id = ?i", $cat);
    if (!$c || $c["company_id"] != 2 || $c["status"] !== "A") { echo "ОШИБКА: категория $cat не найдена или не активна у GOODMi\n"; exit(1); }
    printf("%2d. %-40s C:%d:Y  class gm-mi gm-mi--%s\n", $n + 1, $c["category"], $cat, $slug);
}
if (!$apply) { echo "ПРОБНЫЙ ПРОГОН: изменений нет\n"; exit(0); }

$created = ["menu_id" => 0, "items" => []];
$menu = db_query("INSERT INTO ?:menus ?e", ["status" => "A", "company_id" => 2]);
foreach ($langs as $l) { db_query("INSERT INTO ?:menus_descriptions ?e", ["menu_id" => $menu, "lang_code" => $l, "name" => $menu_name]); }
$created["menu_id"] = (int) $menu;
foreach ($items as $n => $it) {
    list($cat, $slug) = $it;
    $id = db_query("INSERT INTO ?:static_data ?e", [
        "param" => "", "param_2" => "", "param_3" => "C:$cat:Y", "param_4" => "", "param_5" => (string) $menu, "param_6" => "N",
        "class" => "gm-mi gm-mi--$slug", "section" => "A", "status" => "A", "position" => ($n + 1) * 10, "parent_id" => 0,
        "localization" => "", "company_id" => 2, "abt__ut2_mwi__status" => "N",
    ]);
    db_query("UPDATE ?:static_data SET id_path = ?s WHERE param_id = ?i", (string) $id, $id);
    foreach ($langs as $l) {
        $name = db_get_field("SELECT category FROM ?:category_descriptions WHERE category_id = ?i AND lang_code = ?s", $cat, $l)
            ?: db_get_field("SELECT category FROM ?:category_descriptions WHERE category_id = ?i AND lang_code = 'ru'", $cat);
        db_query("INSERT INTO ?:static_data_descriptions ?e", ["param_id" => $id, "lang_code" => $l, "descr" => $name]);
    }
    $created["items"][] = (int) $id;
}
file_put_contents(__DIR__ . "/created-catalog-menu.json", json_encode($created, JSON_UNESCAPED_UNICODE));
echo "СОЗДАНО: меню №{$menu}, пунктов ", count($created["items"]), "\n";
