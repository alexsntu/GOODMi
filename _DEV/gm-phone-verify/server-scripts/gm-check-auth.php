<?php
// GOODMi: сводка для проверки регистрации/входа по номеру (только чтение). Запуск: php /root/gm-check-auth.php
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru");
define("BOOTSTRAP",1); define("DIR_ROOT",getcwd()); define("PRODUCT_STATUS","");
$config=[]; @include "config.local.php"; $m=new mysqli($config["db_host"],$config["db_user"],$config["db_password"],$config["db_name"]); $m->set_charset("utf8"); $p=$config["table_prefix"];
function q($m,$t,$s){ echo "-- $t\n"; $r=$m->query($s); if(!$r){echo "ERR ",$m->error,"\n";return;} while($x=$r->fetch_row()) echo "   ",implode(" | ",$x),"\n"; if(!$r->num_rows) echo "   (нет)\n"; }
$since="UNIX_TIMESTAMP(CURDATE())";
q($m,"записи за сегодня: id | почта | телефон | подтверждён | обязан подтвердить | код ждёт ввода | создана | последний вход",
 "SELECT u.user_id, IF(u.email REGEXP \"^7[0-9]{10}(-[0-9]+)?@goodmi.ru$\",\"заглушка\",CONCAT(LEFT(u.email,2),\"***\")), CONCAT(\"…\",RIGHT(u.phone,5)), IF(v.verified_at>0,\"да\",\"нет\"), IFNULL(v.must_verify,0), IF(v.code_hash<>\"\" AND v.code_expires>UNIX_TIMESTAMP(),\"да\",\"нет\"), FROM_UNIXTIME(u.timestamp,\"%H:%i:%s\"), IF(u.last_login>0,FROM_UNIXTIME(u.last_login,\"%H:%i:%s\"),\"-\") FROM {$p}users u LEFT JOIN {$p}gm_phone_verify v ON v.user_id=u.user_id WHERE u.company_id=2 AND u.user_type=\"C\" AND (u.timestamp > $since OR v.last_sent > $since OR v.verified_at > $since) ORDER BY u.user_id");
q($m,"заказы за сегодня: № | покупатель | статус | сумма | почта | время",
 "SELECT order_id,user_id,status,total,IF(email REGEXP \"^7[0-9]{10}(-[0-9]+)?@goodmi.ru$\",\"заглушка\",CONCAT(LEFT(email,2),\"***\")),FROM_UNIXTIME(timestamp,\"%H:%i:%s\") FROM {$p}orders WHERE company_id=2 AND timestamp > $since ORDER BY order_id");
q($m,"вход по номеру и восстановление: номер | неверных паролей | код восстановления ждёт | отправлено за час | последняя отправка",
 "SELECT CONCAT(\"…\",RIGHT(phone,5)),login_fails,IF(code_hash<>\"\" AND code_expires>UNIX_TIMESTAMP(),\"да\",\"нет\"),sent_count,IF(last_sent>0,FROM_UNIXTIME(last_sent,\"%H:%i:%s\"),\"-\") FROM {$p}gm_phone_auth");
q($m,"СМС за сегодня (счётчик сайта)","SELECT data FROM {$p}storage_data WHERE data_key=CONCAT(\"gm_sms_day_\",DATE_FORMAT(NOW(),\"%Y%m%d\"))");
echo "время сервера: ",date("H:i:s"),"\n";
