from pathlib import Path
import json,re
root=Path(__file__).resolve().parents[1]
src=root/'Категории/Готовые'
dst=root/'Категории/Новые измененные'
slug='xiaomi-smart-band-10-pro'
html=(src/(slug+'.html')).read_text(encoding='utf-8-sig')
def para(prefix,value):
    global html
    html,n=re.subn(r'<p>'+re.escape(prefix)+r'.*?</p>',lambda m:'<p>'+value+'</p>',html,flags=re.S)
    assert n==1,(prefix,n)
para('Xiaomi Smart Band 10 Pro (Сяоми)', 'Фитнес-браслеты Xiaomi Smart Band 10 Pro в GOODMi оснащены AMOLED-дисплеем 1,74 дюйма и независимой спутниковой навигацией GNSS. Автономность &#8212; до 21 дня при лёгком использовании. Гарантия 1 год. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта.')
para('Доступны три варианта:', 'GOODMi &#8212; магазин электроники и гаджетов в Крыму с 2016 года. Доступные цвета, комплектацию и наличие Xiaomi Smart Band 10 Pro смотрите в карточках товаров. Условия трейд-ин, кредита и бонусной программы уточняйте перед покупкой.')
html=html.replace('Что важно учесть при выборе фитнес-браслета Сяоми (Xiaomi)?','Что важно учесть при выборе Xiaomi Smart Band 10 Pro?')
para('При выборе фитнес-браслета Xiaomi', 'Сравните размер и удобство посадки, совместимость со смартфоном, региональную версию и нужные функции. У Xiaomi Smart Band 10 Pro AMOLED-дисплей 1,74 дюйма и независимая навигация GNSS для записи маршрутов тренировок. Комплектацию и совместимость аксессуаров проверяйте для выбранной версии.')
html=html.replace('Чем Xiaomi Smart Band 10 Pro отличается от Xiaomi Smart Band 10?', 'На сколько хватает заряда Xiaomi Smart Band 10 Pro?')
para('Smart Band 10 Pro оснащён', 'Производитель указывает до 21 дня работы при лёгком использовании, до 15 дней при обычном использовании и до 8 дней с постоянно включённым экраном AOD. Реальная автономность зависит от настроек экрана, мониторинга показателей, тренировок и использования GNSS.')
para('Оформите заказ на goodmi.ru:', 'Выберите вариант Xiaomi Smart Band 10 Pro в каталоге и оформите заказ на goodmi.ru либо позвоните 8 (800) 250-17-00. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму. Стоимость и сроки уточняются при оформлении заказа; режим работы выбранной точки смотрите на странице контактов.')
para('На Xiaomi Smart Band 10 Pro в GOODMi действует', 'На Xiaomi Smart Band 10 Pro действует гарантия 1 год. Условия обслуживания указаны в карточке товара и документах к покупке. По гарантийным вопросам обратитесь в GOODMi по телефону 8 (800) 250-17-00 или через чат на сайте.')
html=html.replace('Как сдать старый смартфон по трейд-ин в GOODMi?', 'Как воспользоваться трейд-ин при покупке Xiaomi Smart Band 10 Pro?')
para('Принесите старое устройство', 'Возможность участия устройства в трейд-ин зависит от модели и состояния. Перед покупкой браслета уточните условия оценки у консультанта или на странице <a href="https://goodmi.ru/treyd-in-goodmi/">программы GOODMi</a>.')
html=html.replace('<strong>Гарантия 1 год</strong> на новую технику','<strong>Гарантия 1 год</strong> на Smart Band 10 Pro')
html=html.replace('<strong>Трейд-ин</strong> сдайте старое','<strong>Трейд-ин</strong> по условиям программы')
html=html.replace('<strong>Официальная гарантия 1 год</strong> &#8212; все браслеты Xiaomi в GOODMi оригинальные, с полноценной гарантией на корпус, дисплей и аккумулятор.', '<strong>Гарантия 1 год</strong> на Xiaomi Smart Band 10 Pro &#8212; условия обслуживания указаны в карточке товара и документах к покупке.')
html=html.replace('&#8212; оформите заказ на goodmi.ru и получите Xiaomi Smart Band 10 Pro в любом городе страны за 2&#8211;7 дней.', '&#8212; стоимость и сроки уточняются при оформлении заказа. Самовывоз доступен в 6 точках выдачи в Крыму.')
html=html.replace('&#8212; сдайте старое устройство и получите скидку на покупку нового, или копите баллы с каждым заказом.', '&#8212; уточните возможность участия вашего устройства в трейд-ин и правила начисления бонусов перед покупкой.')
html=html.replace('<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.', '<strong>Отзывы о GOODMi на Яндекс Картах</strong> &#8212; узнайте, что покупатели пишут о магазине электроники и гаджетов в Крыму.')
h1='Фитнес-браслеты Xiaomi Smart Band 10 Pro'
title='Купить Xiaomi Band 10 Pro в Севастополе, Крыму | GOODMi'
first='Фитнес-браслеты Xiaomi Smart Band 10 Pro в GOODMi в Севастополе и Крыму — гарантия 1 год, AMOLED-дисплей 1,74″, заказывайте онлайн.'
desc=first+' До 21 дня работы при лёгком использовании. GOODMi — магазин электроники и гаджетов в Крыму с 2016 года.'
old=[json.loads(s) for s in re.findall(r'<script[^>]*>(.*?)</script>',(src/(slug+'-jsonld.html')).read_text(encoding='utf-8-sig'),re.S)]
ref=(root/'Категории/Перезалитые/planshety-jsonld.html').read_text(encoding='utf-8-sig')
business=json.loads(re.findall(r'<script[^>]*>(.*?)</script>',ref,re.S)[0])
business['alternateName']=['Магазин электроники GOODMi']
page={'@context':'https://schema.org','@type':'WebPage','name':h1,'description':first,'url':old[0]['url'],'breadcrumb':{k:v for k,v in old[1].items() if k!='@context'},'speakable':{'@type':'SpeakableSpecification','cssSelector':['.gm-intro-text','.gm-faq']}}
schema='\n\n'.join('<script type="application/ld+json">\n'+json.dumps(o,ensure_ascii=False,indent=2)+'\n</script>' for o in [business,page])+'\n'
assert not re.search(r'фирменн|официальн|крупнейш|2000 отзыв|в наличии|2&#8211;7|FAQPage|aggregateRating|4 магазина',html+schema,re.I)
assert 15<=len(h1)<=45 and 180<=len(desc)<=250 and 120<=len(first)<=180
assert html.count('class="gm-faq-question"')==5 and len(business['sameAs'])==9
for filename,content in [(slug+'.html',html),(slug+'-jsonld.html',schema)]:
    target=dst/filename
    assert target.resolve().is_relative_to(root) and not target.exists()
    (src/filename).write_text(content,encoding='utf-8')
    (src/filename).rename(target)
