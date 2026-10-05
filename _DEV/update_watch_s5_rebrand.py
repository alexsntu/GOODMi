from pathlib import Path
import json, re

root = Path(__file__).resolve().parents[1]
src, dst = [root / 'Категории' / p for p in ('Готовые', 'Новые измененные')]
name = 'xiaomi-watch-s5'
html = (src / (name+'.html')).read_text(encoding='utf-8-sig')
def paragraph(prefix, replacement):
    global html
    html, n = re.subn(r'<p>'+re.escape(prefix)+r'.*?</p>', lambda m:'<p>'+replacement+'</p>', html, flags=re.S)
    assert n == 1, (prefix,n)
paragraph('Xiaomi Watch S5 (Сяоми)', 'Смарт-часы Xiaomi Watch S5 в GOODMi &#8212; магазине электроники и гаджетов в Крыму. Модель 46mm оснащена AMOLED-экраном и корпусом из нержавеющей стали 316L. До 21 дня работы при лёгком использовании; автономность зависит от настроек и нагрузки. Гарантия 1 год. Доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму. Наличие, комплектацию и совместимость уточняйте в карточке товара.')
paragraph('Xiaomi Watch S5 выпускается', 'При выборе Xiaomi Watch S5 проверьте региональную версию, совместимость со смартфоном и доступность нужных функций. У модели 46mm AMOLED-экран 1,48 дюйма и корпус из стали 316L. Поддержка отдельных сервисов зависит от региона и версии устройства; наличие NFC само по себе не гарантирует оплату вашей банковской картой.')
html = html.replace('Чем Сяоми Watch S5 отличается от Xiaomi Watch S4?', 'На сколько хватает заряда Xiaomi Watch S5?')
paragraph('Watch S5 получил', 'Для Xiaomi Watch S5 46mm производитель указывает до 21 дня при лёгком использовании, до 14 дней при обычном использовании и до 9 дней при обычном использовании с постоянно включённым экраном AOD. Фактическое время зависит от настроек, тренировок, звонков и других функций.')
paragraph('Оформите заказ на goodmi.ru', 'Оформите заказ на goodmi.ru или позвоните 8 (800) 250-17-00. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта. Стоимость, сроки доставки и способы оплаты уточняются при оформлении заказа.')
paragraph('Официальная гарантия 1 год', 'На Xiaomi Watch S5 действует гарантия 1 год. Условия обслуживания указаны в карточке товара и документах к покупке. По вопросам гарантии обратитесь в GOODMi по телефону 8 (800) 250-17-00 или через чат на сайте.')
html = html.replace('Как сдать старые часы или смартфон по трейд-ин в GOODMi?', 'Как воспользоваться трейд-ин при покупке Xiaomi Watch S5?')
paragraph('Принесите любое исправное устройство', 'Возможность участия устройства в трейд-ин зависит от модели и состояния. Перед покупкой Xiaomi Watch S5 уточните условия оценки у консультанта или на странице <a href="https://goodmi.ru/treyd-in-goodmi/">программы GOODMi</a>.')
html = html.replace('<strong>Гарантия 1 год</strong> на новую технику', '<strong>Гарантия 1 год</strong> на Xiaomi Watch S5')
html = html.replace('<strong>Трейд-ин</strong> сдайте старое', '<strong>Трейд-ин</strong> по условиям программы')
html = html.replace('<strong>Официальная гарантия 1 год</strong> на умные часы Xiaomi Watch S5 &#8212; обслуживание в точках GOODMi в Севастополе, Симферополе и Ялте.', '<strong>Гарантия 1 год</strong> на Xiaomi Watch S5 &#8212; условия обслуживания указаны в карточке товара и документах к покупке.')
html = html.replace('&#8212; 2&#8211;7 дней от момента заказа. Самовывоз в 6 точках Крыма:', '&#8212; стоимость и сроки уточняются при оформлении заказа. Самовывоз в 6 точках выдачи в Крыму:')
html = html.replace('&#8212; сдайте старый смартфон или часы и получите скидку на Xiaomi Watch S5. Оценка бесплатна в любой точке GOODMi.', '&#8212; уточните возможность участия вашего устройства и условия оценки перед покупкой Xiaomi Watch S5.')
html = html.replace('<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.', '<strong>Отзывы о GOODMi на Яндекс Картах</strong> &#8212; магазин электроники и гаджетов в Крыму. Ознакомьтесь с опытом покупателей и актуальным рейтингом.')
html = html.replace('Наши консультанты расскажут о моделях Xiaomi Watch S5 и помогут выбрать между Bluetooth и eSIM-версией.', 'Наши консультанты помогут выбрать Xiaomi Watch S5, проверить совместимость со смартфоном и уточнить функции выбранной версии.')
first = 'Смарт-часы Xiaomi Watch S5 46mm в GOODMi в Севастополе и Крыму — гарантия 1 год, AMOLED-экран и стальной корпус, заказывайте онлайн.'
desc = first+' До 21 дня при лёгком использовании. GOODMi — магазин электроники и гаджетов в Крыму с 2016 года.'
h1 = 'Часы Xiaomi Watch S5'
title = 'Купить Xiaomi Watch S5 в Севастополе, Крыму | GOODMi'
objects = [json.loads(s) for s in re.findall(r'<script[^>]*>(.*?)</script>', (src/(name+'-jsonld.html')).read_text(encoding='utf-8-sig'), re.S)]
ref = (root/'Категории/Перезалитые/planshety-jsonld.html').read_text(encoding='utf-8-sig')
objects[0] = json.loads(re.findall(r'<script[^>]*>(.*?)</script>',ref,re.S)[0])
objects[0]['alternateName'] = ['Магазин электроники GOODMi']
objects[1]['name'] = h1
objects[1]['description'] = first
schema = '\n\n'.join('<script type="application/ld+json">\n'+json.dumps(o,ensure_ascii=False,indent=2)+'\n</script>' for o in objects)+'\n'
assert 180 <= len(desc) <= 250 and 120 <= len(first) <= 180 and 15 <= len(h1) <= 45
assert not re.search(r'фирменн|официальн|крупнейш|2000 отзыв|в наличии|2&#8211;7|FAQPage|aggregateRating',html+schema,re.I)
assert html.count('class="gm-faq-question"') == 5
assert len(objects[0]['sameAs']) == 9
for filename,content in [(name+'.html',html),(name+'-jsonld.html',schema)]:
    target = dst/filename
    assert target.resolve().is_relative_to(root) and not target.exists()
    (src/filename).write_text(content,encoding='utf-8')
    (src/filename).rename(target)
