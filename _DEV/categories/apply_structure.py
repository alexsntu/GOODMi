# -*- coding: utf-8 -*-
# Вносит утверждённую 2026-10-12 структуру категорий GOODMi через API магазина.
# Правила берутся из build_proposal.py (RENAME, MOVE, HIDE, NEW, ROOT_ORDER). Журнал – apply-log.txt.
import io, sys, json, time
sys.path.insert(0, '.')
from api import call
src = io.open('build_proposal.py', encoding='utf-8').read().split("for k,v in NEW.items()")[0]
exec(src)
RENAME['611'] = 'Дозаторы для мыла'          # остаётся одна категория дозаторов (решение пользователя)
MOVE.pop('595', None); HIDE['595'] = 'дубль категории 611, вместо неё переадресация'
log = io.open('apply-log.txt', 'a', encoding='utf-8')
def say(*a):
    s = ' '.join(str(x) for x in a); print(s); log.write(s + '\n'); log.flush()
def put(cid, data, what):
    st, r = call('PUT', '/categories/%s' % cid, data)
    say(('OK ' if st == 200 else 'ОШИБКА %s ' % st) + what, '' if st == 200 else r)
    return st == 200
errors = 0
say('=== запуск', time.strftime('%Y-%m-%d %H:%M:%S'))
# 1. новые категории
ids = {}
for key in ('N1', 'N2'):
    par = NEW[key]['par']; par = ids.get(par, par)
    st, r = call('POST', '/categories', {'category': NEW[key]['name'], 'parent_id': int(par), 'company_id': 2, 'status': 'A', 'position': 500})
    if st in (200, 201) and r.get('category_id'):
        ids[key] = str(r['category_id']); say('OK создана «%s» id=%s' % (NEW[key]['name'], ids[key]))
    else:
        say('ОШИБКА создание', NEW[key]['name'], st, r); sys.exit(1)
# 2. переименования
for cid, name in RENAME.items():
    if cid == '1410': continue                      # уже сделано пробой
    errors += not put(cid, {'category': name}, 'название %s: «%s» → «%s»' % (cid, cat[cid]['name'], name))
# 3. переносы
for cid, par in MOVE.items():
    if cid == '586': continue                       # уже сделано пробой
    par = ids.get(par, par)
    errors += not put(cid, {'parent_id': int(par)}, 'перенос %s «%s» → родитель %s' % (cid, RENAME.get(cid, cat[cid]['name']), par))
# 4. скрыть
for cid, why in HIDE.items():
    errors += not put(cid, {'status': 'H'}, 'скрыта %s «%s» (%s)' % (cid, cat[cid]['name'], why))
# 5. порядок: корни – по утверждённому списку, второй уровень – по выручке за год
for key, v in NEW.items():
    cat[ids[key]] = {'id': ids[key], 'par': ids.get(v['par'], v['par']), 'pos': 0, 'name': v['name'], 'prod': 0, 'rev': 0}
for cid, par in MOVE.items():
    cat[cid]['par'] = ids.get(par, par)
order = [ids.get(k, k) for k in ROOT_ORDER]
for i, cid in enumerate(order):
    errors += not put(cid, {'position': (i + 1) * 10}, 'порядок корня %d: %s' % (i + 1, RENAME.get(cid, cat[cid]['name'])))
def rev(cid):
    return cat[cid]['rev'] + sum(rev(k) for k, c in cat.items() if c['par'] == cid and c.get('rev', 0) == 0 and k in ids.values())
for root in order:
    kids = [k for k, c in cat.items() if c['par'] == root and k not in HIDE]
    if len(kids) < 2: continue
    kids.sort(key=lambda k: (-(cat[k]['rev'] if k not in ids.values() else sum(cat[x]['rev'] for x, c in cat.items() if c['par'] == k)), cat[k]['pos']))
    for i, cid in enumerate(kids):
        errors += not put(cid, {'position': (i + 1) * 10}, '  порядок в «%s» %d: %s' % (RENAME.get(root, cat[root]['name']), i + 1, RENAME.get(cid, cat[cid]['name'])))
json.dump(ids, io.open('new-ids.json', 'w', encoding='utf-8'))
say('=== готово, ошибок:', errors, '| новые id:', ids)
