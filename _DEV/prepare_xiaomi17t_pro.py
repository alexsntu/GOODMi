from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]
s=(root/'_DEV/update_xiaomi17t_rebrand.py').read_text(encoding='utf-8')
s=s.replace('xiaomi-17t','xiaomi-17t-pro').replace('Xiaomi 17T','Xiaomi 17T Pro').replace('Сяоми 17Т','Сяоми 17Т Про')
s=s.replace('12/256','12/1 ТБ').replace('12/512 и 12/1 ТБ ГБ','12/512 ГБ и 12/1 ТБ').replace('12/1 ТБ и 12/512 ГБ','12/512 ГБ и 12/1 ТБ')
s=s.replace('Dimensity 8500-Ultra','Dimensity 9500').replace('6,59','6,83').replace('120 Гц','144 Гц').replace('6500','7000').replace('до 67 Вт','до 100 Вт и беспроводную до 50 Вт')
s=s.replace('Вариант 512 ГБ даёт больше места','Вариант 1 ТБ даёт больше места')
start=s.index("('Чем Xiaomi")
end=s.index(")]",start)+2
s=s[:start]+"""('Чем Xiaomi 17T Pro отличается от Xiaomi 17T?', 'Xiaomi 17T Pro оснащён Dimensity 9500, экраном 6,83 дюйма до 144 Гц, аккумулятором 7000 мА&#183;ч и зарядкой до 100 Вт по проводу либо до 50 Вт без провода. У Xiaomi 17T &#8212; Dimensity 8500-Ultra, экран 6,59 дюйма до 120 Гц, аккумулятор 6500 мА&#183;ч и проводная зарядка до 67 Вт. Сравните обе модели на <a href="https://goodmi.ru/smartfonyi/xiaomi-17t-series/">странице серии</a>.')]"""+s[end:]
s=s.replace("first='Смартфоны Xiaomi 17T Pro в GOODMi в Севастополе и Крыму — гарантия 1 год, варианты 12/512 ГБ и 12/1 ТБ, камера Leica и AMOLED 144 Гц.'", "first='Смартфоны Xiaomi 17T Pro в GOODMi в Севастополе и Крыму — гарантия 1 год, память 12/512 ГБ и 12/1 ТБ, камеры Leica и экран 144 Гц.'")
s=s.replace('`xiaomi-17t-pro-pro` (№91–92)', '`xiaomi-smartfony` (№93–94)')
s=s.replace('Wordstat восстановился.', 'Wordstat доступен; модельный кластер и USP проверены.')
s=s.replace("html=html.replace('Купить", "html=html.replace('отправляем в любой регион.', 'условия доставки уточняются при оформлении.').replace('по всей России</span>', 'по России</span>')\nhtml=html.replace('Купить",1)
compile(s,'update_xiaomi17t_pro_rebrand.py','exec')
(root/'_DEV/update_xiaomi17t_pro_rebrand.py').write_text(s,encoding='utf-8')
