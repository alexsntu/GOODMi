from pathlib import Path
import re, json
from html.parser import HTMLParser

root=Path(__file__).resolve().parent.parent
src=root/'Категории/Готовые'
dst=root/'Категории/Новые измененные'
meta=json.loads((root/'_DEV/poco-category-meta.json').read_text(encoding='utf-8'))
hp=src/'poco-category.html'
jp=src/'poco-category-jsonld.html'
original=hp.read_text(encoding='utf-8')
h=original.replace(' itemscope itemtype="https://schema.org/WebPage"','')
h=re.sub(r'(<p class="gm-intro-text">).*?(</p>)',r'\1\n      '+meta['web']+' GOODMi — магазин электроники и гаджетов в Крыму с 2016 года. В каталоге есть версии на 256 и 512 ГБ; наличие, комплектацию и условия гарантии проверяйте в карточке выбранного товара. Доступны доставка СДЭК и 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте.\n    '+r'\2',h,count=1,flags=re.S)
replacements={
'Флагманские серии F и X для гейминга, доступная серия M для повседневных задач и бюджетная C &#8212; POCO на любой запрос.':'Сравните модели серий F, X, M и C по процессору, экрану, камерам и памяти. Характеристики зависят от конкретной модели и её региональной версии.',
'Абсолютный флагман POCO: максимальный чипсет, высокочастотный дисплей и запас мощности для самых требовательных игр и задач.':'Snapdragon 8 Elite Gen 5, AMOLED до 120 Гц и перископический телеобъектив 50 Мп — для игр, видео и съёмки с приближением.',
'Топовая производительность предыдущего поколения серии F: мощный процессор, AMOLED и 5G &#8212; надёжный выбор для геймеров.':'Модель серии F для игр и многозадачности. Сравните её цену, камеры и объём памяти с новым поколением перед покупкой.',
'Флагман серии X: максимальный экран, мощная платформа и поддержка 5G &#8212; для тех, кто хочет лучшее в линейке X.':'Dimensity 9500s, AMOLED 6,83 дюйма и аккумулятор 8500 мАч в глобальной версии. Более крупная альтернатива X8 Pro.',
'Лучшее соотношение цены и производительности в серии X: AMOLED, 5G и мощный чипсет для уверенной работы в любых сценариях.':'Dimensity 8500-Ultra, AMOLED 6,59 дюйма и аккумулятор 6500 мАч в глобальной версии. Есть варианты на 256 и 512 ГБ.',
'Топовая модель серии M: производительность выше среднего, 5G и большой экран для тех, кто выбирает доступный смартфон без компромиссов.':'Snapdragon 7s Gen 4, AMOLED 6,83 дюйма и основная камера 50 Мп с оптической стабилизацией. Для повседневных задач и съёмки.',
'Доступный вход в экосистему POCO: надёжный аппарат для повседневных задач с простым управлением и долгой работой без подзарядки.':'Модель серии C для звонков, мессенджеров и повседневных приложений. Перед покупкой сравните память, экран и комплектацию.',
'<strong>Официальная гарантия 1 год</strong>':'<strong>Гарантия 1 год</strong>',
'на все смартфоны POCO с подтверждённым происхождением и поддержкой в фирменном магазине GOODMi.':'по условиям выбранного товара. Срок и порядок обслуживания уточняйте в карточке и гарантийных документах.',
'заказывайте POCO онлайн и получайте в любом городе, или заберите в одном из 6 магазинов Крыма &#8212; в Севастополе, Симферополе и Ялте.':'заказывайте POCO онлайн; доступность, стоимость и срок доставки уточняются при оформлении. Самовывоз — 6 точек выдачи в Крыму: Севастополь, Симферополь и Ялта.',
'сдайте старый смартфон в зачёт стоимости нового POCO и получайте бонусы на следующие покупки.':'возможность приёма и сумму зачёта старого устройства определяет оценка специалиста. Начисление и использование бонусов — по правилам программы.',
'<strong>Кредит и рассрочка</strong>':'<strong>Покупка в кредит</strong>',
'удобные варианты оплаты для любой модели POCO, в том числе флагманских серий F и X.':'доступность и условия уточняются при оформлении; решение принимает банк.',
'<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.':'<strong>Отзывы покупателей на Яндекс Картах</strong> &#8212; GOODMi работает с 2016 года. Посмотрите актуальные оценки и опыт покупателей в профиле магазина.',
'<strong>Гарантия 1 год</strong> на новые смартфоны':'<strong>Гарантия 1 год</strong> по условиям товара',
'<strong>Трейд-ин</strong> сдайте старый смартфон':'<strong>Трейд-ин</strong> после оценки устройства'
}
for a,b in replacements.items():
    assert a in h,a
    h=h.replace(a,b)
