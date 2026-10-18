#!/bin/bash
# Сторож нагрузки на время подготовки тестовой копии: раз в 15 с – нагрузка и время ответа боевых витрин (мимо NGENIX).
# Плохо (ответ > 2 с два раза подряд или load1 > 20) – пауза всей группы процессов копирования, норма – продолжить.
PGID=$1
LOG=/root/gm-load-guard.log
bad=0; paused=0
while kill -0 -- -"$PGID" 2>/dev/null; do
  la=$(cut -d' ' -f1 /proc/loadavg)
  t1=$(curl -sk -m 10 --resolve goodmi.ru:443:176.123.171.223 -o /dev/null -w '%{http_code}:%{time_total}' https://goodmi.ru/)
  t2=$(curl -sk -m 10 --resolve maxmobiles.ru:443:176.123.171.223 -o /dev/null -w '%{http_code}:%{time_total}' https://maxmobiles.ru/)
  slow=$(awk -v a="${t1#*:}" -v b="${t2#*:}" -v l="$la" 'BEGIN{print (a>2||b>2||l>20)?1:0}')
  ok=$(awk -v a="${t1#*:}" -v b="${t2#*:}" -v l="$la" 'BEGIN{print (a<1&&b<1&&l<12)?1:0}')
  if [ "$slow" = 1 ]; then bad=$((bad+1)); else bad=0; fi
  if [ $bad -ge 2 ] && [ $paused = 0 ]; then kill -STOP -- -"$PGID"; paused=1; echo "$(date '+%T') ПАУЗА load=$la goodmi=$t1 mm=$t2" >>$LOG
  elif [ $paused = 1 ] && [ "$ok" = 1 ]; then kill -CONT -- -"$PGID"; paused=0; echo "$(date '+%T') продолжаем load=$la goodmi=$t1 mm=$t2" >>$LOG
  else echo "$(date '+%T') load=$la goodmi=$t1 mm=$t2${paused:+ }$([ $paused = 1 ] && echo '(на паузе)')" >>$LOG; fi
  sleep 15
done
[ $paused = 1 ] && kill -CONT -- -"$PGID" 2>/dev/null
echo "$(date '+%T') копирование завершено, сторож выключен" >>$LOG
