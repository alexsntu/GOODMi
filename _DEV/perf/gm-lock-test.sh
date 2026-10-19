#!/bin/bash
# Тест блокировок на ТЕСТОВОЙ копии (2026-10-19): скорость страниц dev.goodmi.ru (10 одновременных) отдельно и во время полного
# обмена МойСклад (проигрывание import.xml + offers.xml; отпечатки обработчика пропуска стираются – импорт полный). Для сравнения
# MyISAM и InnoDB. Сторож: если боевой goodmi.ru дважды подряд отвечает дольше 1 с – тест прерывается.
# Запуск: gm-lock-test.sh <метка>. Итог – /root/gm-bench/lock-<метка>.txt
set -u
L=$1; O=/root/gm-bench/lock-$L.txt; : > $O
DEV=/var/www/www-root/data/www/dev.maxmobiles.ru
AUTH=$(cat /root/.gm-dev-auth)
U=https://dev.goodmi.ru/redmi-note-17-pro-max-8-512gb-white/
ab1() { ab -q -n 100 -c 10 -A "$AUTH" "$U" 2>&1 | awk '/Time per request/ && !t {t=$4} / 50%/ {m=$2} / 95%/ {p=$2} /100%/ {x=$2} /Failed requests/ {f=$3} END {printf "среднее %s мс, медиана %s, 95%% %s, макс %s, ошибок %s", t, m, p, x, f}'; }
live() { curl -sk -m 5 --resolve goodmi.ru:443:82.202.160.72 -o /dev/null -w '%{time_total}' https://goodmi.ru/; }
for u in / $U; do curl -sk -u "$AUTH" -o /dev/null "https://dev.goodmi.ru$u"; done
echo "без обмена: $(ab1)" >> $O
echo "без обмена: $(ab1)" >> $O
mysql mm_dev -e "TRUNCATE cscart_gm_cml_hash"
systemd-run -q --unit=gm-lock-import --collect -p User=www-root -p WorkingDirectory=$DEV -p Nice=10 \
  -p StandardOutput=file:/root/gm-bench/lock-$L-import.txt /opt/php83/bin/php -d memory_limit=2048M var/gm-cml-replay.php 2 var/gm-cml-test/import.xml var/gm-cml-test/offers.xml
sleep 5; bad=0; i=0
while systemctl is-active -q gm-lock-import; do
  i=$((i+1)); r=$(ab1); t=$(live)
  echo "во время обмена $i: $r | боевой $t с" >> $O
  if awk -v t="$t" 'BEGIN{exit !(t>1)}'; then bad=$((bad+1)); else bad=0; fi
  [ $bad -ge 2 ] && { systemctl stop gm-lock-import; echo "ПРЕРВАНО: боевой медленный" >> $O; break; }
  sleep 3
done
cat /root/gm-bench/lock-$L-import.txt >> $O
cat $O
