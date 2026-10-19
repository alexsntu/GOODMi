#!/bin/bash
# Очистка пустых сохранённых сеансов cscart_stored_sessions (2026-10-19, решение пользователя «почистим старые записи сеансов»).
# 5,56 млн «живых» (CS-Cart хранит 14 дней) записей по 233 Б – почти все от ботов и проверок здоровья NGENIX (каждая создавала
# сеанс; с 08:24 их отвечает nginx). Удаляем только сеансы, где есть лишь служебные ключи cart_languageC / secondary_currencyC /
# company_id (+ «i:0;b:0;»); сеансы с любыми другими данными (cu_id, сеансы администратора …) остаются. Порциями по 5 тыс.,
# каждые 20 порций – замер боевого goodmi.ru (дольше 1,5 с – пауза). Журнал: /root/gm-bench/sessions-cleanup.log
set -u
LOG=/root/gm-bench/sessions-cleanup.log
R=$(cat /root/gm-bench/sessions-empty-regexp.txt)
BATCH=5000
echo "$(date '+%F %T') старт, записей $(mysql -N mm_work -e "SELECT COUNT(*) FROM cscart_stored_sessions")" >> $LOG
n=0; total=0
while :; do
  d=$(mysql -N mm_work -e "DELETE FROM cscart_stored_sessions WHERE data REGEXP '$R' LIMIT $BATCH; SELECT ROW_COUNT();")
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
  sleep 0.2
done
echo "$(date '+%F %T') ГОТОВО: удалено $total, осталось $(mysql -N mm_work -e "SELECT COUNT(*) FROM cscart_stored_sessions")" >> $LOG
