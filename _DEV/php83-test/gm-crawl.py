#!/usr/bin/env python3
# Обход тестовых витрин (2026-10-18, тест PHP 8.3): адреса – из карт сайта ab__advanced_sitemap в копии (боевые домены
# заменяются на тестовые), все категории/страницы/блог/бренды/фильтры + по 300 случайных товаров на витрину. 2 потока.
# Боевой goodmi.ru меряется раз в 5 с; дважды подряд дольше 1 с – обход останавливается. Запуск: python3 -I gm-crawl.py
import concurrent.futures, glob, random, re, ssl, subprocess, sys, threading, time, urllib.request, base64, collections

DEV = '/var/www/www-root/data/www/dev.maxmobiles.ru'
SITES = {1: ('maxmobiles.ru', 'dev.maxmobiles.ru'), 2: ('goodmi.ru', 'dev.goodmi.ru'), 3: ('xione.ru', 'dev.mi92.ru')}
AUTH = 'Basic ' + base64.b64encode(open('/root/.gm-dev-auth').read().strip().encode()).decode()
CTX = ssl._create_unverified_context()
random.seed(42)
stop = threading.Event()

def guard():
    bad = 0
    while not stop.is_set():
        t0 = time.time()
        try:
            r = subprocess.run(['curl', '-sk', '-m', '5', '--resolve', 'goodmi.ru:443:82.202.160.72', '-o', '/dev/null',
                                '-w', '%{time_total}', 'https://goodmi.ru/'], capture_output=True, text=True)
            t = float(r.stdout or 9)
        except Exception:
            t = 9
        bad = bad + 1 if t > 1 else 0
        if bad >= 2:
            print('ОСТАНОВ: боевой goodmi.ru отвечает', t, 'с', flush=True)
            stop.set()
        time.sleep(5)

def urls_for(cid, live, dev):
    out = []
    for f in sorted(glob.glob('%s/var/files/%d/ab__advanced_sitemap/*.xml' % (DEV, cid))):
        kind = re.sub(r'\d*\.xml$', '', f.rsplit('/', 1)[1])
        if kind in ('sitemap', 'images'):
            continue
        locs = re.findall(r'<loc>([^<]+)</loc>', open(f, encoding='utf-8', errors='replace').read())
        locs = [l.replace('https://' + live, 'https://' + dev).replace('https://www.' + live, 'https://' + dev) for l in locs]
        locs = [l for l in locs if l.startswith('https://' + dev)]
        if kind == 'products':
            locs = random.sample(locs, min(300, len(locs)))
        elif len(locs) > 400:
            locs = random.sample(locs, 400)
        out += [(kind, l) for l in locs]
    return out

def fetch(item):
    kind, url = item
    if stop.is_set():
        return kind, url, 0, 0
    req = urllib.request.Request(url, headers={'Authorization': AUTH, 'User-Agent': 'gm-crawl-php83-test'})
    t0 = time.time()
    try:
        with urllib.request.urlopen(req, context=CTX, timeout=60) as r:
            r.read(); code = r.status
    except urllib.error.HTTPError as e:
        code = e.code
    except Exception:
        code = -1
    return kind, url, code, time.time() - t0

threading.Thread(target=guard, daemon=True).start()
for cid, (live, dev) in SITES.items():
    items = urls_for(cid, live, dev)
    res = []
    with concurrent.futures.ThreadPoolExecutor(2) as ex:
        res = list(ex.map(fetch, items))
    by = collections.defaultdict(list)
    for kind, url, code, t in res:
        by[kind].append((code, t, url))
    print('\n=== %s: адресов %d' % (dev, len(res)), flush=True)
    for kind in sorted(by):
        rows = by[kind]
        codes = collections.Counter(c for c, _, _ in rows)
        ts = sorted(t for _, t, _ in rows)
        print('  %-18s %4d  коды %s  медиана %.2f с, 95%% %.2f с, макс %.2f с' % (
            kind, len(rows), dict(codes), ts[len(ts) // 2], ts[int(len(ts) * .95)], ts[-1]))
        for c, t, u in rows:
            if c >= 500 or c < 0:
                print('     ОШИБКА %s %s' % (c, u))
        slow = sorted(rows, key=lambda r: -r[1])[:2]
        for c, t, u in slow:
            if t > 2:
                print('     долго %.1f с %s' % (t, u))
    if stop.is_set():
        break
stop.set()
