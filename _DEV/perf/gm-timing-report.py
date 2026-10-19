#!/usr/bin/env python3
# Разбор журнала времени ответа PHP (/var/log/nginx/gm-timing-<сайт>.log, 2026-10-19): какие запросы занимают процессы PHP.
# Запуск на сервере: python3 -I gm-timing-report.py [сайт ...]. Только чтение.
import collections, re, sys, glob, gzip

SITES = sys.argv[1:] or ['goodmi.ru', 'maxmobiles.ru', 'xione.ru']
LINE = re.compile(r'^(\S+) (\S+) (\S+) "(\S+) (\S+)[^"]*" (\d{3}) (\d+) rt=([\d.]+) uht=([\d.,-]+) urt=([\d.,-]+) sid=(\d) "([^"]*)"')
BOT = re.compile(r'bot|crawl|spider|slurp|yandex|google|bing|mail\.ru|petal|semrush|ahrefs|mj12|dotbot|curl|python|go-http|wget|ncdn', re.I)


def kind(uri):
    path, _, q = uri.partition('?')
    d = re.search(r'(?:^|&)dispatch=([\w.]+)', q)
    if d:
        return 'dispatch=' + d.group(1)
    if path.endswith('.php') and path != '/index.php':
        return path
    if path in ('/', '/index.php'):
        return '/ главная'
    if 'features_hash' in q:
        return 'ЧПУ + фильтр (features_hash)'
    if re.search(r'[?&](page|sort_by|items_per_page)=', q):
        return 'ЧПУ + страница/сортировка'
    return 'ЧПУ (категория/товар/страница)'


def num(v):
    try:
        return float(v.split(',')[-1])
    except ValueError:
        return 0.0


for site in SITES:
    files = sorted(glob.glob('/var/log/nginx/gm-timing-%s.log*' % site))
    rows = []
    for f in files:
        op = gzip.open if f.endswith('.gz') else open
        with op(f, 'rt', errors='replace') as fh:
            for line in fh:
                m = LINE.match(line)
                if m:
                    rows.append(m.groups())
    if not rows:
        continue
    total = sum(num(r[9]) for r in rows)
    print('\n' + '=' * 100)
    print('%s: запросов к PHP %d с %s по %s, суммарное время работы PHP %.0f с (%.1f ч)' % (
        site, len(rows), rows[0][0][:16], rows[-1][0][:16], total, total / 3600))
    by = collections.defaultdict(list)
    for r in rows:
        by[kind(r[4])].append(r)
    print('\n%-44s %7s %7s %6s %6s %6s %5s %5s %6s' % ('тип запроса', 'шт', 'доля', 'сред', '95%', 'макс', '>1с', '>3с', 'люди'))
    for k, rs in sorted(by.items(), key=lambda kv: -sum(num(r[9]) for r in kv[1]))[:22]:
        ts = sorted(num(r[9]) for r in rs)
        s = sum(ts)
        human = sum(1 for r in rs if r[10] == '1' and not BOT.search(r[11]))
        print('%-44s %7d %6.1f%% %6.2f %6.2f %6.1f %5d %5d %5.0f%%' % (
            k[:44], len(rs), 100 * s / total, s / len(rs), ts[int(len(ts) * .95)], ts[-1],
            sum(1 for t in ts if t > 1), sum(1 for t in ts if t > 3), 100 * human / len(rs)))
    # Кто: люди / боты по UA
    who = collections.Counter()
    whot = collections.Counter()
    for r in rows:
        ua = r[11]
        b = BOT.search(ua)
        k = ('бот: ' + b.group(0).lower()) if b else ('человек (cookie)' if r[10] == '1' else 'без cookie, браузер')
        who[k] += 1
        whot[k] += num(r[9])
    print('\nкто нагружает PHP:')
    for k, n in whot.most_common(8):
        print('   %-28s %7d запросов, %5.1f%% времени PHP' % (k, who[k], 100 * n / total))
    # Очередь: uht - urt не показывает очередь (оба считают от отправки в upstream); смотрим долю долгих по часам
    print('\nпо часам: запросов, среднее время PHP, запросов >1 с, >3 с')
    hours = collections.defaultdict(list)
    for r in rows:
        hours[r[0][:13]].append(num(r[9]))
    for h in sorted(hours):
        ts = hours[h]
        print('   %s  %6d  %5.2f с  %4d  %4d' % (h.replace('T', ' '), len(ts), sum(ts) / len(ts),
                                                sum(1 for t in ts if t > 1), sum(1 for t in ts if t > 3)))
    # Самые долгие отдельные запросы
    print('\nсамые долгие запросы:')
    for r in sorted(rows, key=lambda r: -num(r[9]))[:12]:
        print('   %5.1f с  %s  %s %s  %s' % (num(r[9]), r[0][5:19], r[5], r[4][:90], r[11][:40]))
