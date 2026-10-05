from pathlib import Path
import json,re
root=Path(__file__).resolve().parents[1]
src=root/'Категории/Готовые'; dst=root/'Категории/Новые измененные'; slug='xiaomi-17t-series'
html=(src/(slug+'.html')).read_text(encoding='utf-8-sig')
def para(prefix,value):
    global html
    html,n=re.subn(r'<p>'+re.escape(prefix)+r'.*?</p>',lambda m:'<p>'+value+'</p>',html,flags=re.S)
    assert n==1,(prefix,n)
para('Смартфоны Xiaomi 17T (Сяоми 17Т)', 'Смартфоны Xiaomi 17T и Xiaomi 17T Pro в GOODMi &#8212; магазине электроники и гаджетов в Крыму. Сравните две модели с камерами Leica, аккумуляторами 6500&#8211;7000 мА&#183;ч и проводной зарядкой 67&#8211;100 Вт. Гарантия 1 год. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму. Наличие, память и комплектацию смотрите в карточке выбранного смартфона.')
html=html.replace('Смартфоны Xiaomi 17T Series</h2>','Смартфоны Xiaomi 17T и Xiaomi 17T Pro</h2>')
html=html.replace('Два смартфона серии &#8212; суб-флагман 17T и топовый 17T Pro с зарядкой 100W и батареей 7000 мАч.', 'Xiaomi 17T и 17T Pro различаются размером экрана, процессором, ёмкостью аккумулятора и возможностями зарядки. Выберите модель под свои задачи.')
para('Суб-флагман серии:', 'Dimensity 8500-Ultra, экран 6,59 дюйма с частотой до 120 Гц и камеры Leica. Аккумулятор 6500 мА&#183;ч, проводная зарядка до 67 Вт. Более компактная модель серии.')
para('Топ серии:', 'Dimensity 9500, экран 6,83 дюйма с частотой до 144 Гц и камеры Leica. Аккумулятор 7000 мА&#183;ч, проводная зарядка до 100 Вт и беспроводная до 50 Вт. Рамка из алюминиевого сплава.')
html=html.replace('<h3>Все смартфоны Xiaomi</h3>','<h3>Все смартфоны</h3>').replace('aria-label="Смотреть каталог смартфонов Xiaomi в GOODMi"','aria-label="Смотреть каталог смартфонов в GOODMi"')
para('Полный каталог смартфонов Xiaomi', 'Сравните смартфоны разных брендов в каталоге GOODMi. Выбирайте по размеру экрана, памяти, камерам и другим характеристикам.')
html=html.replace('Что важно учесть при выборе смартфона Сяоми 17Т Series?', 'Как выбрать между Xiaomi 17T и Xiaomi 17T Pro?')
para('При выборе между Xiaomi 17T', 'Сравните размеры смартфонов, требуемую производительность и способы зарядки. Xiaomi 17T компактнее: экран 6,59 дюйма и масса 200 г. У Xiaomi 17T Pro экран 6,83 дюйма, масса 219 г, процессор Dimensity 9500 и поддержка беспроводной зарядки до 50 Вт. Проверьте доступную память и комплектацию выбранной версии.')
para('Xiaomi 17T Pro мощнее', 'У Xiaomi 17T процессор Dimensity 8500-Ultra, AMOLED-экран до 120 Гц, аккумулятор 6500 мА&#183;ч и проводная зарядка до 67 Вт. У Xiaomi 17T Pro &#8212; Dimensity 9500, экран до 144 Гц, аккумулятор 7000 мА&#183;ч и зарядка до 100 Вт по проводу либо до 50 Вт без провода. Обе модели оснащены камерами Leica; фактическая скорость зарядки зависит от адаптера и условий использования.')
para('Оформите заказ на goodmi.ru', 'Оформите заказ на goodmi.ru или позвоните 8 (800) 250-17-00. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта. Стоимость, сроки и способы оплаты уточняются при оформлении заказа. Режим работы выбранной точки указан на странице контактов.')
para('На все смартфоны серии Xiaomi 17T', 'На смартфоны Xiaomi 17T и 17T Pro действует гарантия 1 год. Условия обслуживания указаны в карточке товара и документах к покупке. По вопросам гарантии обратитесь в GOODMi по телефону 8 (800) 250-17-00 или ознакомьтесь с <a href="https://goodmi.ru/info/guarantee/">условиями гарантии</a>.')
para('Принесите старый смартфон', 'Возможность участия смартфона в трейд-ин зависит от модели и состояния. Условия оценки и подготовку устройства к передаче уточните у консультанта или на странице <a href="https://goodmi.ru/treyd-in-goodmi/">программы GOODMi</a> до визита в магазин.')
html=html.replace('<strong>Трейд-ин</strong> сдайте старое','<strong>Трейд-ин</strong> по условиям программы')
html=html.replace('Официальная гарантия 1 год','Гарантия 1 год')
html=html.replace('&#8212; на все смартфоны Xiaomi 17T Series. Гарантийное обслуживание в магазинах GOODMi в Севастополе, Симферополе и Ялте.', '&#8212; на Xiaomi 17T и 17T Pro. Условия обслуживания указаны в карточке товара и документах к покупке.')
html=html.replace('&#8212; самовывоз в 6 точках Крыма ежедневно с 10:00 до 21:00: 4 магазина в Севастополе, Симферополь и Ялта.', '&#8212; стоимость и сроки уточняются при оформлении заказа. Самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта.')
html=html.replace('&#8212; обменяйте старый смартфон и получите скидку на Xiaomi 17T или 17T Pro.', '&#8212; уточните условия участия вашего смартфона и правила начисления бонусов перед покупкой.')
html=html.replace('<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.', '<strong>Отзывы о GOODMi на Яндекс Картах</strong> &#8212; узнайте, что покупатели пишут о магазине электроники и гаджетов в Крыму.')
h1='Смартфоны Xiaomi 17T и Xiaomi 17T Pro'
title='Xiaomi 17T и 17T Pro — купить в Севастополе, Крыму | GOODMi'
first='Смартфоны Xiaomi 17T и 17T Pro в GOODMi в Севастополе и Крыму — гарантия 1 год, память от 256 ГБ до 1 ТБ, выбирайте онлайн.'
desc=first+' Камеры Leica, зарядка 67–100 Вт. GOODMi — магазин электроники и гаджетов в Крыму с 2016 года.'
objects=[json.loads(s) for s in re.findall(r'<script[^>]*>(.*?)</script>',(src/(slug+'-jsonld.html')).read_text(encoding='utf-8-sig'),re.S)]
ref=(root/'Категории/Перезалитые/planshety-jsonld.html').read_text(encoding='utf-8-sig')
objects[0]=json.loads(re.findall(r'<script[^>]*>(.*?)</script>',ref,re.S)[0]); objects[0]['alternateName']=['Магазин электроники GOODMi']
objects[1]['name']=h1; objects[1]['description']=first
objects[1]['breadcrumb']['itemListElement'][-1]['name']='Xiaomi 17T и 17T Pro'
schema='\n\n'.join('<script type="application/ld+json">\n'+json.dumps(o,ensure_ascii=False,indent=2)+'\n</script>' for o in objects)+'\n'
assert not re.search(r'фирменн|официальн|крупнейш|2000 отзыв|2&#8211;7|FAQPage|aggregateRating|4 магазина|Подготовка устройства к сдаче не требуется',html+schema,re.I)
assert 15<=len(h1)<=45 and 180<=len(desc)<=250 and 120<=len(first)<=180
assert html.count('class="gm-faq-question"')==5 and len(objects[0]['sameAs'])==9
for filename,content in [(slug+'.html',html),(slug+'-jsonld.html',schema)]:
    target=dst/filename
    assert target.resolve().is_relative_to(root) and not target.exists()
    (src/filename).write_text(content,encoding='utf-8'); (src/filename).rename(target)
