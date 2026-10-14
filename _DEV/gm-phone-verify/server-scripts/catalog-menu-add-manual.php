<?php
// GOODMi: ручные пункты в каталог-меню №379 (2026-10-14) – перекрёстные ссылки, которых нет в дереве категорий
// (аксессуары к смартфонам в «Смартфонах», бренды планшетов, Яндекс Станции и т. п.). CS-Cart показывает их после
// пунктов, построенных из дерева. Категории – ссылкой categories.view (не ломается при смене адреса), подборки – адресом.
// php catalog-menu-add-manual.php [--apply] – от пользователя maxmobiles, из папки вне /root. Созданное – в created-manual.json рядом.
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru"); define("AREA", "A"); define("ACCOUNT_TYPE", "admin");
$_SERVER["HTTP_HOST"] = "goodmi.ru"; $_SERVER["REMOTE_ADDR"] = "127.0.0.1"; $_SERVER["REQUEST_METHOD"] = "GET"; $_SERVER["REQUEST_URI"] = "/";
require "init.php";
$apply = in_array("--apply", $argv, true);
$menu = 379;

// раздел меню (param_id пункта 1-го уровня) => [название, ссылка (cat:<slug> | путь), [3-й уровень]]
$plan = [
    7137 => [ // Смартфоны
        ["Аксессуары для смартфонов", "cat:dlya-smartfonov", [
            ["Чехлы", "cat:chehlyi"], ["Защитные стёкла", "cat:stekla"], ["Зарядки", "cat:adaptery-pitaniya-zaryadnyie-ustroystva"],
            ["Кабели", "cat:kabelya"], ["Повербанки", "cat:power-banks"], ["Беспроводные зарядки", "cat:besprovodnye-zaryadki"],
            ["Автодержатели", "cat:derzhateli-dlya-smartfonov"], ["Моноподы", "cat:monopodyi"], ["Карты памяти", "/kartyi-pamyati/"],
            ["Беспроводные наушники", "cat:besprovodnye-naushniki"],
        ]],
    ],
    7138 => [ // Планшеты
        ["Redmi Pad", "/planshetyi/redmi/", []],
        ["POCO Pad", "/planshetyi/poco/", []],
        ["Аксессуары для планшетов", "cat:dlya-planshetov", [
            ["Чехлы и клавиатуры", "cat:chehlyi-dlya-planshetov"], ["Стёкла", "cat:stekla-dlya-planshetov"], ["Стилусы", "cat:stilusy"],
        ]],
    ],
    7141 => [ // Смарт-часы и браслеты
        ["Ремешки и зарядки", "cat:dlya-gadzhetov", [
            ["Ремешки 20 мм", "/dlya-gadzhetov/remeshok-20mm/"], ["Ремешки 22 мм", "/dlya-gadzhetov/22-mm/"],
            ["Силиконовые", "/dlya-gadzhetov/silikonovye/"], ["Кожаные", "/dlya-gadzhetov/iz-kozi/"], ["Нейлоновые", "/dlya-gadzhetov/nejlonovye/"],
        ]],
        ["Умные часы Xiaomi", "/smart-chasy/smart-chasy-xiaomi/", []],
        ["Умные часы Amazfit", "/smart-chasy/smart-chasy-amazfit/", []],
    ],
    7142 => [ // Наушники и колонки
        ["Яндекс Станции", "/akustika-i-kolonki/umnye-kolonki/", [
            ["Станция 3", "/akustika-i-kolonki/stantsiya-3/"], ["Станция Лайт 2", "/akustika-i-kolonki/stantsiya-layt-2/"],
            ["Станция Мини 3", "/akustika-i-kolonki/stantsiya-mini-3/"], ["Станция Мини 3 Про", "/akustika-i-kolonki/stantsiya-mini-3-pro/"],
            ["Станция Миди", "/akustika-i-kolonki/stantsiya-midi/"], ["Станция Стрит", "/akustika-i-kolonki/stantsiya-street/"],
        ]],
        ["Саундбары", "cat:soundbary-xiaomi", []],
    ],
    7148 => [ // Умный дом
        ["Умное освещение", "cat:osweshenie", [
            ["Умные лампы", "cat:smart-lampi"], ["Настольные лампы", "cat:nastolnie-lampi"],
            ["Ночники и светильники", "cat:svetilniki-i-nochniki"], ["Светодиодные ленты", "cat:svetodiodnye-lenty"],
        ]],
    ],
    7151 => [ // Аксессуары
        ["Для ноутбуков", "cat:" . "498", []],
        ["Для наушников", "cat:aksessuary-dlya-naushnikov", []],
    ],
    7149 => [ // Авто и электротранспорт
        ["Аксессуары для электротранспорта", "cat:dlya-elektrotransporta", []],
    ],
];

