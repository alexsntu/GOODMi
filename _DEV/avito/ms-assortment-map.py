#!/usr/bin/env python3
# Сопоставление товаров МойСклад: UUID (id) ↔ внешний код (externalCode) – для модуля Авито (2026-10-19).
# Id объявлений в нынешнем фиде UnitXML = UUID товаров МойСклад, а CS-Cart знает товары по externalCode
# (cscart_commerceml_import_entity_map). Ключи Basic берутся из app/controllers/frontend/moysklad.php и НЕ печатаются.
# Только чтение (GET). Запуск на сервере от root:
#   python3 -I ms-assortment-map.py probe            – какие ключи к какому аккаунту МойСклад
#   python3 -I ms-assortment-map.py fetch <N> <out>  – выгрузить весь ассортимент аккаунта по ключу №N в JSON
import gzip, json, re, sys, time, urllib.request

SRC = '/var/www/maxmobiles/data/www/maxmobiles.ru/app/controllers/frontend/moysklad.php'
API = 'https://api.moysklad.ru/api/remap/1.2'
tokens = []
for t in re.findall(r'Authorization:\s*Basic\s+([A-Za-z0-9+/=]+)', open(SRC, encoding='utf-8', errors='replace').read()):
    if t not in tokens:
        tokens.append(t)


def get(url, token):
    req = urllib.request.Request(url, headers={'Authorization': 'Basic ' + token, 'Accept-Encoding': 'gzip',
                                               'User-Agent': 'goodmi-avito-map'})
    with urllib.request.urlopen(req, timeout=60) as r:
        body = r.read()
        if r.headers.get('Content-Encoding') == 'gzip':
            body = gzip.decompress(body)
        return json.loads(body)


if sys.argv[1] == 'probe':
    print('ключей в файле:', len(tokens))
    for i, t in enumerate(tokens):
        try:
            d = get(API + '/entity/assortment?limit=1', t)
            row = (d.get('rows') or [{}])[0]
            print('ключ №%d: аккаунт %s, товаров/ассортимента %s' % (i, row.get('accountId'), d.get('meta', {}).get('size')))
        except Exception as e:
            print('ключ №%d: ошибка %s' % (i, str(e)[:80]))
elif sys.argv[1] == 'fetch':
    t = tokens[int(sys.argv[2])]
    out, offset = {}, 0
    while True:
        d = get(API + '/entity/assortment?limit=1000&offset=%d' % offset, t)
        for row in d.get('rows', []):
            out[row['id']] = {'externalCode': row.get('externalCode'), 'name': row.get('name'), 'code': row.get('code'),
                              'type': row.get('meta', {}).get('type'),
                              'product_id': (row.get('product') or {}).get('meta', {}).get('href', '').rsplit('/', 1)[-1] or None}
        offset += 1000
        if offset >= d.get('meta', {}).get('size', 0):
            break
        time.sleep(0.3)
    json.dump(out, open(sys.argv[3], 'w'), ensure_ascii=False)
    print('выгружено позиций:', len(out))
