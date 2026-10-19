#!/usr/bin/env python3
# Сводка журнала медленных запросов MariaDB (/var/log/mysql/mariadb-slow.log, 2026-10-19): какие запросы и таблицы держат базу.
# Запросы нормализуются (числа и строки → ?), группируются; суммы времени выполнения и ожидания блокировок. Только чтение.
import collections, re, sys

F = sys.argv[1] if len(sys.argv) > 1 else '/var/log/mysql/mariadb-slow.log'
txt = open(F, errors='replace').read()
entries = txt.split('# Time: ')[1:]
groups = collections.defaultdict(lambda: [0, 0.0, 0.0, 0, '', collections.Counter(), 0.0])
for e in entries:
    u = re.search(r'# User@Host: (\w+)', e)
    m = re.search(r'Query_time: ([\d.]+)\s+Lock_time: ([\d.]+)\s+Rows_sent: (\d+)\s+Rows_examined: (\d+)', e)
    if not m:
        continue
    q = ' '.join(l for l in e.split('\n')[1:] if l and not l.startswith('#') and not l.startswith('SET timestamp')
                 and not l.startswith('use ')).strip()
    n = re.sub(r"'(?:[^'\\]|\\.)*'", '?', q)
    n = re.sub(r'\b\d+\b', '?', n)
    n = re.sub(r'\(\s*\?(?:\s*,\s*\?)+\s*\)', '(?…)', n)
    n = re.sub(r'\s+', ' ', n)[:160]
    g = groups[n]
    g[0] += 1; g[1] += float(m.group(1)); g[2] += float(m.group(2)); g[3] += int(m.group(4)); g[4] = u.group(1) if u else '?'
    g[5][e[:9].strip()[:9]] += 1
    g[6] = max(g[6], float(m.group(1)))
tot = sum(g[1] for g in groups.values())
print('записей %d, суммарно %.0f с выполнения; первые – с наибольшим суммарным временем' % (len(entries), tot))
print('%5s %8s %6s %8s %7s %12s  %-8s запрос' % ('шт', 'сумма,с', 'доля', 'блок,с', 'макс,с', 'строк просм', 'кто'))
for n, g in sorted(groups.items(), key=lambda kv: -kv[1][1])[:30]:
    print('%5d %8.0f %5.1f%% %8.1f %7.1f %12d  %-8s %s' % (g[0], g[1], 100 * g[1] / tot, g[2], g[6], g[3] // max(g[0], 1), g[4], n))
# Таблицы
tabs = collections.Counter(); tabt = collections.Counter()
for n, g in groups.items():
    for t in set(re.findall(r'\b(?:FROM|JOIN|INTO|UPDATE|TABLE)\s+`?(\w+)`?', n, re.I)):
        tabs[t] += g[0]; tabt[t] += g[1]
print('\nтаблицы по суммарному времени медленных запросов:')
for t, s in tabt.most_common(15):
    print('   %8.0f с  %5d запросов  %s' % (s, tabs[t], t))