$resolve = function ($ref) {
    if (strpos($ref, "cat:") !== 0) { return [$ref, "адрес"]; }
    $key = substr($ref, 4);
    $id = ctype_digit($key) ? (int) $key
        : (int) db_get_field("SELECT object_id FROM ?:seo_names WHERE type = 'c' AND company_id = 2 AND lang_code = 'ru' AND name = ?s", $key);
    $ok = $id && db_get_field("SELECT 1 FROM ?:categories WHERE category_id = ?i AND company_id = 2 AND status = 'A'", $id);
    if (!$ok) { echo "ОШИБКА: категория $ref не найдена или не активна\n"; exit(1); }
    return ["categories.view?category_id=$id", "категория $id"];
};

$created = [];
$add = function ($parent, $id_path, $name, $url, $pos) use ($menu, &$created) {
    $id = db_query("INSERT INTO ?:static_data ?e", [
        "param" => $url, "param_2" => "", "param_3" => "", "param_4" => "", "param_5" => (string) $menu, "param_6" => "N",
        "class" => "", "section" => "A", "status" => "A", "position" => $pos, "parent_id" => $parent, "localization" => "",
        "company_id" => 2, "abt__ut2_mwi__status" => "N",
    ]);
    db_query("UPDATE ?:static_data SET id_path = ?s WHERE param_id = ?i", $id_path . "/" . $id, $id);
    db_query("INSERT INTO ?:static_data_descriptions ?e", ["param_id" => $id, "lang_code" => "ru", "descr" => $name]);
    $created[] = (int) $id;
    return $id;
};

foreach ($plan as $root => $l2s) {
    $r = db_get_row("SELECT s.param_id, s.id_path, d.descr FROM ?:static_data s JOIN ?:static_data_descriptions d ON d.param_id = s.param_id AND d.lang_code = 'ru' WHERE s.param_id = ?i AND s.param_5 = ?s AND s.parent_id = 0", $root, (string) $menu);
    if (!$r) { echo "ОШИБКА: пункт $root не в меню $menu\n"; exit(1); }
    if (db_get_field("SELECT COUNT(*) FROM ?:static_data WHERE parent_id = ?i", $root)) { echo "ОШИБКА: у пункта {$r['descr']} уже есть ручные подпункты – ничего не делаю\n"; exit(1); }
    echo "\n", $r["descr"], ":\n";
    foreach ($l2s as $n => $l2) {
        list($name, $ref, $l3s) = $l2;
        list($url, $what) = $resolve($ref);
        echo "  + $name – $url ($what)\n";
        $id2 = $apply ? $add($root, $r["id_path"], $name, $url, ($n + 1) * 10) : 0;
        foreach ($l3s as $m => $l3) {
            list($url3, $what3) = $resolve($l3[1]);
            echo "      · {$l3[0]} – $url3 ($what3)\n";
            if ($apply) { $add($id2, $r["id_path"] . "/" . $id2, $l3[0], $url3, ($m + 1) * 10); }
        }
    }
}
if (!$apply) { echo "\nПРОБНЫЙ ПРОГОН: изменений нет\n"; exit(0); }
file_put_contents(__DIR__ . "/created-manual.json", json_encode(["menu_id" => $menu, "items" => $created], JSON_UNESCAPED_UNICODE));
echo "\nСОЗДАНО пунктов: ", count($created), "\n";
