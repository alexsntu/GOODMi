#!/bin/bash
# Запуск с Mac (нужен ssh goodmi): bash watch-fronts.sh – 15 кругов раз в минуту; копия на сервере не нужна.
# Наблюдение за витринами после правки nginx: раз в минуту коды ключевых страниц + 5xx в журналах
URLS="https://goodmi.ru/ https://goodmi.ru/power-banks/ https://goodmi.ru/vneshniy-akkumulyator-power-bank-xiaomi-3-20000-mah-white-plm18zm/ https://goodmi.ru/cart/ https://goodmi.ru/robots.txt https://goodmi.ru/index.php?dispatch=products.search&q=redmi https://maxmobiles.ru/ https://maxmobiles.ru/iphone/ https://maxmobiles.ru/cart/ https://maxmobiles.ru/robots.txt https://xione.ru/ https://xione.ru/robots.txt"
for i in $(seq 1 15); do
  bad=""; slow=""
  for u in $URLS; do
    r=$(curl -s -o /dev/null -A "Mozilla/5.0 Chrome/130 gm-watch" --max-time 20 -w '%{http_code} %{time_total}' "$u")
    c=${r% *}; t=${r#* }
    [ "$c" != "200" ] && bad="$bad $c:$u"
    awk "BEGIN{exit !($t>3)}" && slow="$slow ${t}s:$u"
  done
  srv=$(ssh goodmi 'for s in goodmi.ru maxmobiles.ru xione.ru; do printf "%s 5xx=%s " $s $(awk -v t="$(date -d "-1 min" +%Y-%m-%dT%H:%M)" "\$1 >= t" /var/log/nginx/gm-timing-$s.log | grep -cE "\" 5[0-9][0-9] "); done; printf "nginx-crit/emerg=%s" $(grep -cE "\[(emerg|crit|alert)\]" /var/log/nginx/error.log)')
  echo "$(date +%H:%M) страницы: $([ -z "$bad" ] && echo "все 200" || echo "НЕ 200:$bad") ${slow:+медленно:$slow} | $srv"
  [ $i -lt 15 ] && sleep 60
done
