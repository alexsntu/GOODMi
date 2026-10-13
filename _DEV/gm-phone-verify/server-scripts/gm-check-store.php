<?php
// GOODMi: что кабинет покажет покупателю из МойСклад (заказы в работе и покупки). php gm-check-store.php 7978...
// Запускать от пользователя maxmobiles. Дозагружает документы этого номера в нашу таблицу, больше ничего не меняет.
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru"); define("AREA","C");
$_SERVER["HTTP_HOST"]="goodmi.ru"; $_SERVER["SERVER_NAME"]="goodmi.ru"; $_SERVER["REMOTE_ADDR"]="127.0.0.1"; $_SERVER["REQUEST_METHOD"]="GET"; $_SERVER["REQUEST_URI"]="/";
require "init.php"; require_once "app/gm_cabinet/store.php";
$ph = gm_cab_phone(isset($argv[1]) ? $argv[1] : ""); if ($ph === "") { echo "нужен номер\n"; exit(1); }
$count = function () use ($ph) { return db_get_hash_single_array("SELECT doc, COUNT(*) c FROM ?:gm_ms_orders WHERE phone = ?s GROUP BY doc", ["doc", "c"], $ph); };
$before = $count(); $n = fn_gm_cab_store_refresh_customer($ph); $after = $count();
echo "дозагрузка вернула: ", var_export($n, true), " | было: ", json_encode($before), " | стало: ", json_encode($after), "\n";
$rows = fn_gm_cab_store_get($ph); $sales = fn_gm_cab_store_sales($ph);
$kinds = []; foreach ($rows as $r) { $k = $r["doc"] . ($r["doc"] == "O" ? ":" . gm_cab_status_kind($r["state"]) : ""); $kinds[$k] = (isset($kinds[$k]) ? $kinds[$k] : 0) + 1; }
echo "покажем из магазинов: ", count($rows), " ", json_encode($kinds), ", из них покупок: ", count($sales), "\n";
$all = db_get_array("SELECT state FROM ?:gm_ms_orders WHERE phone = ?s AND doc = ?s", $ph, "O"); $rec = 0; foreach ($all as $o) if (gm_cab_status_kind($o["state"]) === "received") $rec++;
echo "заказов покупателя в таблице: ", count($all), ", из них выданных (скрыты, их место занимают продажи): $rec\n";
$all_sales = (int) db_get_field("SELECT COUNT(*) FROM ?:gm_ms_orders WHERE phone = ?s AND doc IN (?a)", $ph, ["D", "R"]);
echo "продаж в таблице: $all_sales, показано: ", count($sales), " (остальные – продажи по заказам сайта, чужие площадки или возвращённые)\n";
$disp = gm_cab_store_display($rows);
foreach (array_slice($disp, 0, 12) as $i => $d) { echo "   ", $d["date"], " | №", $d["number"], " | ", $d["sum"], " | ", $d["status"], " | ", $rows[$i]["doc"], !empty($rows[$i]["store"]) ? " (" . $rows[$i]["store"] . ")" : "", "\n"; }
$ids = []; foreach (array_slice($rows, 0, 3) as $r) $ids[$r["id"]] = (string) $r["items_at"];
foreach (fn_gm_cab_store_items($ids) as $id => $it) echo "   состав ", substr($id, 0, 8), ": ", count($it), " поз., первая: ", mb_substr($it[0]["name"], 0, 50), "\n";
