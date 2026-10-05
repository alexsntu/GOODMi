from pathlib import Path
import json,re
root=Path(__file__).resolve().parents[1]; src=root/'Категории/Готовые'; dst=root/'Категории/Новые измененные'; slug='redmi-note-15-pro-plus-5g'
html=(src/(slug+'.html')).read_text(encoding='utf-8-sig')
def para(prefix,value):
    global html
    html,n=re.subn(r'<p>'+re.escape(prefix)+r'.*?</p>',lambda m:'<p>'+value+'</p>',html,flags=re.S)
    assert n==1,(prefix,n)
html=html.replace('Redmi Note 15 Pro Plus 5G &#8212; флагман Redmi с камерой 200 МП','Redmi Note 15 Pro Plus 5G с камерой 200 МП')
para('Купить Redmi Note 15 Pro Plus 5G', 'Смартфоны Redmi Note 15 Pro Plus 5G в GOODMi &#8212; магазине электроники и гаджетов в Крыму. Глобальная модель оснащена процессором Snapdragon 7s Gen 4, камерой 200 МП с оптической стабилизацией OIS, AMOLED-экраном 6,83 дюйма с разрешением 1.5K и частотой до 120 Гц. Аккумулятор 6500 мА&#183;ч поддерживает зарядку до 100 Вт. Гарантия 1 год. В каталоге представлены варианты 8/256 и 12/512 ГБ; актуальные цвета и наличие смотрите в карточках товаров.')
para('Самовывоз в 6 точках Крыма', 'Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта. Стоимость и сроки доставки уточняются при оформлении заказа. Условия кредита и участия старого смартфона в трейд-ин смотрите на соответствующих страницах GOODMi.')
para('Redmi Note 15 Pro Plus 5G &#8212;', 'Перед покупкой проверьте региональную версию, объём памяти и комплектацию. Глобальный Redmi Note 15 Pro Plus 5G оснащён Snapdragon 7s Gen 4, камерой 200 МП с OIS и аккумулятором 6500 мА&#183;ч с зарядкой до 100 Вт. Экран AMOLED 6,83 дюйма поддерживает частоту до 120 Гц. Поддержка отдельных функций и комплект поставки могут различаться по регионам.')
para('Главные отличия Redmi Note 15 Pro Plus', 'У глобального Redmi Note 15 Pro+ 5G процессор Snapdragon 7s Gen 4, аккумулятор 6500 мА&#183;ч и зарядка до 100 Вт. У Redmi Note 15 Pro 5G &#8212; Dimensity 7400-Ultra, аккумулятор 6580 мА&#183;ч и зарядка до 45 Вт. Обе модели имеют основную камеру 200 МП с OIS и AMOLED-экран 6,83 дюйма до 120 Гц. Сравнивайте версии для одного региона.')
para('Оформите заказ на goodmi.ru', 'Оформите заказ на goodmi.ru или позвоните 8 (800) 250-17-00. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму. Стоимость и сроки доставки уточняются при оформлении заказа; адрес и часы работы выбранной точки смотрите на странице контактов.')
para('На Redmi Note 15 Pro Plus 5G в GOODMi действует', 'На Redmi Note 15 Pro Plus 5G действует гарантия 1 год. Условия обслуживания указаны в карточке товара и документах к покупке. По вопросам гарантии обратитесь в GOODMi по телефону 8 (800) 250-17-00 или через чат на сайте.')
para('Принесите старый смартфон', 'Возможность участия смартфона в трейд-ин зависит от модели и состояния. Перед покупкой уточните условия оценки и подготовки устройства у консультанта или на странице <a href="https://goodmi.ru/treyd-in-goodmi/">программы GOODMi</a>.')
html=html.replace('<strong>Гарантия 1 год</strong> на новую технику','<strong>Гарантия 1 год</strong> на Redmi Note 15 Pro Plus 5G')
html=html.replace('<strong>Трейд-ин</strong> &#8212; сдайте старое','<strong>Трейд-ин</strong> по условиям программы')
html=html.replace('Официальная гарантия 1 год','Гарантия 1 год').replace('&#8212; диагностика и замена устройства при производственном дефекте.', '&#8212; условия обслуживания указаны в карточке товара и документах к покупке.')
html=html.replace('Самовывоз в 6 точках Крыма ежедневно с 10:00 до 21:00.', 'Самовывоз в 6 точках выдачи в Крыму. Стоимость и сроки доставки уточняются при оформлении заказа.')
html=html.replace('&#8212; сдайте старый смартфон в зачёт стоимости, накапливайте баллы на следующие покупки.', '&#8212; уточните условия участия вашего смартфона и правила начисления бонусов перед покупкой.')
html=html.replace('Кредит и рассрочка','Покупка в кредит')
html=html.replace('<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.', '<strong>Отзывы о GOODMi на Яндекс Картах</strong> &#8212; узнайте, что покупатели пишут о магазине электроники и гаджетов в Крыму.')
h1='Смартфоны Redmi Note 15 Pro Plus 5G'
title='Купить Redmi Note 15 Pro+ 5G в Севастополе, Крыму | GOODMi'
first='Смартфоны Redmi Note 15 Pro+ 5G в GOODMi в Севастополе и Крыму — гарантия 1 год, камера 200 МП с OIS, заказывайте онлайн.'
desc=first+' Snapdragon 7s Gen 4, аккумулятор 6500 мА·ч, зарядка 100 Вт. GOODMi — магазин электроники и гаджетов в Крыму.'
objects=[json.loads(s) for s in re.findall(r'<script[^>]*>(.*?)</script>',(src/(slug+'-jsonld.html')).read_text(encoding='utf-8-sig'),re.S)]
ref=(root/'Категории/Перезалитые/planshety-jsonld.html').read_text(encoding='utf-8-sig')
objects[0]=json.loads(re.findall(r'<script[^>]*>(.*?)</script>',ref,re.S)[0]); objects[0]['alternateName']=['Магазин электроники GOODMi']
objects[1]['name']=h1; objects[1]['description']=first
objects[1]['breadcrumb']['itemListElement'][1]['item']='https://goodmi.ru/smartfonyi/'
schema='\n\n'.join('<script type="application/ld+json">\n'+json.dumps(o,ensure_ascii=False,indent=2)+'\n</script>' for o in objects)+'\n'
assert not re.search(r'фирменн|официальн|крупнейш|рассроч|2000 отзыв|6200|Gen 3|120(?:&nbsp;| )Вт|FAQPage|aggregateRating|4 магазина',html+schema,re.I)
assert 15<=len(h1)<=45 and 180<=len(desc)<=250 and 120<=len(first)<=180
assert html.count('class="gm-faq-question"')==5 and len(objects[0]['sameAs'])==9
for filename,content in [(slug+'.html',html),(slug+'-jsonld.html',schema)]:
    target=dst/filename
    assert target.resolve().is_relative_to(root) and not target.exists()
    (src/filename).write_text(content,encoding='utf-8'); (src/filename).rename(target)
