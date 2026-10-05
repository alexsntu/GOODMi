from pathlib import Path
import re,json
root=Path(__file__).resolve().parents[1]; src=root/'Категории/Готовые'; dst=root/'Категории/Новые измененные'
hn='xiaomi-smartfony-category.html'; jn='xiaomi-smartfony-jsonld.html'
h=(src/hn).read_text(encoding='utf-8-sig'); old=h
h=re.sub(r'<p class="gm-intro-text">.*?</p>','<p class="gm-intro-text">В <strong>GOODMi</strong> &#8212; магазине электроники и гаджетов в Крыму &#8212; представлены <strong>смартфоны Xiaomi</strong> линеек 15 и 17, включая модели T, Pro и Ultra. Сравните размеры экрана, камеры, память и возможности зарядки. Гарантия 1 год; условия указаны в карточке товара и документах к покупке. Наличие, региональную версию и комплектацию проверяйте перед заказом. Доступны доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму.</p>',h,flags=re.S)
replacements={
'Купить флагманы Xiaomi в Севастополе &#8212; GOODMi':'Смартфоны Xiaomi в GOODMi',
'Новейшие Xiaomi 17 с камерами Leica и флагманы Xiaomi 15 &#8212; топовые смартфоны Сяоми для тех, кто не идёт на компромисс.':'Выберите модель Сяоми по размерам, камерам и задачам. Характеристики и комплектация зависят от модели и региональной версии.',
'Абсолютный флагман Xiaomi: профессиональная система Leica, керамический корпус и максимальная производительность поколения.':'Модель с экраном 6,9 дюйма, процессором Snapdragon 8 Elite Gen 5 и камерой Leica с телеобъективом 200 Мп.',
'Флагман серии T: Snapdragon, камера Leica и высокая частота дисплея &#8212; оптимальный выбор между топовым Ultra и базовой моделью.':'Dimensity 9500, камеры Leica и экран 6,83 дюйма до 144 Гц. Поддерживает проводную и беспроводную зарядку.',
'Базовый флагман нового поколения: Snapdragon 8 Elite, AMOLED и камера Leica без переплаты за Ultra-версию.':'Snapdragon 8 Elite Gen 5, OLED-экран 6,3 дюйма и камеры Leica. Более компактный вариант по сравнению с Xiaomi 17 Ultra.',
'Доступный флагман T-серии нового поколения: Snapdragon, камера Leica и высокая частота дисплея по оптимальной цене.':'Dimensity 8500-Ultra, камеры Leica и AMOLED-экран 6,59 дюйма до 120 Гц. Сравните с Pro по размеру и возможностям зарядки.',
'Производительный флагман T-серии с акцентом на скорость зарядки, мощный процессор и надёжную работу камеры в любых условиях.':'Модель серии 15T для сравнения с новым поколением. Проверьте характеристики камеры, процессора и зарядки на странице модели.',
'Компактный флагман серии: Snapdragon, Leica и долгая автономность в лаконичном корпусе &#8212; для тех, кто ценит размер.':'Модель линейки Xiaomi 15. Перед покупкой сравните размеры, объём памяти и региональную версию выбранной конфигурации.',
'Официальная гарантия 1 год':'Гарантия 1 год',
'на все флагманские смартфоны Xiaomi серий 15 и 17 с подтверждённым происхождением и поддержкой в фирменном магазине GOODMi.':'условия обслуживания указаны в карточке товара и документах к покупке.',
'заказывайте флагман Xiaomi онлайн и получайте в любом городе, или заберите в одном из 6 магазинов Крыма &#8212; в Севастополе, Симферополе и Ялте.':'стоимость и сроки уточняются при оформлении. Самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта.',
'сдайте старый смартфон в зачёт стоимости нового Xiaomi и получайте бонусы на следующие покупки.':'условия участия устройства и начисления бонусов уточняйте до покупки.',
'Кредит и рассрочка':'Покупка в кредит',
'удобные варианты оплаты для флагманских моделей Xiaomi 15 и 17 серий, включая Ultra.':'доступные программы и условия уточняйте при оформлении заказа.',
'<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.':'<strong>Отзывы о GOODMi на Яндекс Картах</strong> &#8212; узнайте, что покупатели пишут о магазине электроники и гаджетов в Крыму.',
'<strong>Трейд-ин</strong> сдайте старый смартфон':'<strong>Трейд-ин</strong> по условиям программы',
'по всей России</span>':'по России</span>'}
for a,b in replacements.items():
    assert a in h,a
    h=h.replace(a,b)
