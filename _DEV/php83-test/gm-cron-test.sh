#!/bin/bash
# Прогон заданий cron боевых на тестовой копии под PHP 8.3 (2026-10-18). Каждое – один раз, от www-root (наружу не ходит –
# GM_DEV_OUT), в папке копии и с базой mm_dev, nice 15, предел 15 мин. Ключи доступа берутся из crontab maxmobiles и не
# печатаются. Итог: /root/gm-bench/cron/<n>.log и сводка в /root/gm-bench/cron/SUMMARY.txt
set -u
DEV=/var/www/www-root/data/www/dev.maxmobiles.ru
O=/root/gm-bench/cron; mkdir -p $O; : > $O/SUMMARY.txt
# Уникальные команды из crontab (без расписания), пути – на копию, PHP – 8.3
crontab -l -u maxmobiles | grep -v '^\s*#' | grep -o '/opt/php74/bin/php.*' | sed -E 's#>/dev/null 2>&1##; s/[[:space:]]+$//; s#/opt/php74/bin/php#/opt/php83/bin/php#; s#/var/www/maxmobiles/data/www/maxmobiles.ru/#'"$DEV"'/#g' \
  | awk '!seen[$0]++' > $O/commands.txt
n=0
while read -r cmd; do
  n=$((n+1))
  shown=$(echo "$cmd" | sed -E 's/(key|token|pass|password|secret)=[^ ]+/\1=***/Ig; s#'"$DEV"'/##g; s#/opt/php83/bin/php #php83 #')
  t0=$(date +%s)
  timeout 900 nice -n 15 su -s /bin/bash www-root -c "cd $DEV && $cmd" > $O/$n.log 2>&1
  rc=$?; dt=$(( $(date +%s) - t0 ))
  fat=$(grep -c -i 'fatal error\|Uncaught\|Parse error' $O/$n.log)
  warn=$(grep -c -i 'Warning:' $O/$n.log)
  dep=$(grep -c -i 'Deprecated:' $O/$n.log)
  printf '%2d  код %-3s %4s с  фатальных %s, предупреждений %s, устаревшее %s  | %s\n' $n $rc $dt $fat $warn $dep "$shown" >> $O/SUMMARY.txt
  [ "$fat" -gt 0 ] && grep -i -m2 'fatal error\|Uncaught\|Parse error' $O/$n.log | sed "s#$DEV/##g" | cut -c1-220 | sed 's/^/        /' >> $O/SUMMARY.txt
done < $O/commands.txt
cat $O/SUMMARY.txt
