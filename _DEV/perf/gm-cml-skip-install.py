#!/usr/bin/env python3
# Добавляет обработчик пропуска неизменённых товаров обмена CommerceML в модуль gm_delivery_date (2026-10-19).
# python3 -I gm-cml-skip-install.py <папка модуля gm_delivery_date> <файл с кодом функции>
import os, sys

D, CODE = sys.argv[1], sys.argv[2]
func = os.path.join(D, 'func.php')
init = os.path.join(D, 'init.php')
code = open(CODE, encoding='utf-8').read()
s = open(func, encoding='utf-8').read()
if 'commerceml_product_importer_import_pre' in s:
    sys.exit('func.php: обработчик уже есть')
t = open(init, encoding='utf-8').read()
old = "    'place_order'\n);"
if t.count(old) != 1:
    sys.exit('init.php: не найдено место для хука')
new_t = t.replace(old, "    'place_order',\n    // Обмен CommerceML: пропуск неизменённых товаров (2026-10-19)\n    'commerceml_product_importer_import_pre'\n);")
for path, content in ((func, s.rstrip('\n') + '\n' + code), (init, new_t)):
    st = os.stat(path)
    tmp = path + '.gmtmp'
    open(tmp, 'w', encoding='utf-8').write(content)
    os.chown(tmp, st.st_uid, st.st_gid)
    os.chmod(tmp, st.st_mode & 0o777)
    os.replace(tmp, path)
print('обработчик добавлен:', D)
