from pathlib import Path
import re, json

root = Path(__file__).resolve().parents[1]
src = root / 'Категории/Готовые'
dst = root / 'Категории/Новые измененные'
html = (src / 'xiaomi-buds-6.html').read_text(encoding='utf-8-sig')
intro = 'Беспроводные наушники Xiaomi Buds 6 (Сяоми) в GOODMi &#8212; магазине электроники и гаджетов в Крыму. Сравните характеристики, совместимость со смартфоном и комплектацию в карточке товара. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму. Наличие и условия гарантии уточняйте перед покупкой.'
html = re.sub(r'(<div class="gm-intro-text">\s*<p>).*?(</p>)', lambda m: m[1]+intro+m[2], html, count=1, flags=re.S)
html = html.replace('<strong>Гарантия 1 год</strong> на новую технику', '<strong>Гарантия</strong> по условиям товара')
html = html.replace('<strong>Трейд-ин</strong> сдайте старое', '<strong>Трейд-ин</strong> по условиям программы')
replacements = {
'Оформите заказ на goodmi.ru': 'Оформите заказ на goodmi.ru или позвоните 8 (800) 250-17-00. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму: в Севастополе, Симферополе и Ялте. Стоимость, сроки доставки и способы оплаты уточняются при оформлении заказа.',
'Официальная гарантия 1 год на наушники': 'Срок и условия гарантии на Xiaomi Buds 6 указаны в карточке товара и документах к покупке. По вопросам обслуживания обратитесь в GOODMi по телефону 8 (800) 250-17-00 или через чат на сайте.',
'Принесите любое исправное устройство': 'Возможность участия устройства в трейд-ин зависит от его модели и состояния. Перед покупкой Xiaomi Buds 6 уточните условия оценки у консультанта или на странице <a href="https://goodmi.ru/treyd-in-goodmi/">программы GOODMi</a>.',
}
for prefix, replacement in replacements.items():
    html, count = re.subn(r'<p>'+re.escape(prefix)+r'.*?</p>', lambda m: '<p>'+replacement+'</p>', html, flags=re.S)
    assert count == 1, prefix
html = html.replace('Как сдать старые наушники или смартфон по трейд-ин в GOODMi?', 'Как воспользоваться программой трейд-ин в GOODMi?')
html = html.replace('<strong>Официальная гарантия 1 год</strong> на наушники Xiaomi Buds 6 &#8212; гарантийное обслуживание в точках GOODMi в Севастополе, Симферополе и Ялте.', '<strong>Гарантия на Xiaomi Buds 6</strong> &#8212; срок и условия указаны в карточке товара и документах к покупке.')
html = html.replace('&#8212; 2&#8211;7 дней от момента заказа. Самовывоз в 6 точках Крыма: Севастополь, Симферополь, Ялта.', '&#8212; стоимость и сроки уточняются при оформлении заказа. Самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь, Ялта.')
html = html.replace('&#8212; сдайте старые наушники или смартфон и получите скидку на Xiaomi Buds 6. Оценка бесплатна в любой точке GOODMi.', '&#8212; уточните возможность участия вашего устройства и условия оценки перед покупкой Xiaomi Buds 6.')
html = html.replace('<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.', '<strong>Отзывы о GOODMi на Яндекс Картах</strong> &#8212; узнайте, что покупатели пишут о магазине электроники и гаджетов в Крыму.')
schema_src = (src / 'xiaomi-buds-6-jsonld.html').read_text(encoding='utf-8-sig')
objects = [json.loads(s) for s in re.findall(r'<script[^>]*>(.*?)</script>', schema_src, re.S)]
reference = (root / 'Категории/Перезалитые/planshety-jsonld.html').read_text(encoding='utf-8-sig')
business = json.loads(re.findall(r'<script[^>]*>(.*?)</script>', reference, re.S)[0])
objects[0] = business
business['alternateName'] = ['Магазин электроники GOODMi']
description = 'Беспроводные наушники Xiaomi Buds 6 в GOODMi. Самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта. Доставка СДЭК по России.'
objects[1]['description'] = description
schema = '\n\n'.join('<script type="application/ld+json">\n'+json.dumps(o, ensure_ascii=False, indent=2)+'\n</script>' for o in objects)+'\n'
assert not re.search(r'фирменн|официальн|крупнейш|2000 отзыв|в наличии|2&#8211;7', html+schema, re.I)
dst.mkdir(exist_ok=True)
for name, content in [('xiaomi-buds-6.html',html), ('xiaomi-buds-6-jsonld.html',schema)]:
    target = dst / name
    assert not target.exists()
    (src / name).write_text(content, encoding='utf-8')
    (src / name).rename(target)
tracker = root / '_DEV/positioning-rebrand-task.md'
text = tracker.read_text(encoding='utf-8-sig')
text = re.sub(r'- Последняя обработанная категория:.*', '- Последняя обработанная категория: `xiaomi-buds-6` — HTML + JSON-LD в «Новые измененные», ожидают проверки пользователя. Планшеты уже в «Перезалитые».', text, count=1)
text = re.sub(r'- Точка продолжения на завтра:.*', '- Следующая пара по графику: `xiaomi-watch-s5` (№81–82).', text, count=1)
for line in text.splitlines():
    if '| Категории/Готовые/xiaomi-buds-6' in line:
        text = text.replace(line, line.replace('⬜', '🔶 в «Новые измененные», ожидает проверки'))
    if '| Категории/Готовые/planshety-' in line:
        text = text.replace(line, re.sub(r'\| [^|]+ \|$', '| ✅ в «Перезалитые», проверено 2026-09-24 |', line))
text += '\n### Мета Xiaomi Buds 6 — 2026-09-24\n\nTitle: Xiaomi Buds 6 — купить в Севастополе и Крыму | GOODMi\n\nDescription: '+description+'\n'
tracker.write_text(text, encoding='utf-8')
print('Готово: HTML + JSON-LD перемещены; 2 JSON-LD объекта валидны; старое позиционирование не найдено.')
