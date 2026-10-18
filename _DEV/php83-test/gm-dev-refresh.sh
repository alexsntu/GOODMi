#!/bin/bash
# Новая тестовая копия боевых витрин для теста PHP 8.3 (2026-10-18): старая копия удаляется, код – с боевой папки
# (без images, кэша и данных покупателей в bonusplus/logsklad/mc_log/sber), картинки – боевые только для чтения (bind ro),
# база – из резервной копии 17:37 в mm_dev. Боевое только читается. Журнал: /root/gm-dev-refresh.log
set -u
export HOME=/root  # под systemd-run иначе mysql не видит /root/.my.cnf
LIVE=/var/www/maxmobiles/data/www/maxmobiles.ru
DEV=/var/www/www-root/data/www/dev.maxmobiles.ru
B=/root/backup-2026-10-18-pre-php83
NI="nice -n 19 ionice -c3"
log() { echo "$(date '+%F %T') $*"; }
die() { log "ОСТАНОВ: $*"; exit 1; }
[ "$DEV" != "$LIVE" ] && [ -d "$DEV" ] && [ -f "$B/mm_work.sql.zst" ] || die "пути"

if [ "${1:-}" != db ]; then
mountpoint -q "$DEV/images" && die "images уже смонтирован"
if [ "${1:-}" != resume ]; then
  log "1/6 удаление старой копии"
  $NI find "$DEV" -mindepth 1 -maxdepth 1 -exec rm -rf {} + || die "удаление"
  log "   свободно: $(df -h / | awk 'NR==2{print $4}')"
fi

log "2/6 копирование кода"
$NI rsync -a --bwlimit=80000 --chown=www-root:www-root \
  --exclude=/images/ --exclude=/var/cache/ \
  --exclude='/bonusplus/*' --exclude='/logsklad/*' --exclude='/mc_log/*' --exclude='/sber/*' \
  "$LIVE/" "$DEV/"; rc=$?
[ $rc = 0 ] || [ $rc = 24 ] || die "rsync $rc"  # 24 – файл исчез во время копирования (временные выгрузки) – не ошибка
mkdir -p "$DEV/images" "$DEV/var/cache"; chown www-root:www-root "$DEV/images" "$DEV/var/cache"
log "   готово: $(du -sh "$DEV" | cut -f1)"

log "3/6 пути и настройки копии"
grep -rlI --include='*.php' --exclude-dir=upgrades --exclude-dir=vendor --exclude-dir=images "$LIVE" "$DEV" 2>/dev/null \
  | while read -r f; do sed -i "s#$LIVE#$DEV#g" "$f"; echo "   путь заменён: ${f#$DEV/}"; done
python3 - "$DEV/config.local.php" "$B/dev-config.local.php.old" <<'EOF' || die "config.local.php"
import re, sys
cfg, old = sys.argv[1], sys.argv[2]
o = open(old, encoding='utf-8', errors='replace').read()
s = open(cfg, encoding='utf-8', errors='replace').read()
for k in ('db_name', 'db_user', 'db_password'):
    m = re.search(r"^\s*\$config\['%s'\]\s*=.*$" % k, o, re.M)
    s, n = re.subn(r"^\s*\$config\['%s'\]\s*=.*$" % k, lambda _: m.group(0), s, count=1, flags=re.M)
    assert m and n == 1, k
for k in ('http_host', 'https_host'):
    s, n = re.subn(r"^(\s*\$config\['%s'\]\s*=\s*)'maxmobiles\.ru';" % k, r"\1'dev.maxmobiles.ru';", s, flags=re.M)
    assert n == 1, k
assert "'mm_work'" not in s
open(cfg, 'w', encoding='utf-8').write(s)
print('   config.local.php: база mm_dev, адрес dev.maxmobiles.ru')
EOF
grep -q "'mm_dev'" "$DEV/config.local.php" || die "в копии не mm_dev"

log "4/6 картинки – боевые только для чтения"
mount --bind "$LIVE/images" "$DEV/images" && mount -o remount,bind,ro "$DEV/images" || die "mount"
su -s /bin/sh www-root -c "touch '$DEV/images/zz_ro_test'" 2>/dev/null && die "images доступен на запись!"
log "   смонтировано ro: $(findmnt -no OPTIONS "$DEV/images" | cut -d, -f1)"

fi  # ${1} = db – только база и адреса (шаги 1–4 уже сделаны)

log "5/6 база mm_dev из копии 17:37 (ограничение скорости)"
CS=$(mysql -N -e "SELECT CONCAT(default_character_set_name,' COLLATE ',default_collation_name) FROM information_schema.schemata WHERE schema_name='mm_work'")
mysql -e "DROP DATABASE IF EXISTS mm_dev; CREATE DATABASE mm_dev CHARACTER SET $CS" || die "create db"
zstd -dc "$B/mm_work.sql.zst" | python3 -c '
import sys, time
rate = 20 * 1024 * 1024; t0 = time.time(); n = 0
while True:
    b = sys.stdin.buffer.read(1 << 20)
    if not b: break
    sys.stdout.buffer.write(b); n += len(b)
    d = n / rate - (time.time() - t0)
    if d > 0: time.sleep(d)
' | mysql --init-command="SET SESSION net_read_timeout=7200, net_write_timeout=7200, foreign_key_checks=0, unique_checks=0" mm_dev
codes="${PIPESTATUS[*]}"; [ "$codes" = "0 0 0" ] || die "импорт: $codes"
log "   таблиц: $(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='mm_dev'")"

log "6/6 адреса витрин и сеансы"
mysql mm_dev -e "UPDATE cscart_companies SET storefront='dev.maxmobiles.ru', secure_storefront='dev.maxmobiles.ru' WHERE company_id=1;
UPDATE cscart_companies SET storefront='dev.goodmi.ru', secure_storefront='dev.goodmi.ru' WHERE company_id=2;
UPDATE cscart_companies SET storefront='dev.mi92.ru', secure_storefront='dev.mi92.ru' WHERE company_id=3;
TRUNCATE cscart_sessions;
SELECT company_id, storefront FROM cscart_companies;" || die "адреса"
log "ГОТОВО"
