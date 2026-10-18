#!/usr/bin/python3
# Запасной путь оповещений в Telegram (2026-10-18): облачная задача goodmi-sms-watch-tick с 17.10 перестала заходить
# за сводкой, а с сервера магазинов Telegram недоступен. Этот скрипт на Mac (launchd, ~/Library/LaunchAgents/
# ru.goodmi.alerts.plist, каждый час в :10 с 10 до 21) забирает ту же сводку gm_cabinet.auth_status и сам шлёт тревоги
# через бота. Окно сводки – время с прошлой удачной проверки (Mac спал – после пробуждения захватит всё пропущенное).
# Первая проверка после 21:00 – короткий итог дня. Сводка не отвечает 2 раза подряд – тревога, ожила – сообщение.
# Служба системная (/Library/LaunchDaemons/ru.goodmi.alerts.plist, от имени alex-max) – работает при любой учётке Mac.
# Всё лежит в /Library/Application Support/goodmi-alerts/: config.json с ключами (права 600, в репозиторий не попадает),
# state.json, goodmi-alerts.log. Ручной запуск: goodmi-alerts.py [--test] (--test – прислать итог прямо сейчас).
import json, math, os, sys, time, urllib.parse, urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
CONFIG = os.path.join(HERE, 'config.json')
STATE = os.path.join(HERE, 'state.json')
LOG = os.path.join(HERE, 'goodmi-alerts.log')
FIRST_HOUR, LAST_HOUR = 10, 22  # проверки только днём; ночное копится и приходит утром
DAY_SUMMARY_HOUR = 21


def log(msg):
    with open(LOG, 'a') as f:
        f.write(time.strftime('%Y-%m-%d %H:%M:%S ') + msg + '\n')


def load(path, default):
    try:
        with open(path) as f:
            return json.load(f)
    except Exception:
        return default


def save_state(state):
    tmp = STATE + '.tmp'
    with open(tmp, 'w') as f:
        json.dump(state, f)
    os.replace(tmp, STATE)


def send(cfg, text):
    data = urllib.parse.urlencode({'chat_id': cfg['chat_id'], 'text': text[:4000], 'disable_web_page_preview': 'true'}).encode()
    url = 'https://api.telegram.org/bot' + cfg['bot_token'] + '/sendMessage'
    with urllib.request.urlopen(urllib.request.Request(url, data=data), timeout=30) as r:
        if not json.load(r).get('ok'):
            raise RuntimeError('Telegram ответил не ok')


def fetch(cfg, hours):
    url = cfg['status_url'] + '&hours=' + str(hours) + '&key=' + urllib.parse.quote(cfg['key'])
    req = urllib.request.Request(url, headers={'User-Agent': 'goodmi-mac-alerts'})
    last = None
    for attempt in range(2):
        try:
            with urllib.request.urlopen(req, timeout=40) as r:
                return json.load(r)
        except Exception as e:
            last = e
            time.sleep(20)
    raise RuntimeError(type(last).__name__ + ': ' + str(last)[:150])


def day_summary(d):
    t = d.get('today', {})
    lines = ['GOODMi – итог дня ' + time.strftime('%d.%m') + ' (через Mac)',
             'Вход по СМС: отправлено ' + str(t.get('sms_sent', 0)) + ', кодов введено ' + str(t.get('codes_ok', 0)) +
             ', ошибок СМС ' + str(t.get('sms_errors', 0)) + ', неверных паролей ' + str(t.get('login_bad', 0)) +
             ', блокировок ' + str(t.get('blocks', 0))]
    sms = d.get('sms') or {}
    for c in sms.get('checks', []):
        lines.append('СМС: ' + c)
    cw = d.get('code_watch') or {}
    if cw:
        lines.append('Сторож кода: проверено ' + str(cw.get('checked', '?')) + ', файлов ' + str(cw.get('files', '?')) +
                     ', закладок ' + str(cw.get('hits', '?')))
    mw = d.get('malware') or {}
    if mw:
        lines.append('Антивирус: ' + str(mw.get('last_scan', '?')) + ', находок ' + str(mw.get('hits', '?')))
    return '\n'.join(lines)


def main():
    test = '--test' in sys.argv
    cfg = load(CONFIG, None)
    if not cfg:
        log('нет config.json')
        return
    state = load(STATE, {})
    now = time.time()
    hour = time.localtime(now).tm_hour
    if not test and not (FIRST_HOUR <= hour <= LAST_HOUR):
        log('ночь – пропуск')
        return
    last_ok = state.get('last_ok', 0)
    hours = 1 if not last_ok else max(1, min(24, math.ceil((now - last_ok - 300) / 3600)))
    try:
        d = fetch(cfg, hours)
    except Exception as e:
        state['fails'] = state.get('fails', 0) + 1
        log('сводка не получена (' + str(state['fails']) + ' подряд): ' + str(e))
        if state['fails'] >= 2 and not state.get('fail_sent'):
            try:
                send(cfg, 'GOODMi: сводка сервера не отвечает ' + str(state['fails']) + ' раза подряд (' + str(e)[:200] +
                     '). Проверить сайт goodmi.ru и сервер.')
                state['fail_sent'] = True
            except Exception as e2:
                log('Telegram: ' + str(e2)[:200])
        save_state(state)
        return
    messages = []
    if state.get('fail_sent'):
        messages.append('GOODMi: сводка сервера снова отвечает.')
    alerts = d.get('alerts') or []
    if alerts:
        messages.append('GOODMi – тревоги за ' + str(hours) + ' ч (через Mac):\n' + '\n'.join('• ' + a for a in alerts))
    today = time.strftime('%Y-%m-%d')
    if test or (hour >= DAY_SUMMARY_HOUR and state.get('day_sent') != today):
        messages.append(day_summary(d))
    try:
        for m in messages:
            send(cfg, m)
    except Exception as e:
        # окно не сдвигаем – на следующей проверке тревоги попадут в сводку снова
        log('Telegram не принял: ' + str(e)[:200])
        save_state(state)
        return
    if not test:
        state['last_ok'] = now
        if hour >= DAY_SUMMARY_HOUR:
            state['day_sent'] = today
    state['fails'] = 0
    state['fail_sent'] = False
    save_state(state)
    log('окно ' + str(hours) + ' ч, тревог ' + str(len(alerts)) + ', сообщений ' + str(len(messages)))


if __name__ == '__main__':
    main()
