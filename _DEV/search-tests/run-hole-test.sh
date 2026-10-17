#!/bin/sh
# 2026-10-17: проверка, что обработчики PHP сайта GOODMi не зависают, если Manticore молчит. Запускать с Mac (нужен `ssh goodmi`).
# На минуту кладёт на сайт тестовую страницу gm-hole-test-<случайно>.php (копия подключения gm-search.php, порт 9399) и запускает
# на сервере посредника hole-proxy.py: proxy – пропускает к Manticore, но «глотает» запрос со словом hangtest; silent – молчит совсем.
# По 3 запроса одновременно (у пула goodmi.ru всего 5 обработчиков – 2 остаются покупателям). Ждём: каждый ответ ~3 с, errno 2006,
# главная открывается как обычно. В конце страница и посредник удаляются всегда (trap). pkill – только с шаблоном [h]ole-proxy,
# иначе он убивает собственную ssh-команду (так и было в первом заходе).
D=$(cd "$(dirname "$0")" && pwd)
R=/var/www/maxmobiles/data/www/maxmobiles.ru
T=/root/gm-backups/search-tests
F=gm-hole-test-$(openssl rand -hex 6).php
U="https://goodmi.ru/$F"
cleanup() { ssh goodmi "rm -f $R/$F; pkill -f '[h]ole-proxy'; sleep 0.3; echo \"уборка: файлов \$(ls $R/gm-hole-test-* 2>/dev/null | wc -l), посредников \$(pgrep -fc '[h]ole-proxy')\""; }
trap cleanup EXIT
proxy() { ssh goodmi "pkill -f '[h]ole-proxy'; sleep 0.3; setsid timeout 90 python3 $T/hole-proxy.py $1 </dev/null >/dev/null 2>&1 & sleep 1; echo \"посредник $1 слушает: \$(ss -ltn | grep -c ':9399 ')\""; }
req() { curl -s -m 30 -A Mozilla/5.0 -w "  [http %{http_code}, всего %{time_total}s]\n" "$U?q=$1&r=$RANDOM$2"; }

ssh goodmi "mkdir -p $T" && scp -q "$D/hole-proxy.py" goodmi:$T/hole-proxy.py && scp -q "$D/hole-test.php" goodmi:$R/$F \
    && ssh goodmi "chown maxmobiles:maxmobiles $R/$F; chmod 644 $R/$F" || exit 1

proxy proxy
echo "== 1. Обычный запрос через посредника (быстро, товары найдены)"; req xiaomi
echo "== 2. Запрос завис (как Manticore 16.10), 3 одновременно + главная"
for i in 1 2 3; do req hangtest "&i=$i" & done
sleep 0.5; curl -s -m 30 -o /dev/null -A Mozilla/5.0 -w "  главная: http %{http_code}, %{time_total}s\n" "https://goodmi.ru/?r=$RANDOM" &
wait
proxy silent
echo "== 3. Manticore молчит даже на подключение, 3 одновременно"
for i in 1 2 3; do req xiaomi "&i=$i" & done; wait
