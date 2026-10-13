<?php
// GOODMi: всё по номеру телефона (только чтение). Запуск: php /root/gm-check-phone.php 79780000000
chdir("/var/www/maxmobiles/data/www/maxmobiles.ru");
define("BOOTSTRAP",1); define("DIR_ROOT",getcwd()); define("PRODUCT_STATUS","");
$config=[]; @include "config.local.php"; $m=new mysqli($config["db_host"],$config["db_user"],$config["db_password"],$config["db_name"]); $m->set_charset("utf8"); $p=$config["table_prefix"];
$t=substr(preg_replace("/\D+/","",isset($argv[1])?$argv[1]:""),-10); if(strlen($t)!==10){ echo "нужен номер\n"; exit(1);} 
function q($m,$t,$s){ echo "-- $t\n"; $r=$m->query($s); if(!$r){echo "ERR ",$m->error,"\n";return;} while($x=$r->fetch_assoc()){ $o=[]; foreach($x as $k=>$v) $o[]="$k: $v"; echo "   ",implode(" | ",$o),"\n"; } if(!$r->num_rows) echo "   (нет)\n"; }
$n="REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s,\"+\",\"\"),\"(\",\"\"),\")\",\"\"),\"-\",\"\"),\" \",\"\")";
q($m,"покупатели сайта с этим номером","SELECT u.user_id id,u.company_id витрина,u.status,TRIM(CONCAT(u.firstname,\" \",u.lastname)) имя,CONCAT(LEFT(u.email,3),\"***\",SUBSTRING(u.email,LOCATE(\"@\",u.email))) почта,u.phone,u.clear_phone,FROM_UNIXTIME(u.timestamp,\"%d.%m.%Y\") создана,IF(u.last_login>0,FROM_UNIXTIME(u.last_login,\"%d.%m.%Y %H:%i\"),\"-\") вход,IFNULL((SELECT FROM_UNIXTIME(MAX(profile_update_timestamp),\"%d.%m.%Y %H:%i\") FROM {$p}user_profiles pr WHERE pr.user_id=u.user_id),\"-\") профиль_изменён FROM {$p}users u WHERE ".sprintf($n,"u.phone")." LIKE \"%$t\" OR u.clear_phone LIKE \"%$t\"");
q($m,"адресные телефоны в профилях","SELECT user_id,b_phone,s_phone FROM {$p}user_profiles WHERE ".sprintf($n,"b_phone")." LIKE \"%$t\" OR ".sprintf($n,"s_phone")." LIKE \"%$t\"");
q($m,"подтверждение","SELECT v.user_id,v.phone,IF(v.verified_at>0,FROM_UNIXTIME(v.verified_at,\"%d.%m.%Y %H:%i\"),\"нет\") подтверждён,v.verified_by,v.note,v.must_verify,v.code_phone FROM {$p}gm_phone_verify v WHERE v.phone LIKE \"%$t\" OR v.code_phone LIKE \"%$t\" OR v.user_id IN (SELECT user_id FROM {$p}users WHERE ".sprintf($n,"phone")." LIKE \"%$t\" OR clear_phone LIKE \"%$t\")");
q($m,"покупки в магазинах (МойСклад)","SELECT COUNT(*) всего, IFNULL(MIN(phone),\"-\") номер_в_таблице FROM {$p}gm_ms_orders WHERE phone LIKE \"%$t\"");
q($m,"заказы сайта на этот номер","SELECT COUNT(*) всего, IFNULL(GROUP_CONCAT(DISTINCT user_id),\"-\") записи, IFNULL(GROUP_CONCAT(DISTINCT company_id),\"-\") витрины, IFNULL(FROM_UNIXTIME(MAX(timestamp),\"%d.%m.%Y\"),\"-\") последний FROM {$p}orders WHERE ".sprintf($n,"phone")." LIKE \"%$t\"");
q($m,"вход/блокировка","SELECT phone,login_fails,strikes,blocked_at FROM {$p}gm_phone_auth WHERE phone LIKE \"%$t\"");
