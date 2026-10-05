from pathlib import Path
import re,json
from html.parser import HTMLParser
root=Path(__file__).resolve().parent.parent
src=root/'Категории/Готовые'; dst=root/'Категории/Новые измененные'
hp=src/'redmi-category.html'; jp=src/'redmi-category-jsonld.html'
m=json.loads((root/'_DEV/redmi-category-meta.json').read_text(encoding='utf-8'))
old=hp.read_text(encoding='utf-8'); h=old.replace(' itemscope itemtype="https://schema.org/WebPage"','')
h=re.sub(r'(<p class="gm-intro-text">).*?(</p>)',lambda x:x[1]+m['web']+' GOODMi — магазин электроники и гаджетов в Крыму с 2016 года. Ниже — подборка Redmi 15 4G, 15C и A5; полный ассортимент и статус наличия или предзаказа указаны в каталоге. Доступны доставка СДЭК и 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте.'+x[2],h,count=1,flags=re.S)
cards=[
'Snapdragon 685, экран 6,9 дюйма FHD+ до 144 Гц и аккумулятор 7000 мАч. Модель 4G: не путайте её с отдельной версией Redmi 15 5G.',
'Helio G81-Ultra, экран 6,9 дюйма HD+ до 120 Гц и аккумулятор 6000 мАч. Сравните с Redmi 15 4G по чёткости экрана, памяти и цене.',
'UNISOC T7250, экран 6,88 дюйма до 120 Гц и аккумулятор 5200 мАч. Работает на Android 15 Go Edition; в линейке есть версии на 64 и 128 ГБ.'
]
i=iter(cards)
h,n=re.subn(r'(<article class="gm-service-card".*?<p>).*?(</p>)',lambda x:x[1]+next(i)+x[2],h,flags=re.S);assert n==3
replacements={
'Бюджетные смартфоны Xiaomi для повседневных задач: от начального уровня до уверенного среднего класса.':'Сравните три модели для повседневных задач. Характеристики ниже относятся к глобальным версиям; комплектация и доступные конфигурации зависят от товара.',
'<strong>Гарантия 1 год</strong> на новые смартфоны':'<strong>Гарантия 1 год</strong> по условиям товара',
'<strong>Трейд-ин</strong> сдайте старый смартфон':'<strong>Трейд-ин</strong> после оценки устройства',
'Официальная гарантия 1 год':'Гарантия 1 год',
'на все смартфоны серии Redmi с подтверждённым происхождением и поддержкой в фирменном магазине GOODMi.':'по условиям выбранного товара. Срок и порядок обслуживания уточняйте в карточке и гарантийных документах.',
'заказывайте Redmi онлайн и получайте в любом городе, или заберите в одном из 6 магазинов Крыма &#8212; в Севастополе, Симферополе и Ялте.':'доступность, стоимость и сроки уточняются при оформлении. Самовывоз — 6 точек выдачи в Крыму: Севастополь, Симферополь и Ялта.',
'сдайте старый смартфон в зачёт стоимости нового Redmi и получайте бонусы на следующие покупки.':'приём устройства и сумма зачёта зависят от оценки специалиста. Начисление и использование бонусов — по правилам программы.',
'Кредит и рассрочка':'Покупка в кредит',
'удобные варианты оплаты для любой модели серии Redmi, чтобы не откладывать нужную покупку.':'доступность и условия уточняются при оформлении; решение принимает банк.',
'<strong>Более 2000 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2016 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.':'<strong>Отзывы покупателей на Яндекс Картах</strong> &#8212; GOODMi работает с 2016 года. Посмотрите актуальные оценки и опыт покупателей в профиле магазина.'
}
for a,b in replacements.items():assert a in h,a;h=h.replace(a,b)
qa=[
('Чем Redmi 15 4G отличается от Redmi 15C?','У глобального Redmi 15 4G процессор Snapdragon 685, экран FHD+ до 144 Гц и батарея 7000 мАч. У Redmi 15C 4G — Helio G81-Ultra, экран HD+ до 120 Гц и батарея 6000 мАч. Оба поддерживают зарядку до 33 Вт. У Redmi 15 чётче экран и ёмче аккумулятор, но фактическое время работы зависит от нагрузки и настроек.'),
('Для каких задач подойдёт Redmi A5?','Redmi A5 рассчитан на базовые сценарии: звонки, мессенджеры, просмотр видео и простые приложения. Он работает на Android 15 Go Edition и оснащён UNISOC T7250. Сравнивайте требования нужных приложений и объём памяти; для тяжёлых игр стоит рассмотреть более производительные модели.'),
('Как выбрать память Redmi: 128 или 256 ГБ?','128 ГБ можно рассмотреть для базовых приложений и хранения части файлов в облаке. 256 ГБ дают больше места для фото, видео и офлайн-контента. Проверяйте также объём оперативной памяти. Не у всех моделей есть обе версии: Redmi A5 выпускается с накопителем 64 или 128 ГБ.'),
('Какая гарантия на смартфоны Redmi в GOODMi?','Для представленных смартфонов указан срок гарантии 1 год по условиям товара. Проверьте срок и порядок обслуживания в карточке и документах к покупке. Уточнить детали можно по телефону 8 (800) 250-17-00 или на странице условий гарантии GOODMi.'),
('Как получить заказ и воспользоваться трейд-ин?','Оформите заказ на goodmi.ru, проверив наличие или статус предзаказа. Доступны доставка СДЭК и 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте. Стоимость и срок доставки уточняются при оформлении. Приём старого смартфона и сумма зачёта по трейд-ин определяются после оценки специалистом.')
]
faq='  <section class="gm-faq" aria-labelledby="faq-heading">\n    <h3 id="faq-heading" class="gm-section-title">Часто задаваемые вопросы о смартфонах Redmi</h3>\n'
for q,a in qa:faq+=f'    <div class="gm-faq-item"><p class="gm-faq-question">{q}</p><div class="gm-faq-answer"><p>{a}</p></div></div>\n'
faq+='  </section>\n'
h,n=re.subn(r'      <section class="gm-faq".*?</section>\n','',h,count=1,flags=re.S);assert n==1
h=h.replace('</div><!-- /.gm-block (SEO-блок: всегда виден) -->',faq+'</div><!-- /.gm-block (SEO-блок: всегда виден) -->')
objects=[json.loads(x) for x in re.findall(r'<script[^>]*>(.*?)</script>',jp.read_text(encoding='utf-8'),re.S)]
business,items,crumb=objects
business['alternateName']=['GOODMi — магазин электроники и гаджетов']
business['description']='GOODMi — магазин электроники и гаджетов в Крыму с 2016 года. Доступны 6 точек выдачи в Крыму: 4 в Севастополе, по одной в Симферополе и Ялте. Доставка СДЭК по России; условия уточняются при оформлении.'
business.pop('aggregateRating',None);business['priceRange']='₽₽₽'
business['sameAs']=[f'https://yandex.ru/maps/org/goodmi/{x}/' for x in ['81345582117','219323553091','41033084263','95861137013','183196216973','63307304488']]+['https://vk.ru/reviews-126411469','https://www.avito.ru/brands/i155162702/all?sellerId=557ad28f61641d9114ad5ca6531fa735','https://otzovik.com/reviews/mi92_ru-internet-magazin_tehniki_xiaomi']
items['name']='Подборка смартфонов Redmi в GOODMi'
items['description']='Три модели из каталога GOODMi: Redmi 15 4G, Redmi 15C и Redmi A5. Наличие, статус предзаказа и комплектацию уточняйте в карточке товара.'
crumb['itemListElement'][1]['name']='Смартфоны';crumb['@id']=items['url']+'#breadcrumb'
page={'@context':'https://schema.org','@type':'WebPage','@id':items['url']+'#webpage','url':items['url'],'name':m['h1'],'description':m['web'],'inLanguage':'ru-RU','publisher':{'@id':business['@id']},'breadcrumb':{'@id':crumb['@id']},'speakable':{'@type':'SpeakableSpecification','cssSelector':['#redmi-heading','.gm-intro-text','#faq-heading','.gm-faq-answer']}}
j='\n\n'.join('<script type="application/ld+json">\n'+json.dumps(x,ensure_ascii=False,indent=2)+'\n</script>' for x in [business,page,items,crumb])+'\n'
assert re.findall(r'<script>(.*?)</script>',old,re.S)==re.findall(r'<script>(.*?)</script>',h,re.S)
assert not re.search(r'фирменн|официальн|авторизованн|рассроч|крупнейш|2000 отзыв|FAQPage|@graph|aggregateRating',h+j,re.I)
assert h.count('class="gm-service-card"')==3 and h.count('class="gm-faq-item"')==5
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
print('OK: pair moved, HTML/JSON checked, 3 cards, 5 visible FAQ, scripts unchanged')
