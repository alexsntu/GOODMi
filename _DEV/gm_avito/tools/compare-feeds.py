#!/usr/bin/env python3
"""Сравнение файла модуля gm_avito с фидом UnitXML (2026-10-19): python3 -I compare-feeds.py <unitxml.xml> <feed.xml>
По каждому Id: есть ли в обоих, совпадают ли поля (кроме Images), цена, число фото; итог по каждому полю."""
import collections
import sys
import xml.etree.ElementTree as ET


def load(path):
    ads = {}
    for ad in ET.parse(path).getroot().findall('Ad'):
        fields = {}
        for el in ad:
            if el.tag == 'Images':
                fields['Images'] = [i.get('url') for i in el.findall('Image')]
            else:
                fields[el.tag] = (el.text or '').strip()
        ads[fields.get('Id')] = fields
    return ads


old, new = load(sys.argv[1]), load(sys.argv[2])
print('UnitXML:', len(old), ' модуль:', len(new))
print('только в UnitXML:', len(old.keys() - new.keys()), ' только в модуле:', len(new.keys() - old.keys()))
diff = collections.Counter()
examples = collections.defaultdict(list)
photos = collections.Counter()
for ad_id in old.keys() & new.keys():
    a, b = old[ad_id], new[ad_id]
    for tag in (a.keys() | b.keys()) - {'Images'}:
        if a.get(tag) != b.get(tag):
            diff[tag] += 1
            if len(examples[tag]) < 3:
                examples[tag].append((ad_id, (a.get(tag) or '')[:80], (b.get(tag) or '')[:80]))
    na, nb = len(a.get('Images', [])), len(b.get('Images', []))
    photos['поровну' if na == nb else ('в модуле больше' if nb > na else 'в модуле меньше')] += 1
print('фото:', dict(photos))
print('расхождения по полям:', dict(diff) or 'нет')
for tag, rows in examples.items():
    for ad_id, x, y in rows:
        print(f'  {tag} {ad_id}: UnitXML «{x}» → модуль «{y}»')
