#!/usr/bin/env python3
"""Совет по ставкам Авито «цена целевого действия» (пакет кликов = оплата за просмотр), 2026-10-22. Только расчёт.
  cpx-advice.py <company_id> <items-N.tsv> <bids-N.json> <выход.tsv> [конверсия просмотр→покупка, по умолчанию 0.01]
items – выгрузка объявлений за 30 дней (avito_id, product_id, title, price, amount, cat, imp, views, contacts, spend_kop, fav);
bids – ответы /cpxpromo/1/getBids/{id}. Ценность просмотра = цена × доля прибыли группы × конверсия просмотр→покупка
(пользователь: 1%). Конверсия просмотр→контакт (сглажена: объявление → раздел → витрина) – для справки. Ставка – ступень сетки Авито с наибольшей
ожидаемой прибылью прогноз × (ценность − ставка); от срока прогноза выбор не зависит."""
import csv, json, sys, collections

cid, items_f, bids_f, out_f = int(sys.argv[1]), sys.argv[2], sys.argv[3], sys.argv[4]
p_buy = float(sys.argv[5]) if len(sys.argv) > 5 else 0.01

# наценка пользователя (середина диапазона) → доля прибыли в цене продажи k/(1+k)
MARKUP = {1: {'el': 0.125, 'gad': 0.275, 'acc': 1.125}, 2: {'el': 0.275, 'gad': 0.425, 'acc': 1.125}}[cid]
MARGIN = {g: k / (1 + k) for g, k in MARKUP.items()}

EL = {
    1: ('iPhone', 'iPad', 'Mac', 'iMac', 'Apple Display', 'Vision Pro', 'Приставки', 'Dyson', 'Apple TV', 'Виртуальная реальность'),
    2: ('Ноутбуки', 'Мониторы', 'Роутеры', 'Проекторы', 'Саундбары', 'ТВ-приставки', 'Игровые консоли', 'Электросамокаты',
        'Портативные электростанции', 'Климатическая техника', 'Обогреватели', 'Вентиляторы', 'Увлажнители воздуха',
        'Очистители воздуха', 'пылесосы', 'Пылесосы', 'Электрические чайники', 'Грили', 'Блендеры', 'Тостеры', 'Все для кофе',
        'Кастрюли', 'Фены', 'Стайлеры', 'Отпариватели', 'Фотопринтеры', 'Солнечные панели', 'Электроинструмент',
        'Камеры видеонаблюдения', 'Видеорегистраторы', 'Компрессоры', 'Пуско-зарядные'),
}[cid]
ACC = {
    1: ('Аксессуары для Mac', 'Игровые аксессуары', 'Аксессуары'),
    2: ('Держатели для телефона', 'Беспроводные зарядки', 'Сетевые зарядки', 'Зарядки в авто', 'Кабели', 'Чехлы',
        'Парковочные карты', 'Удлинители'),
}[cid]


def group(cat):
    if any(cat.startswith(a) for a in ACC):
        return 'acc'
    if any(e in cat for e in EL):
        return 'el'
    return 'gad'


rows = {}
for r in csv.DictReader(open(items_f), delimiter='\t'):
    rows.setdefault(r['avito_id'], r)
bids = json.load(open(bids_f))

tv = sum(int(r['views']) for r in rows.values())
tc = sum(int(r['contacts']) for r in rows.values())
prior = (tc + 1) / (tv + 50)
cat_v, cat_c = collections.Counter(), collections.Counter()
for r in rows.values():
    cat_v[r['cat']] += int(r['views'])
    cat_c[r['cat']] += int(r['contacts'])
cat_conv = {c: (cat_c[c] + 50 * prior) / (cat_v[c] + 50) for c in cat_v}

out = []
for aid, r in rows.items():
    b = bids.get(aid) or {}
    m = b.get('manual') or {}
    ladder = [x for x in m.get('bids', []) if x.get('valuePenny')]
    price = float(r['price'] or 0)
    g = group(r['cat'] or '')
    conv = (int(r['contacts']) + 30 * cat_conv.get(r['cat'], prior)) / (int(r['views']) + 30)
    view_value = price * MARGIN[g] * p_buy
    contact_value = view_value / conv if conv else 0
    best, best_profit = None, 0.0
    for x in ladder:
        bid = x['valuePenny'] / 100
        fc = (x['minForecast'] + x['maxForecast']) / 2
        profit = fc * (view_value - bid)
        if profit > best_profit:
            best, best_profit = x, profit
    rec = (m.get('recBidPenny') or 0) / 100
    rec_x = min(ladder, key=lambda x: abs(x['valuePenny'] - (m.get('recBidPenny') or 0))) if ladder else None
    out.append({
        'avito_id': aid, 'product_id': r['product_id'], 'title': r['title'], 'cat': r['cat'], 'group': g,
        'price': int(price), 'amount': r['amount'], 'views30': r['views'], 'contacts30': r['contacts'],
        'spend30': round(int(r['spend_kop']) / 100), 'conv': round(conv * 100, 2),
        'contact_value': round(contact_value), 'view_value': round(view_value, 2),
        'min_bid': (m.get('minBidPenny') or 0) / 100, 'rec_bid': rec,
        'rec_compare': rec_x['compare'] if rec_x else '', 'rec_fc': '%s–%s' % (rec_x['minForecast'], rec_x['maxForecast']) if rec_x else '',
        'advice_bid': best['valuePenny'] / 100 if best else '', 'advice_compare': best['compare'] if best else '',
        'advice_fc': '%s–%s' % (best['minForecast'], best['maxForecast']) if best else '',
        'advice_profit_idx': round(best_profit), 'error': b.get('error', ''),
    })
out.sort(key=lambda x: -x['advice_profit_idx'])
w = csv.DictWriter(open(out_f, 'w'), fieldnames=list(out[0].keys()), delimiter='\t')
w.writeheader()
w.writerows(out)

print('витрина', cid, 'объявлений', len(out), 'конверсия просмотр→контакт по витрине %.1f%%' % (prior * 100), 'доля прибыли', {g: round(v * 100) for g, v in MARGIN.items()})
worth = [x for x in out if x['advice_bid'] != '']
print('ставка выше минимума окупается:', len(worth), 'из', len(out), '; без данных Авито:', sum(1 for x in out if x['error'] or not x['min_bid']))
for g in ('el', 'gad', 'acc'):
    gs = [x for x in out if x['group'] == g]
    gw = [x for x in gs if x['advice_bid'] != '']
    print(' ', g, len(gs), 'окупается', len(gw), 'средняя ценность просмотра %.1f ₽' % (sum(x['view_value'] for x in gs) / max(1, len(gs))),
          'средняя рекомендация Авито %.0f ₽' % (sum(x['rec_bid'] for x in gs) / max(1, len(gs))))