tracker=root/'_DEV/positioning-rebrand-task.md'; t=tracker.read_text(encoding='utf-8-sig')
t=re.sub(r'- Последняя обработанная категория:.*','- Последняя обработанная категория: `redmi-note-15-pro-plus-5g` — HTML + JSON-LD в «Новые измененные», ожидают проверки пользователя.',t,count=1)
t=re.sub(r'- Следующая пара по графику:.*','- Следующая пара по графику: `xiaomi-17t` (№89–90).',t,count=1)
for line in t.splitlines():
    if '| Категории/Готовые/redmi-note-15-pro-plus-5g' in line:
        t=t.replace(line,line.replace('⬜','🔶 в «Новые измененные», ожидает проверки'))
t+=f'\n### Мета Redmi Note 15 Pro Plus 5G — 2026-09-24\n\nH1: {h1}\n\nTitle: {title}\n\nDescription: {desc}\n\nWebPage.description: {first}\n\nWordstat 429, применён SERP-fallback скилла. Исправлены процессор, аккумулятор, зарядка и сравнение. См. `_DEV/redmi-note-15-pro-plus-5g-analysis.md`.\n'
tracker.write_text(t,encoding='utf-8')
print(json.dumps({'H1':len(h1),'title':len(title),'description':len(desc),'WebPage.description':len(first),'FAQ':5,'sameAs':9,'JSON-LD':'valid'},ensure_ascii=False))