qa=[
('Как выбрать смартфон Xiaomi?', 'Сравните размер экрана, объём памяти, камеры и зарядку. Xiaomi 17 компактнее 17 Ultra: диагональ 6,3 против 6,9 дюйма. У моделей 17T и 17T Pro другой набор характеристик. Для фото и видео проверьте возможности конкретных объективов, а для игр &#8212; процессор и экран. Региональную версию и комплектацию уточняйте в карточке товара.'),
('Чем Xiaomi 17 Ultra отличается от Xiaomi 17?', 'Обе модели оснащены Snapdragon 8 Elite Gen 5. У 17 Ultra экран 6,9 дюйма, основная камера с сенсором размером 1 дюйм и телеобъектив 200 Мп; у Xiaomi 17 &#8212; экран 6,3 дюйма и телеобъектив 50 Мп. Ultra крупнее и тяжелее. Выбор зависит от задач съёмки и удобного размера устройства.'),
('Как заказать телефон Xiaomi с доставкой?', 'Оформите заказ на goodmi.ru или позвоните 8 (800) 250-17-00. Доступны доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму. Стоимость, сроки и возможность получения выбранного товара уточняются при оформлении.'),
('Какая гарантия на смартфоны Xiaomi в GOODMi?', 'Гарантия 1 год. Условия обслуживания указаны в карточке товара и документах к покупке. По вопросам гарантии обратитесь в GOODMi или ознакомьтесь с <a href="https://goodmi.ru/info/guarantee/">условиями гарантии</a>.'),
('Можно ли воспользоваться трейд-ин при покупке Xiaomi?', 'Возможность участия зависит от модели и состояния старого смартфона. Условия оценки и подготовки устройства уточните у консультанта или на странице <a href="https://goodmi.ru/treyd-in-goodmi/">программы трейд-ин GOODMi</a> до визита.')]
faq='<section class="gm-faq" aria-labelledby="faq-heading"><h3 id="faq-heading" class="gm-section-title">Вопросы о смартфонах Xiaomi</h3>\n'+''.join(f'<div class="gm-faq-item"><p class="gm-faq-question">{q}</p><div class="gm-faq-answer"><p>{a}</p></div></div>\n' for q,a in qa)+'</section>\n'
h,n=re.subn(r'<section class="gm-faq".*?</section>','',h,flags=re.S); assert n==1
h=h.replace('</div><!-- /.gm-block (SEO-блок: всегда виден) -->',faq+'</div><!-- /.gm-block (SEO + FAQ: всегда видимы) -->')
h1='Флагманские смартфоны Xiaomi'; title='Телефоны Xiaomi — купить в Севастополе, Крыму | GOODMi'
first='Флагманские смартфоны Xiaomi в GOODMi в Севастополе и Крыму — гарантия 1 год, линейки Xiaomi 15 и 17, модели T, Pro и Ultra.'
desc=first+' Выбирайте Сяоми по камере, памяти и размеру экрана. GOODMi — магазин электроники и гаджетов в Крыму с 2016 года.'
objects=[json.loads(x) for x in re.findall(r'<script[^>]*>(.*?)</script>',(src/jn).read_text(encoding='utf-8-sig'),re.S)]
ref=(root/'Категории/Перезалитые/planshety-jsonld.html').read_text(encoding='utf-8-sig')
business=json.loads(re.findall(r'<script[^>]*>(.*?)</script>',ref,re.S)[0]); business['alternateName']=['Магазин электроники GOODMi']
items=objects[1]; items['description']='Модели Xiaomi в GOODMi: Xiaomi 17 Ultra, 17T Pro, 17, 17T, 15T Pro и 15.'
crumb=objects[2]; crumb.pop('@context'); crumb['itemListElement'][1]['name']='Смартфоны'
page={'@context':'https://schema.org','@type':'WebPage','name':h1,'description':first,'url':items['url'],'breadcrumb':crumb,'speakable':{'@type':'SpeakableSpecification','cssSelector':['.gm-intro-text','.gm-faq']}}
schema='\n\n'.join('<script type="application/ld+json">\n'+json.dumps(o,ensure_ascii=False,indent=2)+'\n</script>' for o in [business,page,items])+'\n'
assert 120<=len(first)<=180 and 180<=len(desc)<=250
assert not re.search(r'фирменн|официальн|рассроч|крупнейш|2000 отзыв|aggregateRating|FAQPage|керамическ',h+schema,re.I)
assert h.count('class="gm-faq-question"')==5 and h.index('class="gm-faq"')<h.index('class="gm-collapse-wrapper"')
assert re.findall(r'<script>(.*?)</script>',old,re.S)==re.findall(r'<script>(.*?)</script>',h,re.S)
assert len(business['sameAs'])==9
for name,content in [(hn,h),(jn,schema)]:
    target=dst/name; assert not target.exists() and target.resolve().is_relative_to(root)
    (src/name).write_text(content,encoding='utf-8'); (src/name).rename(target)
tpath=root/'_DEV/positioning-rebrand-task.md'; t=tpath.read_text(encoding='utf-8-sig')
t=re.sub(r'- Последняя обработанная категория:.*','- Последняя обработанная категория: `xiaomi-smartfony` — HTML + JSON-LD в «Новые измененные», ожидают проверки пользователя.',t,count=1)
t=re.sub(r'- Следующая пара по графику:.*','- Следующая пара по графику: `poco-category` (№95–96).',t,count=1)
for name in [hn,jn]:
    t=re.sub(r'(\| Категории/Готовые/'+re.escape(name)+r' \| )⬜',r'\1🔶 в «Новые измененные», ожидает проверки',t)
t+='\n### Мета Xiaomi смартфоны — 2026-09-24\n\n'+'\n\n'.join(k+': '+v for k,v in [('H1',h1),('Title',title),('Description',desc),('WebPage.description',first)])+'\n'
tpath.write_text(t,encoding='utf-8')
hp=root/'_DEV/category-rework-handoff.md'; hand=hp.read_text(encoding='utf-8')
hand=re.sub(r'\*\*Следующая пара:.*?\*\*','**Следующая пара: `poco-category` — №95–96 (`poco-category-jsonld.html` + `poco-category.html`).**',hand)
hand+='\n24.09.2026: подготовлена `xiaomi-smartfony`, оба файла в «Новые измененные». Wordstat, исправлены процессоры 17/17T, сравнение Ultra, видимые 5 FAQ. Исследование `_DEV/xiaomi-smartfony-analysis.md`. Перед продолжением сверять папки заново.\n'
hp.write_text(hand,encoding='utf-8')
print(json.dumps({k:{'text':v,'length':len(v)} for k,v in [('H1',h1),('Title',title),('Description',desc),('WebPage.description',first)]},ensure_ascii=False))
