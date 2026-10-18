#!/bin/bash
# Нагрузочный тест тестовой витрины dev.goodmi.ru (2026-10-18). Боевые не трогает; параллельно раз в 2 с меряет ответ боевого
# goodmi.ru и прерывает тест, если он дважды подряд дольше 1 с. Запуск: gm-bench.sh <метка> <режим: single|conc> [параллельность]
# Логин/пароль копии – в /root/.gm-dev-auth (одна строка login:pass). Итог – /root/gm-bench/<метка>.txt
set -u
LABEL=$1; MODE=$2; C=${3:-10}
OUT=/root/gm-bench; mkdir -p $OUT
AUTH=$(cat /root/.gm-dev-auth)
URLS="/ /smartfonyi/ /redmi-note-17-pro-max-8-512gb-white/"
guard() {
  bad=0
  while kill -0 $1 2>/dev/null; do
    t=$(curl -sk -m 5 --resolve goodmi.ru:443:82.202.160.72 -o /dev/null -w '%{time_total}' https://goodmi.ru/)
    echo "$(date +%T) боевой goodmi.ru $t" >> $OUT/$LABEL.guard
    if awk -v t="$t" 'BEGIN{exit !(t>1)}'; then bad=$((bad+1)); else bad=0; fi
    [ $bad -ge 2 ] && { pkill -f "ab .*dev.goodmi.ru" ; echo "ПРЕРВАНО: боевой отвечает медленно" >> $OUT/$LABEL.txt; }
    sleep 2
  done
}
: > $OUT/$LABEL.txt; : > $OUT/$LABEL.guard
for u in $URLS; do curl -sk -u "$AUTH" -o /dev/null https://dev.goodmi.ru$u; curl -sk -u "$AUTH" -o /dev/null https://dev.goodmi.ru$u; done  # прогрев
if [ "$MODE" = single ]; then
  for u in $URLS; do
    ab -q -n 30 -c 1 -A "$AUTH" "https://dev.goodmi.ru$u" > $OUT/ab.tmp 2>&1 &
    p=$!; guard $p & wait $p
    echo "$u  среднее $(grep 'Time per request' $OUT/ab.tmp | head -1 | awk '{print $4}') мс, 95% $(grep ' 95%' $OUT/ab.tmp | awk '{print $2}') мс, ошибок $(grep 'Failed requests' $OUT/ab.tmp | awk '{print $3}'), не-2xx $(grep 'Non-2xx' $OUT/ab.tmp | awk '{print $3}')" >> $OUT/$LABEL.txt
  done
else
  u=/redmi-note-17-pro-max-8-512gb-white/
  ab -q -n 200 -c $C -A "$AUTH" "https://dev.goodmi.ru$u" > $OUT/ab.tmp 2>&1 &
  p=$!; guard $p & wait $p
  echo "$u параллельно $C: $(grep 'Requests per second' $OUT/ab.tmp | awk '{print $4}') запр/с, среднее $(grep 'Time per request' $OUT/ab.tmp | head -1 | awk '{print $4}') мс, 50% $(grep ' 50%' $OUT/ab.tmp | awk '{print $2}'), 95% $(grep ' 95%' $OUT/ab.tmp | awk '{print $2}'), макс $(grep '100%' $OUT/ab.tmp | awk '{print $2}') мс, ошибок $(grep 'Failed requests' $OUT/ab.tmp | awk '{print $3}'), не-2xx $(grep 'Non-2xx' $OUT/ab.tmp | awk '{print $3}')" >> $OUT/$LABEL.txt
fi
sleep 3
echo "боевой goodmi.ru во время теста: макс $(awk '{print $4}' $OUT/$LABEL.guard | sort -n | tail -1) с, замеров $(wc -l < $OUT/$LABEL.guard)" >> $OUT/$LABEL.txt
cat $OUT/$LABEL.txt