tracker = root/'_DEV/positioning-rebrand-task.md'
t = tracker.read_text(encoding='utf-8-sig')
t = t.replace('## Актуальный порядок работы — 2026-09-22', '## Актуальный порядок работы — 2026-09-24\n\n- Уточнение пользователя 2026-09-24: далее мету делать через `.cursor/skills/seo-meta-builder-multibrand/SKILL.md`, с Wordstat и поисковым анализом; это заменяет прежний упрощённый подход к метаданным.')
t = re.sub(r'- Последняя обработанная категория:.*', '- Последняя обработанная категория: `xiaomi-watch-s5` — HTML + JSON-LD в «Новые измененные», ожидают проверки пользователя.',t,count=1)
t = re.sub(r'- Следующая пара по графику:.*','- Следующая пара по графику: `xiaomi-smart-band-10-pro` (№83–84).',t,count=1)
for line in t.splitlines():
    if '| Категории/Готовые/xiaomi-watch-s5' in line:
        t = t.replace(line,line.replace('⬜','🔶 в «Новые измененные», ожидает проверки'))
t += f'\n### Мета Xiaomi Watch S5 — 2026-09-24\n\nH1: {h1}\n\nTitle: {title}\n\nDescription: {desc}\n\nWebPage.description: {first}\n\nГарантия 1 год сохранена из исходных файлов; отдельно через сайт не подтверждена. Исследование: `_DEV/xiaomi-watch-s5-analysis.md`.\n'
tracker.write_text(t,encoding='utf-8')
print(json.dumps({'H1':len(h1),'title':len(title),'description':len(desc),'WebPage.description':len(first),'FAQ':5,'sameAs':9,'JSON-LD':'valid'},ensure_ascii=False))
