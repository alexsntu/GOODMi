#!/bin/bash
# Перевод таблиц MyISAM → InnoDB (2026-10-19). Сначала – только ТЕСТОВАЯ база mm_dev (проверка блокировок и скорости).
# По одной таблице, от маленьких к большим; ошибка таблицы – в журнал, идём дальше; перед каждой таблицей – замер боевого
# goodmi.ru (дольше 1,5 с – пауза 30 с). Журнал: /root/gm-bench/innodb-<база>.log
# Запуск: gm-myisam-to-innodb.sh <база>   (для боевой mm_work – только по отдельному слову пользователя)
set -u
DB=${1:?база}
LOG=/root/gm-bench/innodb-$DB.log
echo "$(date '+%F %T') старт $DB: таблиц MyISAM $(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB' AND engine='MyISAM'")" >> $LOG
mysql -N -e "SELECT table_name FROM information_schema.tables WHERE table_schema='$DB' AND engine='MyISAM' ORDER BY data_length + index_length" | while read -r t; do
  while :; do
    s=$(curl -sk -m 10 --resolve goodmi.ru:443:82.202.160.72 -o /dev/null -w '%{time_total}' https://goodmi.ru/)
    awk -v s="$s" 'BEGIN{exit !(s>1.5)}' || break
    echo "$(date '+%T') пауза: боевой отвечает $s с" >> $LOG; sleep 30
  done
  t0=$(date +%s.%N)
  if err=$(mysql "$DB" -e "ALTER TABLE \`$t\` ENGINE=InnoDB" 2>&1); then
    printf '%s %-50s %6.1f с\n' "$(date '+%T')" "$t" "$(awk -v a="$t0" -v b="$(date +%s.%N)" 'BEGIN{print b-a}')" >> $LOG
  else
    echo "$(date '+%T') ОШИБКА $t: $err" >> $LOG
  fi
done
echo "$(date '+%F %T') готово: осталось MyISAM $(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB' AND engine='MyISAM'")" >> $LOG
