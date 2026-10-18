#!/bin/bash
# Установка запасных оповещений GOODMi как системной службы Mac (работает при любой учётке). Запуск от администратора:
#   su a0000 -c "sudo bash /Users/Shared/goodmi-alerts-install/install.sh"
# Перед этим скрипт, plist, install.sh и config.json с ключами копируются в /Users/Shared/goodmi-alerts-install/ (не из
# «Документов» – туда macOS не пускает sudo без разрешения); после установки папка удаляется.
# Удаление: sudo launchctl bootout system/ru.goodmi.alerts; sudo rm -rf "/Library/Application Support/goodmi-alerts"
#   /Library/LaunchDaemons/ru.goodmi.alerts.plist
set -e
SRC="$(cd "$(dirname "$0")" && pwd)"
DST="/Library/Application Support/goodmi-alerts"
STAGE=/Users/Shared/goodmi-alerts-install
[ "$(id -u)" = 0 ] || { echo "нужен sudo"; exit 1; }
mkdir -p "$DST"
install -m 755 "$SRC/goodmi-alerts.py" "$DST/goodmi-alerts.py"
[ -f "$STAGE/config.json" ] && install -m 600 "$STAGE/config.json" "$DST/config.json"
[ -f "$DST/config.json" ] || { echo "нет config.json"; exit 1; }
chown root:wheel "$DST/goodmi-alerts.py"
chown alex-max "$DST" "$DST/config.json"
chmod 700 "$DST"
install -m 644 -o root -g wheel "$SRC/ru.goodmi.alerts.plist" /Library/LaunchDaemons/ru.goodmi.alerts.plist
launchctl bootout system/ru.goodmi.alerts 2>/dev/null || true
launchctl bootstrap system /Library/LaunchDaemons/ru.goodmi.alerts.plist
rm -rf "$STAGE"
echo "готово: служба ru.goodmi.alerts установлена"
launchctl print system/ru.goodmi.alerts | grep -E "state|runs" | head -3
