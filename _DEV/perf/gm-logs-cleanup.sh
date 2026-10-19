#!/bin/bash
# Очистка журнала CS-Cart cscart_logs (2026-10-19, решение пользователя: «хранить 30 дней, на сегодня очистить»).
# 9,1 ГБ, 38 млн записей (почти все – «товар обновлён» от обмена МойСклад), ночная копия ISPmanager проверяет и выгружает его
# ~2 мин и вешает сайты. Удаляем записи старше 30 дней порциями по 10 тыс. с паузами (не одним запросом – иначе долгая
# блокировка); каждые 20 порций – замер ответа боевого goodmi.ru, дольше 1,5 с – пауза до нормы. После – log_lifetime = 30.
# Журнал: /root/gm-bench/logs-cleanup.log. Записи до чистки есть в /root/backup-2026-10-18-pre-php83/mm_work.sql.zst (18.10 17:37).
set -u
LOG=/root/gm-bench/logs-cleanup.log
C=${1:-$(date -d "-30 days" +%s)}
BATCH=10000
echo "$(date '+%F %T') старт, граница $(date -d @$C '+%F %T'), к удалению $(mysql -N mm_work -e "SELECT COUNT(*) FROM cscart_logs WHERE timestamp < $C")" >> $LOG
n=0; total=0
while :; do
  d=$(mysql -N mm_work -e "DELETE FROM cscart_logs WHERE timestamp < $C ORDER BY timestamp LIMIT $BATCH; SELECT ROW_COUNT();")
  [ -z "$d" ] && { echo "$(date '+%F %T') ОШИБКА запроса, стоп" >> $LOG; exit 1; }
  total=$((total + d)); n=$((n + 1))
  [ "$d" -lt $BATCH ] && break
  if [ $((n % 20)) = 0 ]; then
    while :; do
      t=$(curl -sk -m 10 --resolve goodmi.ru:443:82.202.160.72 -o /dev/null -w '%{time_total}' https://goodmi.ru/)
      awk -v t="$t" 'BEGIN{exit !(t>1.5)}' || break
      echo "$(date '+%F %T') пауза: goodmi.ru отвечает $t с" >> $LOG; sleep 30
    done
    echo "$(date '+%F %T') удалено $total, ответ goodmi.ru $t с" >> $LOG
  fi
  sleep 0.3
done
echo "$(date '+%F %T') ГОТОВО: удалено $total, осталось $(mysql -N mm_work -e "SELECT COUNT(*) FROM cscart_logs")" >> $LOG
