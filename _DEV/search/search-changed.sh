#!/bin/sh
# Свой поиск GOODMi и Maxmobiles (2026-10-23): пересборка индекса, если с прошлой проверки товары витрины менялись
# (новый товар, правка названия, кода, характеристик – CS-Cart ставит updated_timestamp). Полная сборка по расписанию – раз в
# 2 часа; эта проверка из cron каждые 15 минут, чтобы новый товар находился сразу, а не через 2 часа. Сборка – ~2 с.
# Запуск: search-changed.sh <company_id> <скрипт индекса> <файл отметки>; замок и предел времени – в строке cron.
# Копии: /root/gm-search/search-changed.sh на сервере, _DEV/search-gm/ и _DEV/search/ в репозиториях.
c=$1; idx=$2; st=$3
m=$(mysql mm_work -N -e "SELECT MAX(updated_timestamp) FROM cscart_products WHERE company_id = $c")
[ -n "$m" ] && [ "$m" != "NULL" ] || exit 0
[ -f "$st" ] && [ "$(cat "$st")" -ge "$m" ] 2>/dev/null && exit 0
# Отметка – до сборки: правка во время сборки попадёт в следующую проверку
echo "$m" > "$st"
echo "== $(date "+%F %T") индекс после правки товаров"
timeout 600 /opt/php83/bin/php "$idx"
