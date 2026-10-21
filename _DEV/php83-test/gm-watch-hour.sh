#!/bin/bash
# Наблюдение за витриной после перехода на PHP 8.3 (2026-10-21): N кругов по M минут, сводка за каждый круг и общая.
# bash gm-watch-hour.sh <сайт> <company_id> <кругов> <минут>
S=${1:-goodmi.ru}; C=${2:-2}; N=${3:-6}; M=${4:-10}
START=$(date +%s); T0=$(date +%H:%M:%S)
summary() {
  local from=$1 from_ts=$2 label=$3
  local D=$(date +%Y-%m-%d)
  echo "===== $label ($from – $(date +%H:%M:%S)) ====="
  awk -v t="${D}T$from" '$1 >= t' /var/log/nginx/gm-timing-$S.log > /tmp/gmw.$$
  python3 - /tmp/gmw.$$ <<'PY'
import sys,re,collections
codes=collections.Counter(); rts=[]; ppl=0; act=collections.Counter(); err5=[]; slow=[]
pat={'корзина /cart/':r'"GET /cart/','оформление /checkout/':r'"GET /checkout/','отправка заказа (POST /checkout/)':r'"POST /checkout/',
     'избранное':r'wishlist','сравнение':r'compare|product_features','быстрый заказ (окно)':r'call_requests\.request','вход/кабинет':r'/cabinet/|gm_cabinet|/login|auth\.|/my-bonus/|/my-profile/',
     'поиск':r'products\.search|gm-search','расчёт доставки':r'gm_delivery_date','добавление/обновление корзины (POST)':r'"POST /index\.php"'}
for l in open(sys.argv[1]):
    m=re.search(r'"(\S+) (\S+)[^"]*" (\d{3}) \d+ rt=([\d.]+).*sid=(\d)',l)
    if not m: continue
    code=m.group(3); rt=float(m.group(4)); codes[code]+=1; rts.append(rt)
    if code.startswith('5'): err5.append(l[11:19]+' '+code+' '+m.group(2)[:70])
    if rt>3: slow.append(l[11:19]+' %.1fс '%rt+m.group(2)[:70])
    if m.group(5)=='1':
        ppl+=1
        for k,p in pat.items():
            if re.search(p,l): act[k]+=1
rts.sort(); n=len(rts)
print('запросов к PHP',n,'коды',dict(sorted(codes.items())),'медиана %.2f с, 95%% %.2f с, макс %.2f с'%(rts[n//2] if n else 0,rts[int(n*.95)] if n else 0,rts[-1] if n else 0))
print('с cookie (люди)',ppl,'действия:',dict(act.most_common()))
for e in err5[:10]: print('  5xx',e)
for e in slow[:10]: print('  медленно',e)
PY
  rm -f /tmp/gmw.$$
  echo "PHP-сообщения:"; awk -v t="$from" '$2 >= t' /var/www/httpd-logs/$S.error.log | grep "PHP message" | sed -E 's/.*PHP message: //; s/ in \/var\/www\/[^ ]*\/maxmobiles.ru\// @ /; s/ on line / :/; s/" while.*//; s/, client.*//' | cut -c1-160 | sort | uniq -c | sort -rn | head -8
  echo "FPM 8.3:"; journalctl -t php-fpm83 --since "$D $from" --no-pager | grep -v NOTICE | grep -v "^-- " | sed -E 's/.*\]: //' | cut -c1-160 | tail -5
  echo "заказы (company $C): $(mysql -N -e "SELECT CONCAT(COUNT(*),' шт., статусы: ',IFNULL(GROUP_CONCAT(status ORDER BY order_id),'–')) FROM mm_work.cscart_orders WHERE company_id=$C AND timestamp >= $from_ts AND is_parent_order <> 'Y'")"
  echo "заявки «быстрый заказ»/звонок: $(mysql -N -e "SELECT COUNT(*) FROM mm_work.cscart_call_requests WHERE company_id=$C AND timestamp >= $from_ts" 2>/dev/null)"
}
for i in $(seq 1 $N); do
  F=$(date +%H:%M:%S); FT=$(date +%s)
  sleep $((M*60))
  summary $F $FT "круг $i из $N"
done
summary $T0 $START "ИТОГО за всё наблюдение"
