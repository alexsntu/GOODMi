# Поиск в журналах ошибок сайтов (14 дней) предупреждений PHP 7.4, которые в PHP 8.x становятся исключениями (2026-10-21).
# Запуск на сервере: python3 -I gm-php8-log-risks.py. Только чтение.
import re,collections,gzip,glob,os
os.chdir("/var/www/maxmobiles/data/logs")
# предупреждения 7.4, которые в 8.x – исключения (TypeError/ValueError/DivisionByZeroError/Error)
pat=re.compile(r"expects parameter|must be an array or an object that implements Countable|Division by zero|Modulo by zero|Unsupported operand types|Invalid arguments passed|Illegal string offset|Cannot use a scalar value|Illegal offset type|Object of class .* could not be converted|Array to string conversion|A non-numeric value|Uninitialized string offset|Cannot unset offset|Only variables should be passed by reference|Non-static method .* should not be called statically|Fatal error|Uncaught")
c=collections.Counter(); first={}; last={}
for s in ["goodmi.ru","maxmobiles.ru","xione.ru","mi92.ru"]:
    for f in sorted(glob.glob(s+".error.log*")):
        op=gzip.open if f.endswith(".gz") else open
        for l in op(f,"rt",errors="replace"):
            if "PHP message" not in l: continue
            for part in l.split("PHP message: ")[1:]:
                if not pat.search(part): continue
                m=re.search(r"(PHP )?(\w[\w ]*?):\s+(.*?) in (/var/www/\S+?) on line (\d+)",part)
                if m:
                    key="%s | %s: %s @ %s:%s"%(s,m.group(2),re.sub(r"\d+","N",m.group(3))[:120],m.group(4).split("/maxmobiles.ru/")[-1],m.group(5))
                else:
                    key=s+" | "+re.sub(r"\d+","N",part)[:200]
                d=l[:10]
                c[key]+=1; first.setdefault(key,d); last[key]=max(last.get(key,d),d)
for k,v in c.most_common(40): print(v,first[k],"..",last[k],k)
print("всего видов:",len(c))
