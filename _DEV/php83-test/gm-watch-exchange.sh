#!/bin/bash
# Наблюдение за обменом с МойСкладом (commerceml) на сайте: $1 – сайт, $2 – минут. Печатает каждый запрос обмена,
# PHP-сообщения и медленные запросы по ходу; в конце – сводка. (2026-10-21, переход на PHP 8.3)
S=${1:-goodmi.ru}; MIN=${2:-25}; T0=$(date +%H:%M:%S); D=$(date +%Y-%m-%d)
echo "наблюдение $S с $T0 на $MIN мин"
timeout $((MIN*60)) tail -n0 -F /var/log/nginx/gm-timing-$S.log /var/www/httpd-logs/$S.error.log /var/log/php-fpm-slow/$S.log 2>/dev/null | \
  grep --line-buffered -E 'commerceml|PHP message|^\[2|executing too slow' | \
  sed -u -E 's/(key|code|requestId|filename=[^&]*)=[^& "]*/\1=…/g' | cut -c1-200
echo "=== сводка обмена $T0 – $(date +%H:%M:%S):"
awk -v t="${D}T$T0" '$1 >= t' /var/log/nginx/gm-timing-$S.log | grep commerceml | python3 -c '
import sys,re,collections
n=collections.Counter(); mx=collections.defaultdict(float)
for l in sys.stdin:
    m=re.search(r"type=([a-z]+)&mode=([a-z]+)[^\"]*\" (\d{3}) \d+ rt=([\d.]+)",l)
    if m:
        k="%s/%s -> %s"%(m.group(1),m.group(2),m.group(3)); n[k]+=1; mx[k]=max(mx[k],float(m.group(4)))
for k in sorted(n): print("  %4d x %s, макс %.2f с"%(n[k],k,mx[k]))'
echo "PHP-сообщения за время обмена:"; awk -v t="$T0" '$2 >= t' /var/www/httpd-logs/$S.error.log | grep "PHP message" | sed -E 's/.*PHP message: //; s/ in \/var\/www\/[^ ]*\/maxmobiles.ru\// @ /; s/" while.*//' | cut -c1-150 | sort | uniq -c | sort -rn | head
