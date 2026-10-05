from pathlib import Path
import json,re
root=Path(__file__).resolve().parents[1]; src=root/'Категории/Готовые'; dst=root/'Категории/Новые измененные'; slug='xiaomi-17t'
html=(src/(slug+'.html')).read_text(encoding='utf-8-sig')
def para(prefix,value):
    global html
    html,n=re.subn(r'<p>'+re.escape(prefix)+r'.*?</p>',lambda m:'<p>'+value+'</p>',html,flags=re.S); assert n==1,(prefix,n)
para('В <strong>GOODMi</strong>', 'Смартфоны <strong>Xiaomi 17T</strong> в GOODMi &#8212; магазине электроники и гаджетов в Крыму. Модель оснащена процессором Dimensity 8500-Ultra, AMOLED-экраном 6,59 дюйма до 120 Гц и камерами Leica. В каталоге представлены конфигурации 12/512 и 12/256 ГБ. Аккумулятор 6500 мА&#183;ч поддерживает проводную зарядку до 67 Вт. Гарантия 1 год; актуальные цвета, наличие и комплектацию смотрите в карточке товара.')
para('Трейд-ин вашего старого смартфона', 'Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта. Стоимость и сроки уточняются при оформлении заказа. Условия кредита и участия старого смартфона в трейд-ин смотрите на соответствующих страницах GOODMi.')
html=html.replace('Купить Xiaomi 17T в Крыму &#8212; GOODMi','Смартфоны Xiaomi 17T в GOODMi')
html=html.replace('на смартфоны Xiaomi</span>','на Xiaomi 17T</span>').replace('<strong>Трейд-ин</strong> сдайте старое','<strong>Трейд-ин</strong> по условиям программы')
html=html.replace('Официальная гарантия 1 год','Гарантия 1 год').replace('Гарантийное обслуживание в сервисном центре GOODMi в Севастополе без очередей и доплат.', 'Условия обслуживания указаны в карточке товара и документах к покупке.')
html=html.replace('Самовывоз в 6 точках Крыма: 4 магазина в Севастополе, Симферополь, Ялта.', 'Самовывоз в 6 точках выдачи в Крыму: Севастополь, Симферополь и Ялта.')
html=html.replace('&#8212; сдайте старый смартфон и получите скидку на Xiaomi 17T. Бонусы начисляются с первой покупки.', '&#8212; уточните условия участия вашего смартфона и правила начисления бонусов перед покупкой.')
html=html.replace('Кредит и рассрочка','Покупка в кредит').replace('Несколько банков-партнёров, удобные условия оформления.', 'Доступные программы и условия уточняйте при оформлении заказа.')
html=html.replace('<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.', '<strong>Отзывы о GOODMi на Яндекс Картах</strong> &#8212; узнайте, что покупатели пишут о магазине электроники и гаджетов в Крыму.')
qa=[
('Как выбрать объём памяти Xiaomi 17T?', 'Для приложений, фото и видео доступны версии 12/256 и 12/512 ГБ. Вариант 512 ГБ даёт больше места для локальных файлов; выбирайте с учётом своего объёма данных. Часть памяти занята системой. Цвет, наличие и комплектацию проверяйте в карточке выбранного смартфона.'),
('Как заказать Xiaomi 17T с доставкой по России?', 'Оформите заказ на goodmi.ru или позвоните 8 (800) 250-17-00. Доступна доставка СДЭК по России и самовывоз в 6 точках выдачи в Крыму. Стоимость и сроки доставки уточняются при оформлении заказа.'),
('Какая гарантия на Xiaomi 17T в GOODMi?', 'На Xiaomi 17T действует гарантия 1 год. Условия обслуживания указаны в карточке товара и документах к покупке. По вопросам гарантии обратитесь в GOODMi или ознакомьтесь с <a href="https://goodmi.ru/info/guarantee/">условиями гарантии</a>.'),
('Как воспользоваться трейд-ин при покупке Сяоми 17Т?', 'Возможность участия смартфона в трейд-ин зависит от модели и состояния. Условия оценки и подготовки устройства уточните у консультанта или на странице <a href="https://goodmi.ru/treyd-in-goodmi/">программы GOODMi</a> до визита в магазин.'),
('Чем Xiaomi 17T отличается от Xiaomi 17T Pro?', 'Xiaomi 17T оснащён Dimensity 8500-Ultra, экраном 6,59 дюйма до 120 Гц, аккумулятором 6500 мА&#183;ч и проводной зарядкой до 67 Вт. У 17T Pro &#8212; Dimensity 9500, экран 6,83 дюйма до 144 Гц, аккумулятор 7000 мА&#183;ч и зарядка до 100 Вт по проводу либо до 50 Вт без провода. Сравните обе модели на <a href="https://goodmi.ru/smartfonyi/xiaomi-17t-series/">странице серии</a>.')]
faq='<section class="gm-faq" aria-labelledby="xiaomi-17t-faq-heading">\n<h3 class="gm-section-title" id="xiaomi-17t-faq-heading">Частые вопросы о Xiaomi 17T</h3>\n'+''.join(f'<div class="gm-faq-item"><p class="gm-faq-question">{q}</p><div class="gm-faq-answer"><p>{a}</p></div></div>\n' for q,a in qa)+'</section>\n'
html,n=re.subn(r'<section class="gm-faq".*?</section>', '',html,flags=re.S); assert n==1
html=html.replace('</div><!-- /.gm-block (SEO-блок: всегда виден) -->',faq+'\n</div><!-- /.gm-block (SEO + FAQ: всегда видимы) -->',1)
html=html.replace('Блок 2: Коллапс — преимущества + FAQ + CTA','Блок 2: Коллапс — преимущества + CTA').replace('интро + trust strip)', 'интро + trust strip + FAQ)')
h1='Смартфоны Xiaomi 17T'; title='Купить Xiaomi 17T в Севастополе, Крыму | GOODMi'
first='Смартфоны Xiaomi 17T в GOODMi в Севастополе и Крыму — гарантия 1 год, варианты 12/512 и 12/256 ГБ, камера Leica и AMOLED 120 Гц.'
desc=first+' Заказывайте Сяоми 17Т онлайн. GOODMi — магазин электроники и гаджетов в Крыму с 2016 года.'
objects=[json.loads(s) for s in re.findall(r'<script[^>]*>(.*?)</script>',(src/(slug+'-jsonld.html')).read_text(encoding='utf-8-sig'),re.S)]
ref=(root/'Категории/Перезалитые/planshety-jsonld.html').read_text(encoding='utf-8-sig'); business=json.loads(re.findall(r'<script[^>]*>(.*?)</script>',ref,re.S)[0]); business['alternateName']=['Магазин электроники GOODMi']
crumb={k:v for k,v in objects[1].items() if k!='@context'}; crumb['itemListElement'][1]['item']='https://goodmi.ru/smartfonyi/'
page={'@context':'https://schema.org','@type':'WebPage','name':h1,'description':first,'url':'https://goodmi.ru/xiaomi-17t/','breadcrumb':crumb,'speakable':{'@type':'SpeakableSpecification','cssSelector':['.gm-intro-text','.gm-faq']}}
schema='\n\n'.join('<script type="application/ld+json">\n'+json.dumps(o,ensure_ascii=False,indent=2)+'\n</script>' for o in [business,page])+'\n'
assert 120<=len(first)<=180 and 180<=len(desc)<=250 and desc.index('Заказывайте')<155
assert not re.search(r'фирменн|официальн|крупнейш|рассроч|без очередей|без доплат|2000 отзыв|6 магазинов|4 магазина|aggregateRating|FAQPage',html+schema,re.I)
assert html.count('class="gm-faq-question"')==5 and html.index('class="gm-faq"')<html.index('class="gm-collapse-wrapper"')
for filename,content in [(slug+'.html',html),(slug+'-jsonld.html',schema)]:
    target=dst/filename; assert target.resolve().is_relative_to(root) and not target.exists()
    (src/filename).write_text(content,encoding='utf-8'); (src/filename).rename(target)
