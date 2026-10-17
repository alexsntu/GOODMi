#!/bin/sh
# 2026-10-17: проверка защиты поиска без влияния на сайт. Настоящий Manticore, индексы и замок /run/lock/search-index.lock не трогаются.
# Запуск: scp _DEV/search-tests/test-safe.sh goodmi:/root/ && ssh goodmi sh /root/test-safe.sh
T=/root/gm-backups/search-tests
mkdir -p $T; cd $T; rm -f t.lock t.log line-*.sh

# Настоящая строка расписания → тестовая: свой замок и журнал, вместо сборки – sleep, \% → % (так делает cron)
mkline() { # $1 – файл cron.d, $2 – начало строки, $3 – чем заменить запуск php, $4 – имя
    grep "^$2" /etc/cron.d/$1 | head -1 | sed -e 's/^[^r]*root //' -e 's#/run/lock/search-index.lock#'$T'/t.lock#' \
        -e 's#>> /root/[a-z]*-search/cron.log#>> '$T'/t.log#' -e 's#/opt/php74/bin/php /root/[a-z]*-search/[a-z]*-search-index.php#'"$3"'#' \
        -e 's/\\%/%/g' > line-$4.sh
}

echo "== A. Сборка зависла: предел 600 с заменён на 3 с, «сборка» – sleep 30"
mkline gm-search '15 ' 'sleep 30' a
sed -i 's/timeout 600/timeout 3/' line-a.sh
t0=$(date +%s); sh line-a.sh; echo "   заняло $(( $(date +%s) - t0 )) с (ждём ~3)"; tail -n 2 t.log

echo "== B. Замок занят другой сборкой: ожидание 600 с заменено на 3 с"
mkline mm-search '15 ' 'echo сборка прошла' b
sed -i 's/flock -w 600/flock -w 3/' line-b.sh
flock t.lock sleep 20 & HOLD=$!; sleep 1
t0=$(date +%s); sh line-b.sh; echo "   заняло $(( $(date +%s) - t0 )) с (ждём ~3)"; tail -n 1 t.log
kill $HOLD 2>/dev/null; wait $HOLD 2>/dev/null

echo "== C. Обычный запуск после сбоя проходит"
mkline mm-search '15 ' 'echo Готово: сборка прошла' c
sh line-c.sh; tail -n 2 t.log

echo "== D. Сборщик и «чёрная дыра» (порт принимает соединение и молчит, как Manticore 16.10): предел ответа 120 с заменён на 5 с"
python3 -c "
import socket,time
s=socket.socket(); s.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1); s.bind(('127.0.0.1',9399)); s.listen(50)
c=[]
while True:
    x,_=s.accept(); c.append(x)
" & HOLE=$!; sleep 1
sed -e "s/'', 9306)/'', 9399)/" -e 's/MYSQLI_OPT_READ_TIMEOUT, 120/MYSQLI_OPT_READ_TIMEOUT, 5/' /root/gm-search/gm-search-index.php > idx-test.php
grep -c "9399" idx-test.php | sed 's/^/   подмена порта: /'
t0=$(date +%s); timeout 60 /opt/php74/bin/php idx-test.php 2>&1 | grep -v 'PHP Notice' | head -3 | cut -c1-200; echo "   заняло $(( $(date +%s) - t0 )) с (ждём ~5–10, не 60)"
kill $HOLE
echo "== Настоящие индексы не тронуты:"
mysql -h127.0.0.1 -P9306 -N -e "SELECT COUNT(*) FROM gm_products; SELECT COUNT(*) FROM mm_products" | tr '\n' ' '; echo
