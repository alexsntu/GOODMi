#!/usr/bin/env python3
# Поиск в стороннем и самописном коде конструкций, которые ломаются или устарели в PHP 8.x (2026-10-18, тест 8.3 на dev).
# Запуск на сервере: python3 -I gm-php8-scan.py <корень копии> <файл со списком путей для проверки>
import os, re, sys

ROOT, LIST = sys.argv[1], sys.argv[2]
P = [
    ('УДАЛЕНО в 8.0: each()', r'(?<![\w>$:])each\s*\('),
    ('УДАЛЕНО в 8.0: create_function()', r'\bcreate_function\s*\('),
    ('УДАЛЕНО в 8.0: money_format()', r'\bmoney_format\s*\('),
    ('УДАЛЕНО в 8.0: get_magic_quotes_*()', r'\bget_magic_quotes_\w+\s*\('),
    ('УДАЛЕНО в 7.0: mysql_*()', r'(?<![\w>$:])mysql_(query|connect|fetch_\w+|num_rows|real_escape_string|select_db)\s*\('),
    ('УДАЛЕНО в 7.0: ereg/split()', r'(?<![\w>$:])(ereg|eregi|ereg_replace|split)\s*\('),
    ('УДАЛЕНО в 8.0: __autoload', r'function\s+__autoload\b'),
    ('УДАЛЕНО в 8.0: (real), is_real()', r'\(real\)|\bis_real\s*\('),
    ('УДАЛЕНО в 8.0: fgetss/hebrevc/convert_cyr_string/restore_include_path', r'\b(fgetss|hebrevc|convert_cyr_string|restore_include_path|ezmlm_hash|image2wbmp)\s*\('),
    ('УДАЛЕНО в 8.0: FILTER_FLAG_SCHEME/HOST_REQUIRED', r'FILTER_FLAG_(SCHEME|HOST)_REQUIRED'),
    ('УДАЛЕНО в 8.0: $str{N} (фигурные скобки)', r'\$\w+\{\s*[\$\d]'),
    ('ИЗМЕНЕНО в 8.0: implode(массив, разделитель)', r'\bimplode\s*\(\s*\$\w+\s*,\s*[\'"]'),
    ('УСТАРЕЛО 8.1: strftime/gmstrftime', r'(?<![\w>$:])g?m?strftime\s*\('),
    ('УСТАРЕЛО 8.1: FILTER_SANITIZE_STRING', r'FILTER_SANITIZE_STRING'),
    ('УСТАРЕЛО 8.2: utf8_encode/utf8_decode', r'\butf8_(en|de)code\s*\('),
    ('УСТАРЕЛО 8.2: "${var}" в строке', r'"[^"\n]*\$\{\w'),
    ('ИЗМЕНЕНО в 8.1: mysqli бросает исключения', r'new\s+mysqli\b|\bmysqli_connect\s*\('),
    ('ИЗМЕНЕНО в 8.0: PDO бросает исключения', r'new\s+PDO\s*\('),
    ('РИСК 8.0: count()/sizeof() от возможного null', r'\b(count|sizeof)\s*\(\s*\$_(GET|POST|REQUEST)\['),
    ('УСТАРЕЛО 8.1: key()/current()/next()/reset() от объекта', r'\b(key|current|next|reset|end)\s*\(\s*\$this\b'),
]
P = [(n, re.compile(r)) for n, r in P]
hits = {}
paths = [l.strip() for l in open(LIST) if l.strip()]
files = []
for p in paths:
    full = os.path.join(ROOT, p)
    if os.path.isfile(full):
        files.append(full)
    elif os.path.isdir(full):
        for d, dn, fn in os.walk(full):
            dn[:] = [x for x in dn if x not in ('vendor', 'phpexcel', 'PHPExcel')]
            files += [os.path.join(d, f) for f in fn if f.endswith('.php')]
for f in files:
    try:
        lines = open(f, encoding='utf-8', errors='replace').read().split('\n')
    except OSError:
        continue
    in_comment = False
    for i, line in enumerate(lines, 1):
        s = line.strip()
        if s.startswith('/*'):
            in_comment = True
        if in_comment:
            if '*/' in s:
                in_comment = False
            continue
        if s.startswith('//') or s.startswith('#') or s.startswith('*'):
            continue
        for n, r in P:
            if r.search(line):
                hits.setdefault(n, []).append('%s:%d  %s' % (f.replace(ROOT + '/', ''), i, s[:110]))
print('файлов проверено:', len(files))
for n in sorted(hits):
    print('\n## %s – %d' % (n, len(hits[n])))
    for h in hits[n][:12]:
        print('   ' + h)
    if len(hits[n]) > 12:
        print('   … ещё %d' % (len(hits[n]) - 12))
