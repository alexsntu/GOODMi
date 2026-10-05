from pathlib import Path
import re,json
from html.parser import HTMLParser
root=Path(__file__).resolve().parent.parent
src=root/'Категории/Готовые'; dst=root/'Категории/Новые измененные'
hp=src/'smartfony-redmi-note-category.html';jp=src/'smartfony-redmi-note-jsonld.html'
m=json.loads((root/'_DEV/smartfony-redmi-note-meta.json').read_text(encoding='utf-8'))
old=hp.read_text(encoding='utf-8');h=old.replace(' itemscope itemtype="https://schema.org/WebPage"','')
h=re.sub(r'(<p class="gm-intro-text">).*?(</p>)',lambda x:x[1]+m['web']+' GOODMi — магазин электроники и гаджетов в Крыму с 2016 года. В серии есть разные модели 4G и 5G: процессор, камеры, аккумулятор и зарядка зависят от версии. Наличие и комплектацию проверяйте в карточке товара. Доступны доставка СДЭК и 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте.'+x[2],h,count=1,flags=re.S)
cards=[
'Snapdragon 7s Gen 4, основная камера 200 Мп с OIS, аккумулятор 6500 мАч и зарядка до 100 Вт. AMOLED-экран до 120 Гц.',
'Dimensity 7400-Ultra, основная камера 200 Мп с OIS, аккумулятор 6580 мАч и зарядка до 45 Вт. AMOLED-экран до 120 Гц.',
'Версия 4G с процессором Helio G200-Ultra. Сравнивайте с Pro 5G по полному набору характеристик: различия не ограничиваются поддержкой сетей.',
'Базовая модель 4G на Helio G100-Ultra. Перед выбором сравните камеру, память и зарядку с версиями Pro.',
'Модель поколения Note 14 для сравнения с Note 15 Pro+. Проверьте региональную версию, камеры, зарядку и текущую цену на странице модели.',
'Модель поколения Note 14 с поддержкой 5G. Сравните процессор, память и камеры с Note 15 Pro 5G; работа 5G зависит от оператора и покрытия.'
]
i=iter(cards)
h,n=re.subn(r'(<article class="gm-service-card".*?<p>).*?(</p>)',lambda x:x[1]+next(i)+x[2],h,flags=re.S);assert n==6
rep={
'Актуальные смартфоны Redmi Note: AMOLED 120 Гц, камеры с OIS до 200 Мп, поддержка 5G и быстрая зарядка.':'Подборка моделей Redmi Note 14 и 15. Характеристики ниже относятся к глобальным версиям; наличие, комплектацию и поддерживаемые сети уточняйте для выбранного товара.',
'<strong>Гарантия 1 год</strong> на новые смартфоны':'<strong>Гарантия 1 год</strong> по условиям товара',
'<strong>Трейд-ин</strong> сдайте старый смартфон':'<strong>Трейд-ин</strong> после оценки устройства',
'Официальная гарантия 1 год':'Гарантия 1 год',
'на все смартфоны серии Redmi Note с подтверждённым происхождением и поддержкой в фирменном магазине GOODMi.':'по условиям выбранного товара. Срок и порядок обслуживания уточняйте в карточке и гарантийных документах.',
'заказывайте Redmi Note онлайн и получайте в любом городе, или заберите в одном из 6 магазинов Крыма &#8212; в Севастополе, Симферополе и Ялте.':'доступность, стоимость и сроки уточняются при оформлении. Самовывоз — 6 точек выдачи в Крыму: Севастополь, Симферополь и Ялта.',
'сдайте старый смартфон в зачёт стоимости Redmi Note и получайте бонусы на следующие покупки.':'приём устройства и сумма зачёта зависят от оценки специалиста. Начисление и использование бонусов — по правилам программы.',
'Кредит и рассрочка':'Покупка в кредит',
'удобные варианты оплаты для любой модели серии Redmi Note без необходимости откладывать покупку.':'доступность и условия уточняются при оформлении; решение принимает банк.',
'<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.':'<strong>Отзывы покупателей на Яндекс Картах</strong> &#8212; GOODMi работает с 2016 года. Посмотрите актуальные оценки и опыт покупателей в профиле магазина.'
}
for a,b in rep.items():assert a in h,a;h=h.replace(a,b)
qa=[
('Чем Redmi Note 15 Pro+ 5G отличается от Note 15 Pro 5G?','В глобальной версии Pro+ 5G установлен Snapdragon 7s Gen 4, аккумулятор 6500 мАч и зарядка до 100 Вт. У Pro 5G — Dimensity 7400-Ultra, 6580 мАч и зарядка до 45 Вт. Оба оснащены основной камерой 200 Мп с OIS и AMOLED-экраном 6,83 дюйма до 120 Гц. Поэтому выбирать следует по процессору, зарядке, памяти и цене, а не только по разрешению камеры.'),
('Чем версии Redmi Note 4G отличаются от 5G?','Это разные модели, а не просто настройка сети. Например, у Note 15 Pro 4G процессор Helio G200-Ultra, а у Note 15 Pro 5G — Dimensity 7400-Ultra. Могут отличаться камеры, экран, аккумулятор и защита корпуса. Проверяйте полное название и региональную версию; доступность 5G зависит от оператора и покрытия.'),
('Как выбрать память Redmi Note: 256 или 512 ГБ?','256 ГБ можно рассмотреть для приложений, фотографий и умеренного объёма видео. 512 ГБ дают больше места для крупных игр и офлайн-файлов. Сравнивайте также оперативную память и конфигурации конкретной модели. Часть накопителя занята системой и предустановленными приложениями.'),
('Какая гарантия на Redmi Note в GOODMi?','Для представленных смартфонов указан срок гарантии 1 год по условиям выбранного товара. Проверьте срок и порядок обслуживания в карточке и документах к покупке. Детали можно уточнить по телефону 8 (800) 250-17-00 или на странице условий гарантии GOODMi.'),
('Как получить заказ и воспользоваться трейд-ин?','Оформите заказ на goodmi.ru, проверив наличие выбранной конфигурации. Доступны доставка СДЭК и 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте. Стоимость и срок доставки уточняются при оформлении. Приём старого смартфона и сумма зачёта определяются после оценки специалистом.')
]
faq='  <section class="gm-faq" aria-labelledby="faq-heading">\n    <h3 id="faq-heading" class="gm-section-title">Часто задаваемые вопросы о Redmi Note</h3>\n'
for q,a in qa:faq+=f'    <div class="gm-faq-item"><p class="gm-faq-question">{q}</p><div class="gm-faq-answer"><p>{a}</p></div></div>\n'
faq+='  </section>\n'
h,n=re.subn(r'      <section class="gm-faq".*?</section>\n','',h,count=1,flags=re.S);assert n==1
h=h.replace('</div><!-- /.gm-block (SEO-блок: всегда виден) -->',faq+'</div><!-- /.gm-block (SEO-блок: всегда виден) -->')
objects=[json.loads(x) for x in re.findall(r'<script[^>]*>(.*?)</script>',jp.read_text(encoding='utf-8'),re.S)]
b,items,crumb=objects
b['alternateName']=['GOODMi — магазин электроники и гаджетов']
b['description']='GOODMi — магазин электроники и гаджетов в Крыму с 2016 года. Доступны 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте. Доставка СДЭК по России; условия уточняются при оформлении.'
b.pop('aggregateRating',None);b['priceRange']='₽₽₽'
b['sameAs']=[f'https://yandex.ru/maps/org/goodmi/{x}/' for x in ['81345582117','219323553091','41033084263','95861137013','183196216973','63307304488']]+['https://vk.ru/reviews-126411469','https://www.avito.ru/brands/i155162702/all?sellerId=557ad28f61641d9114ad5ca6531fa735','https://otzovik.com/reviews/mi92_ru-internet-magazin_tehniki_xiaomi']
items['name']='Подборка смартфонов Redmi Note в GOODMi'
items['description']='Шесть моделей поколений Redmi Note 14 и 15 в каталоге GOODMi. Наличие, характеристики и комплектацию уточняйте на странице выбранной модели.'
crumb['itemListElement'][1]['name']='Смартфоны';crumb['@id']=items['url']+'#breadcrumb'
page={'@context':'https://schema.org','@type':'WebPage','@id':items['url']+'#webpage','url':items['url'],'name':m['h1'],'description':m['web'],'inLanguage':'ru-RU','publisher':{'@id':b['@id']},'breadcrumb':{'@id':crumb['@id']},'speakable':{'@type':'SpeakableSpecification','cssSelector':['#redmi-note-heading','.gm-intro-text','#faq-heading','.gm-faq-answer']}}
j='\n\n'.join('<script type="application/ld+json">\n'+json.dumps(x,ensure_ascii=False,indent=2)+'\n</script>' for x in [b,page,items,crumb])+'\n'
assert re.findall(r'<script>(.*?)</script>',old,re.S)==re.findall(r'<script>(.*?)</script>',h,re.S)
assert not re.search(r'фирменн|официальн|авторизованн|рассроч|крупнейш|2000 отзыв|FAQPage|@graph|aggregateRating|120 Вт|67 Вт|waterfall',h+j,re.I)
assert h.count('class="gm-service-card"')==6 and h.count('class="gm-faq-item"')==5
assert h.index('class="gm-faq"')<h.index('class="gm-collapse-wrapper"')
assert 15<=len(m['h1'])<=45 and 180<=len(m['description'])<=250 and 120<=len(m['web'])<=180
assert m['description'].split('. ')[0]+'.'==m['web']
class Check(HTMLParser):
    def __init__(self):super().__init__();self.stack=[];self.ids=[]
    def handle_starttag(self,t,attrs):
        if t not in ['br','hr','img','input','meta','link','source','wbr','area','base','col','embed','param','track']:self.stack.append(t)
        self.ids += [v for k,v in attrs if k=='id']
    def handle_endtag(self,t):
        assert self.stack and self.stack[-1]==t,(t,self.stack)
        self.stack.pop()
c=Check();c.feed(h);assert not c.stack and len(c.ids)==len(set(c.ids))
for p in [hp,jp]:assert not (dst/p.name).exists()
hp.write_text(h,encoding='utf-8');jp.write_text(j,encoding='utf-8')
for p in [hp,jp]:p.rename(dst/p.name)
print({k:len(v) for k,v in m.items()})
print('OK: pair moved, HTML/JSON checked, 6 cards, 5 visible FAQ, scripts unchanged')
