#!/bin/bash
# Сводка после переключения сайта на PHP 8.3: $1 – сайт, $2 – время переключения ЧЧ:ММ:СС (сегодня)
S=$1; T=$2; D=$(date +%Y-%m-%d)
echo "--- PHP-сообщения после $T:"
awk -v t="$T" '$2 >= t' /var/www/httpd-logs/$S.error.log | grep "PHP message" | sed -E 's/.*PHP message: //; s/, client:.*//; s/[0-9]{3,}/N/g' | cut -c1-200 | sort | uniq -c | sort -rn | head -15
echo "--- FPM 8.3 (кроме NOTICE):"
journalctl -t php-fpm83 --since "$D $T" --no-pager | grep -v NOTICE | grep -v "^-- " | tail -5
echo "--- запросы к PHP после $T:"
awk -v t="${D}T$T" '$1 >= t' /var/log/nginx/gm-timing-$S.log | python3 -c '
import sys,re
st={}; rts=[]
for l in sys.stdin:
    m=re.search(r"\" (\d{3}) \d+ rt=([\d.]+)",l)
    if m: st[m.group(1)]=st.get(m.group(1),0)+1; rts.append(float(m.group(2)))
rts.sort(); n=len(rts)
print("запросов",n,"коды",dict(sorted(st.items())),"медиана",rts[n//2] if n else 0,"95%",rts[int(n*.95)] if n else 0,"макс",rts[-1] if n else 0)'
echo "--- медленные (>3 с) после $T: $(awk -v t="[$(date +%d-%b-%Y) $T" '/^\[/{ if ($0 >= t) c++ } END {print c+0}' /var/log/php-fpm-slow/$S.log)"
