# -*- coding: utf-8 -*-
# Собирает предложение по новой структуре категорий GOODMi из снимка дерева tree-2026-10-12.tsv.
# Правки задаются словарями ниже: RENAME (id -> новое название), MOVE (id -> новый родитель), HIDE, NEW, ORDER.
import io
rows=[l.rstrip('\n').split('\t') for l in io.open('tree-2026-10-12.tsv',encoding='utf-8')]
cat={r[0]:{'id':r[0],'par':r[1],'pos':int(r[3]),'name':r[4].strip(),'prod':int(r[5]),'rev':int(r[6]),'url':r[7]} for r in rows}
# число товаров – по всем привязкам (основная и дополнительные категории), иначе категории с товарами только по доп. привязке выглядят пустыми
for l in io.open('products-any-link.tsv',encoding='utf-8'):
    k,n=l.split('\t'); cat[k]['prod']=int(n)
RENAME={'449':'Ноутбуки и компьютерная техника','454':'Телевизоры и видеотехника','480':'Смарт-часы и браслеты','483':'Зарядки и электропитание',
 '484':'Техника для дома','485':'Красота и здоровье','469':'Авто и электротранспорт',
 '1410':'Смартфоны POCO','2172':'Ноутбуки','587':'Роутеры','498':'Периферия и аксессуары','457':'Мониторы','665':'Графические планшеты',
 '455':'Телевизоры','456':'ТВ-приставки','458':'Кронштейны','721':'Пульты','538':'Кабели и аксессуары для ТВ',
 '530':'Смарт-часы','531':'Фитнес-браслеты','542':'Колонки и умные колонки',
 '548':'Повербанки','546':'Сетевые зарядки','550':'Кабели',
 '556':'Ручные и автомобильные пылесосы',
 '2161':'Климатическая техника','585':'Вентиляторы','615':'Освещение','552':'Для дома и быта','584':'Швабры','628':'Аквариумы',
 '2163':'Приготовление напитков','2165':'Кухонные принадлежности','606':'Посуда',
 '644':'Массажёры','643':'Бритвы и эпиляторы','633':'Стайлеры и выпрямители','641':'Фены','642':'Зеркала','713':'Триммеры','635':'Машинки для стрижки','645':'Термометры','717':'Ушные палочки',
 '624':'Сейфы','479':'Электротранспорт','527':'Гироскутеры и моноколёса',
 '488':'Рюкзаки, сумки и аксессуары','662':'Рюкзаки','661':'Зонты','656':'Очки','659':'Чемоданы','489':'Игры и игрушки','486':'Туризм и отдых','487':'Спорт'}
NEW={'N1':{'name':'Отдых и хобби','par':'0'},'N2':{'name':'Фото и экшн-камеры','par':'N1'}}
MOVE={'553':'0','457':'449','665':'449','543':'454','586':'483','577':'485','479':'469',
 '488':'N1','489':'N1','486':'N1','487':'N1','728':'N2','534':'N2','1921':'N2','597':'2165','606':'2165','601':'2165','595':'2165'}
HIDE={'1919':'все 4 товара выключены'}
ROOT_ORDER=['412','448','449','454','480','482','483','553','484','588','485','622','469','N1','460','701','490']
for k,v in NEW.items(): cat[k]={'id':k,'par':v['par'],'pos':0,'name':v['name'],'prod':0,'rev':0,'url':'(новая)','new':True}
old_par={k:c['par'] for k,c in cat.items()}
for k,p in MOVE.items(): cat[k]['par']=p
kids={}
for k,c in cat.items():
    if k in HIDE: continue
    kids.setdefault(c['par'],[]).append(k)
def total(k,f):
    return cat[k][f]+sum(total(x,f) for x in kids.get(k,[]) if (x in MOVE or cat[x].get('new'))) if cat[k].get('new') else cat[k][f]
out=[]; stat={'rename':0,'move':0,'hide':len(HIDE),'new':len(NEW)}
def line(k,depth):
    c=cat[k]; marks=[]
    name=RENAME.get(k,c['name'])
    if c.get('new'): marks.append('НОВАЯ')
    elif k in RENAME and RENAME[k]!=c['name']: marks.append('было: «%s»'%c['name']); stat['rename']+=1
    if k in MOVE:
        src=old_par[k]; marks.append('перенос из «%s»'%(cat[src]['name'] if src in cat else 'корня')) if src!='0' or True else None; stat['move']+=1
    prod=c['prod']; rev=c['rev']
    if c.get('new'):
        prod=sum(cat[x]['prod'] for x in kids.get(k,[])); rev=sum(cat[x]['rev'] for x in kids.get(k,[]))
        if k=='N1':
            prod=sum((cat[x]['prod'] if not cat[x].get('new') else sum(cat[y]['prod'] for y in kids.get(x,[]))) for x in kids.get(k,[]))
            rev=sum((cat[x]['rev'] if not cat[x].get('new') else sum(cat[y]['rev'] for y in kids.get(x,[]))) for x in kids.get(k,[]))
    out.append('%s- %s%s — %d тов., %d тыс. ₽%s'%('  '*depth, '**%s**'%name if depth==0 else name, '' , prod, rev, (' · _'+'; '.join(marks)+'_') if marks else ''))
def walk(k,depth):
    line(k,depth)
    ch=kids.get(k,[])
    ch=sorted(ch,key=lambda x:(-cat[x]['rev'] if depth<=1 else cat[x]['pos'], cat[x]['pos'])) if depth==0 else sorted(ch,key=lambda x:cat[x]['pos'])
    for x in ch: walk(x,depth+1)
for r in ROOT_ORDER: walk(r,0); out.append('')
left=[k for k in kids.get('0',[]) if k not in ROOT_ORDER]
head=['# Новая структура категорий GOODMi – предложение от 2026-10-12','',
 'Снимок дерева: `tree-2026-10-12.tsv` (220 активных категорий). Цифры – активные товары и выручка сайта за 365 дней, тыс. ₽.',
 'Адреса страниц, заголовки H1 и мета-теги не меняются: меняются названия в дереве, порядок и родитель.',
 'Подразделы второго уровня внутри раздела стоят в порядке выручки.','',
 'Итого: переименований %d, переносов %d, новых категорий %d, скрыть %d.'%(stat['rename'],stat['move'],stat['new'],stat['hide']),'']
tail=['## Скрыть','']+['- %s (%s) – %s'%(cat[k]['name'],k,v) for k,v in HIDE.items()]
if left: tail+=['','## Корни вне списка (проверить)','']+['- %s (%s)'%(cat[k]['name'],k) for k in left]
io.open('novaya-struktura-kategoriy.md','w',encoding='utf-8').write('\n'.join(head+out+tail)+'\n')
print('\n'.join(head[-2:-1])); print('строк:',len(out),'| вне списка:',[cat[k]['name'] for k in left])
