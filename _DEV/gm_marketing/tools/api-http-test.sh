#!/bin/bash
# Проверка API маркетинг-портала по HTTP: $1 – адрес (https://dev.goodmi.ru), $2 – файл с токеном (JSON api_token), $3 – --resolve или пусто
H=$1; TOKEN=$(python3 -c "import json,sys;print(json.load(open(sys.argv[1]))['api_token'])" "$2"); R="-k -s $3"
c() { curl $R -o /tmp/r.json -w "%{http_code}" "$@"; }
j() { python3 -c "import json,sys;d=json.load(open('/tmp/r.json'));$1" 2>&1 | head -c 200; }
A="Authorization: Bearer $TOKEN"; J="Content-Type: application/json"
echo "без токена:       $(c $H/mkt-api/plan) $(head -c 40 /tmp/r.json)"
echo "неверный токен:   $(c -H "Authorization: Bearer x" $H/mkt-api/plan) $(head -c 40 /tmp/r.json)"
echo "план:             $(c -H "$A" "$H/mkt-api/plan?status=planned") $(j "print(len(d['items']), d['items'][0]['title'][:40])")"
echo "темы pending:     $(c -H "$A" "$H/mkt-api/topics?status=pending") $(j "print(len(d['topics']))")"
echo "state/novinki:    $(c -H "$A" "$H/mkt-api/state/novinki") $(j "print(len(d['seen']))")"
echo "tasks/due:        $(c -H "$A" "$H/mkt-api/tasks/due") $(j "print(d)")"
echo "неизвестный путь: $(c -H "$A" $H/mkt-api/foo) $(cat /tmp/r.json)"
echo "неизвестный ключ: $(c -H "$A" $H/mkt-api/state/xxx) $(cat /tmp/r.json)"
echo "темa без pitch:   $(c -X POST -H "$A" -H "$J" --data '{"title":"x"}' $H/mkt-api/topics) $(cat /tmp/r.json)"
echo "кривой JSON:      $(c -X POST -H "$A" -H "$J" --data '{oops' $H/mkt-api/topics) $(cat /tmp/r.json)"
echo "заголовки:        $(curl $R -D - -o /dev/null -H "$A" $H/mkt-api/tasks/due | grep -i '^content-type\|^cache-control\|^set-cookie' | tr -d '\r' | tr '\n' ' ')"
if [ "$4" = "--write" ]; then
  echo "черновик:         $(c -X POST -H "$A" -H "$J" --data '{"source":"friday","title":"Тест API 🙂","text":"Проверка переноса – эмодзи 🚀 и «кавычки» <b>","category":"тест"}' $H/mkt-api/drafts) $(cat /tmp/r.json)"
  echo "читаем:           $(c -H "$A" "$H/mkt-api/drafts?source=friday&limit=1") $(j "x=d['drafts'][0];print(x['id'],x['title'],x['text'],x['status'],x['created_at'])")"
  echo "state:            $(c -X POST -H "$A" -H "$J" --data '{"urls":["https://example.com/gm-test-1","https://example.com/gm-test-1"]}' $H/mkt-api/state/friday) $(cat /tmp/r.json)"
  echo "state читаем:     $(c -H "$A" $H/mkt-api/state/friday) $(j "print([u for u in d['seen'] if 'gm-test' in u])")"
fi
