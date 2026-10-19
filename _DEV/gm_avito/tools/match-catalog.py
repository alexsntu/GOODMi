#!/usr/bin/env python3
"""Сопоставление телефонов, планшетов и телевизоров GOODMi с каталогами автозагрузки Авито (2026-10-19).
  python3 -I match-catalog.py <devices.json> <каталоги-папка> <out.json>
devices.json – [product_id, тип ph/tab/tv, бренд, модель, память, ОЗУ, цвет, название] (выгрузка с сайта).
Для каждого товара – поля каталога (Vendor/Brand, Model, MemorySize, Color, RamSize …), только если такое сочетание
есть в каталоге; иначе – причина. Результат: {"fields": {product_id: {...}}, "problems": {product_id: "..."}}."""
import json
import re
import sys
import xml.etree.ElementTree as ET

devices = json.load(open(sys.argv[1]))
folder = sys.argv[2].rstrip('/')

COLORS = {
    'black': 'черный', 'white': 'белый', 'gray': 'серый', 'grey': 'серый', 'silver': 'серебристый', 'blue': 'синий',
    'green': 'зеленый', 'purple': 'фиолетовый', 'violet': 'фиолетовый', 'pink': 'розовый', 'gold': 'золотой',
    'orange': 'оранжевый', 'red': 'красный', 'yellow': 'желтый', 'brown': 'коричневый', 'beige': 'бежевый',
}
VENDORS = {'redmi': 'Xiaomi', 'poco': 'Xiaomi', 'xiaomi': 'Xiaomi', 'samsung': 'Samsung', 'google': 'Google'}


def norm(text):
    text = (text or '').lower().replace('ё', 'е').replace('+', ' plus ')
    text = re.sub(r'\b(xiaomi|samsung|google|смартфон|планшет|телевизор)\b', ' ', text)
    return re.sub(r'\s+', ' ', text).strip()


def mem(text):
    m = re.search(r'(\d+)\s*(гб|gb|тб|tb)', (text or '').lower())
    if not m:
        return ''
    if m.group(2) in ('тб', 'tb'):
        return str(int(m.group(1)) * 1024) + ' ГБ'
    return m.group(1) + ' ГБ'


def color_names(text):
    """Варианты цвета товара: русская часть после «/», затем перевод английских слов"""
    text = (text or '').lower().replace('ё', 'е')
    out = []
    for part in reversed([p.strip() for p in text.split('/')]):
        if part:
            out.append(part)
            for en, ru in COLORS.items():
                if re.search(r'\b' + en + r'\b', part):
                    out.append(ru)
    return out


def tree(path):
    """Каталог как вложенные словари: имя элемента -> {name: поддерево}"""
    def walk(el):
        return {child.get('name'): (child.tag, walk(child)) for child in el}
    return walk(ET.parse(path).getroot())


def find_model(models, model, name):
    """Модель каталога по модели сайта (или названию товара): точное совпадение, затем без 4G/5G"""
    index = {norm(m): m for m in models}
    candidates = []
    for source in (model, re.sub(r'\d+/\d+\s*(gb|гб|tb|тб).*$', '', name or '', flags=re.I)):
        n = norm(source)
        if not n:
            continue
        candidates += [n, re.sub(r'\s*[45]g$', '', n), n + ' 5g', n + ' 4g', n.replace('pro plus', 'pro plus')]
    for c in candidates:
        if c in index:
            return index[c]
    return None


phones = tree(folder + '/avito-phone_catalog.xml')
result = {'fields': {}, 'problems': {}}
for pid, kind, brand, model, memory, ram, color, name in devices:
    if kind != 'ph':
        continue
    vendor = VENDORS.get((brand or '').lower()) or VENDORS.get((name.split()[1] if len(name.split()) > 1 else '').lower())
    if not vendor or vendor not in phones:
        result['problems'][pid] = 'марка «%s» не найдена в каталоге' % brand
        continue
    models = phones[vendor][1]
    m = find_model(models, model, name)
    if not m:
        result['problems'][pid] = 'модель «%s» (%s) не найдена у %s' % (model, name, vendor)
        continue
    memories = models[m][1]
    ms = mem(memory) or mem(name.split('/')[-1] if '/' in name else '')
    if ms not in memories:
        result['problems'][pid] = 'память «%s» нет у %s %s (есть: %s)' % (memory, vendor, m, ', '.join(memories))
        continue
    colors = memories[ms][1]
    cmap = {c.lower().replace('ё', 'е'): c for c in colors}
    c = next((cmap[x] for x in color_names(color) + color_names(name.split()[-1]) if x in cmap), None)
    if not c:
        c = cmap.get('другое')
    if not c:
        result['problems'][pid] = 'цвет «%s» нет у %s %s %s (есть: %s)' % (color, vendor, m, ms, ', '.join(colors))
        continue
    rams = colors[c][1]
    r = mem(ram) or (mem(re.search(r'(\d+)/\d+', name).group(1) + ' gb') if re.search(r'(\d+)/\d+', name) else '')
    if r not in rams:
        if len(rams) == 1 and not r:
            r = next(iter(rams))
        else:
            result['problems'][pid] = 'ОЗУ «%s» нет у %s %s %s %s (есть: %s)' % (ram, vendor, m, ms, c, ', '.join(rams))
            continue
    result['fields'][pid] = {'Vendor': vendor, 'Model': m, 'MemorySize': ms, 'Color': c, 'RamSize': r}
    result.setdefault('check', {})[pid] = '%s | %s → %s %s %s %s %s' % (name, color, vendor, m, ms, c, r)

json.dump(result, open(sys.argv[3], 'w'), ensure_ascii=False, indent=1)
print('телефонов сопоставлено:', len(result['fields']), ' не сопоставлено:', len(result['problems']))
for pid, why in list(result['problems'].items())[:40]:
    print(' ', pid, why[:230])