tracker=root/'_DEV/positioning-rebrand-task.md'; t=tracker.read_text(encoding='utf-8-sig')
t=re.sub(r'- Последняя обработанная категория:.*','- Последняя обработанная категория: `xiaomi-17t-series` — HTML + JSON-LD в «Новые измененные», ожидают проверки пользователя.',t,count=1)
t=re.sub(r'- Следующая пара по графику:.*','- Следующая пара по графику: `redmi-note-15-pro-plus-5g` (№87–88).',t,count=1)
for line in t.splitlines():
    if '| Категории/Готовые/xiaomi-17t-series' in line:
        t=t.replace(line,line.replace('⬜','🔶 в «Новые измененные», ожидает проверки'))
t+=f'\n### Мета Xiaomi 17T Series — 2026-09-24\n\nH1: {h1}\n\nTitle: {title}\n\nDescription: {desc}\n\nWebPage.description: {first}\n\nЧасть Wordstat недоступна (429); см. `_DEV/xiaomi-17t-series-analysis.md`. Гарантия сохранена из исходника.\n'
tracker.write_text(t,encoding='utf-8')
print(json.dumps({'H1':len(h1),'title':len(title),'description':len(desc),'WebPage.description':len(first),'FAQ':5,'sameAs':9,'JSON-LD':'valid'},ensure_ascii=False))