tracker=root/'_DEV/positioning-rebrand-task.md'
t=tracker.read_text(encoding='utf-8-sig')
t=re.sub(r'- Последняя обработанная категория:.*','- Последняя обработанная категория: `xiaomi-smart-band-10-pro` — HTML + JSON-LD в «Новые измененные», ожидают проверки пользователя.',t,count=1)
t=re.sub(r'- Следующая пара по графику:.*','- Следующая пара по графику: `xiaomi-17t-series` (№85–86).',t,count=1)
for line in t.splitlines():
    if '| Категории/Готовые/xiaomi-smart-band-10-pro' in line:
        t=t.replace(line,line.replace('⬜','🔶 в «Новые измененные», ожидает проверки'))
t+=f'\n### Мета Xiaomi Smart Band 10 Pro — 2026-09-24\n\nH1: {h1}\n\nTitle: {title}\n\nDescription: {desc}\n\nWebPage.description: {first}\n\nИсследование: `_DEV/xiaomi-smart-band-10-pro-analysis.md`. Гарантия 1 год сохранена из исходных файлов.\n'
tracker.write_text(t,encoding='utf-8')
print(json.dumps({'H1':len(h1),'title':len(title),'description':len(desc),'WebPage.description':len(first),'FAQ':5,'sameAs':9,'JSON-LD':'valid'},ensure_ascii=False))