tracker=root/'_DEV/positioning-rebrand-task.md'; t=tracker.read_text(encoding='utf-8-sig')
t=re.sub(r'- Последняя обработанная категория:.*','- Последняя обработанная категория: `xiaomi-17t` — HTML + JSON-LD в «Новые измененные», ожидают проверки пользователя.',t,count=1)
t=re.sub(r'- Следующая пара по графику:.*','- Следующая пара по графику: `xiaomi-17t-pro` (№91–92).',t,count=1)
for line in t.splitlines():
    if re.search(r'\| Категории/Готовые/xiaomi-17t(?:-jsonld)?\.html',line): t=t.replace(line,line.replace('⬜','🔶 в «Новые измененные», ожидает проверки'))
t+=f'\n### Мета Xiaomi 17T — 2026-09-24\n\nH1: {h1}\n\nTitle: {title}\n\nDescription: {desc}\n\nWebPage.description: {first}\n\nWordstat восстановился. Исследование `_DEV/xiaomi-17t-analysis.md`.\n'; tracker.write_text(t,encoding='utf-8')
print(json.dumps({'H1':len(h1),'title':len(title),'description':len(desc),'WebPage.description':len(first),'FAQ':5,'sameAs':9,'JSON-LD':'valid'},ensure_ascii=False))
