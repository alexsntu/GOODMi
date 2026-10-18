#!/bin/bash
# Резервная копия боевых витрин перед тестом PHP 8.3 (2026-10-18). Только чтение боевых данных, низкий приоритет.
# База – без блокировок (--single-transaction; 224 таблицы MyISAM блокировать нельзя – встанут покупатели).
set -u
B=/root/backup-2026-10-18-pre-php83
SITE=/var/www/maxmobiles/data/www/maxmobiles.ru
mkdir -p $B; chmod 700 $B
exec >>$B/backup.log 2>&1
NI="nice -n 19 ionice -c3"
echo "$(date '+%F %T') старт"

for db in mm_work ispmgr; do
  $NI mysqldump --single-transaction --quick --routines --triggers --events --hex-blob --max-allowed-packet=1G "$db" \
    | $NI zstd -T4 -3 -q -o $B/$db.sql.zst
  echo "$(date '+%F %T') база $db: коды ${PIPESTATUS[*]}"
done

$NI tar -C / --zstd -cf $B/code.tar.zst --exclude="${SITE#/}/images" --exclude="${SITE#/}/var/cache" "${SITE#/}"
echo "$(date '+%F %T') код: код $?"

$NI tar -C / --zstd -cf $B/configs.tar.zst etc/nginx opt/php*/etc etc/cron.d var/spool/cron/crontabs usr/local/mgr5/etc etc/mysql usr/local/sbin
echo "$(date '+%F %T') настройки: код $?"

cd $B && sha256sum *.zst > SHA256SUMS && ls -la $B
echo "$(date '+%F %T') готово"