faq=[
('Как выбрать смартфон POCO для игр и повседневных задач?','Для игр сравнивайте процессор, охлаждение и объём памяти конкретных моделей F и X. Для звонков, мессенджеров и видео рассмотрите M и C. Буква серии сама по себе не определяет скорость: учитывайте поколение, цену и характеристики выбранного аппарата.'),
('Чем POCO X8 Pro отличается от X8 Pro Max?','В глобальных версиях X8 Pro оснащён Dimensity 8500-Ultra, экраном 6,59 дюйма и батареей 6500 мАч; X8 Pro Max — Dimensity 9500s, экраном 6,83 дюйма и батареей 8500 мАч. Оба используют AMOLED до 120 Гц. X8 Pro меньше по размерам; Max предлагает более крупный экран и более ёмкий аккумулятор.'),
('Какую память выбрать: 256 или 512 ГБ?','256 ГБ подойдут, если вы не храните большую библиотеку игр и видео на устройстве. 512 ГБ дают больше места для крупных игр, фотографий и офлайн-файлов. Доступная пользователю память меньше заявленной из-за системы и предустановленных приложений.'),
('Какая гарантия на POCO в GOODMi?','Для представленных смартфонов указан срок гарантии 1 год по условиям товара. Перед покупкой проверьте срок и порядок обслуживания в карточке и документах. Уточнить детали можно по телефону 8 (800) 250-17-00.'),
('Как получить заказ и воспользоваться трейд-ин?','Заказ оформляется на goodmi.ru. Доступны доставка СДЭК и 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте. Срок и стоимость доставки уточняются при оформлении. При трейд-ин возможность приёма устройства и сумму зачёта определяет специалист после оценки.')
]
block='  <section class="gm-faq" aria-labelledby="faq-heading">\n    <h3 id="faq-heading" class="gm-section-title">Часто задаваемые вопросы о POCO</h3>\n'
for q,a in faq:
    block+=f'    <div class="gm-faq-item">\n      <p class="gm-faq-question">{q}</p>\n      <div class="gm-faq-answer"><p>{a}</p></div>\n    </div>\n'
block+='  </section>\n'
h,n=re.subn(r'      <section class="gm-faq".*?</section>\n', '',h,count=1,flags=re.S)
assert n==1
h=h.replace('</div><!-- /.gm-block (SEO-блок: всегда виден) -->',block+'</div><!-- /.gm-block (SEO-блок: всегда виден) -->')
objects=[json.loads(x) for x in re.findall(r'<script type="application/ld\+json">\s*(.*?)\s*</script>',jp.read_text(encoding='utf-8'),re.S)]
business,items,crumb=objects
business['alternateName']=['GOODMi — магазин электроники и гаджетов']
business['description']='GOODMi — магазин электроники и гаджетов в Крыму с 2016 года. Доступны 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте. Доставка СДЭК по России; условия уточняются при оформлении.'
business['priceRange']='₽₽₽'
business.pop('aggregateRating',None)
business['sameAs']=[f'https://yandex.ru/maps/org/goodmi/{x}/' for x in ['81345582117','219323553091','41033084263','95861137013','183196216973','63307304488']]+['https://vk.ru/reviews-126411469','https://www.avito.ru/brands/i155162702/all?sellerId=557ad28f61641d9114ad5ca6531fa735','https://otzovik.com/reviews/mi92_ru-internet-magazin_tehniki_xiaomi']
items['description']='Подборка моделей POCO серий F, X, M и C в каталоге GOODMi. Характеристики, комплектации и наличие уточняйте на странице выбранной модели.'
crumb['@id']='https://goodmi.ru/smartfony-poco/#breadcrumb'
crumb['itemListElement'][1]['name']='Смартфоны'
web={'@context':'https://schema.org','@type':'WebPage','@id':'https://goodmi.ru/smartfony-poco/#webpage','url':'https://goodmi.ru/smartfony-poco/','name':meta['h1'],'description':meta['web'],'inLanguage':'ru-RU','publisher':{'@id':business['@id']},'breadcrumb':{'@id':crumb['@id']},'speakable':{'@type':'SpeakableSpecification','cssSelector':['#poco-heading','.gm-intro-text','#faq-heading','.gm-faq-answer']}}
j='\n\n'.join('<script type="application/ld+json">\n'+json.dumps(x,ensure_ascii=False,indent=2)+'\n</script>' for x in [business,web,items,crumb])+'\n'
# Проверки до записи и перемещения.
assert re.findall(r'<script>(.*?)</script>',h,re.S)==re.findall(r'<script>(.*?)</script>',original,re.S)
assert h.count('class="gm-service-card"')==6
assert h.count('class="gm-faq-item"')==5
assert h.index('class="gm-faq"')<h.index('class="gm-collapse-wrapper"')
assert not re.search(r'фирменн|официальн|авторизованн|рассроч|крупнейш|2000 отзыв|FAQPage|@graph',h+j,re.I)
assert 15<=len(meta['h1'])<=45 and 180<=len(meta['description'])<=250 and 120<=len(meta['web'])<=180
assert meta['description'].split('. ')[0]+'.'==meta['web']
class Check(HTMLParser):
    def __init__(self): super().__init__(); self.stack=[]; self.ids=[]
    def handle_starttag(self,t,attrs):
        if t not in ['br','hr','img','input','meta','link','source','wbr','area','base','col','embed','param','track']:
            self.stack.append(t)
        self.ids += [v for k,v in attrs if k=='id']
    def handle_endtag(self,t):
        assert self.stack and self.stack[-1]==t,(t,self.stack)
        self.stack.pop()
check=Check();check.feed(h)
assert not check.stack and len(check.ids)==len(set(check.ids))
for p in [hp,jp]: assert not (dst/p.name).exists()
hp.write_text(h,encoding='utf-8'); jp.write_text(j,encoding='utf-8')
for p in [hp,jp]: p.rename(dst/p.name)
print(json.dumps({k:len(v) for k,v in meta.items()},ensure_ascii=False))
print('OK: JSON, HTML nesting, 6 cards, 5 visible FAQ, 9 sameAs, original scripts preserved; pair moved')
