#!/bin/bash
# GOODMi: догрузка старой истории отгрузок, розничных продаж и возвратов МойСклад (до 2024 года) в таблицу кабинета.
# Запускается из cron пользователя maxmobiles по вечерам, пока история не загрузится целиком; после этого сам
# убирает свою строку из расписания. Журнал: /tmp/gm-sync-history.log. Обычную ночную загрузку (03:20) не заменяет.
LOG=/tmp/gm-sync-history.log
# Сначала – обычная загрузка свежего (до 60 минут), затем старая история (до 270 минут); к 03:20 всё заканчивается
/opt/php74/bin/php /var/www/maxmobiles/data/www/maxmobiles.ru/index.php --dispatch=gm_cabinet.sync_orders --minutes=60 >/dev/null 2>&1
OUT=$(/opt/php74/bin/php /var/www/maxmobiles/data/www/maxmobiles.ru/index.php --dispatch=gm_cabinet.sync_orders --history=1 --minutes=270 --docs=X,Y,D,R 2>/dev/null)
echo "=== $(date '+%Y-%m-%d %H:%M') ===" >> $LOG
echo "$OUT" | grep -v '^PHP ' >> $LOG
DONE=$(echo "$OUT" | grep 'старая история' | grep -c 'дошли до конца')
if [ "$DONE" -ge 4 ]; then
  crontab -l | grep -v 'gm-sync-history' | crontab -
  echo "история загружена целиком, строка расписания убрана" >> $LOG
fi
