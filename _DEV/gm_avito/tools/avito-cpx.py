#!/usr/bin/env python3
"""Ставки Авито (цена целевого действия), ТОЛЬКО ЧТЕНИЕ. avito-cpx.py <company_id> promos|bids <файл ids> <выход.json> [лимит]
promos – getPromotionsByItemIds пачками по 100; bids – getBids по каждому id (с учётом X-RateLimit)."""
import json, sys, time, urllib.request, urllib.parse
cid, mode, idsf, out = sys.argv[1:5]
lim = int(sys.argv[5]) if len(sys.argv) > 5 else 10**9
c = json.load(open("/var/www/maxmobiles/data/private/gm_avito.json"))[cid]
cache = "/root/gm-bench/avito-docs/token-%s.json" % cid
try:
    t = json.load(open(cache)); assert t["exp"] > time.time() + 300
except Exception:
    data = urllib.parse.urlencode({"grant_type": "client_credentials", "client_id": c["client_id"], "client_secret": c["client_secret"]}).encode()
    with urllib.request.urlopen(urllib.request.Request("https://api.avito.ru/token", data=data), timeout=30) as r:
        tok = json.load(r)
    t = {"token": tok["access_token"], "exp": time.time() + tok["expires_in"]}
    import os; old = os.umask(0o077); json.dump(t, open(cache, "w")); os.umask(old)
H = {"Authorization": "Bearer " + t["token"], "Content-Type": "application/json"}
def call(path, body=None):
    for attempt in range(6):
        req = urllib.request.Request("https://api.avito.ru" + path, data=json.dumps(body).encode() if body is not None else None, method="POST" if body is not None else "GET", headers=H)
        try:
            with urllib.request.urlopen(req, timeout=60) as r:
                rem = r.headers.get("X-RateLimit-Remaining"); limh = r.headers.get("X-RateLimit-Limit")
                return json.load(r), rem, limh
        except urllib.error.HTTPError as e:
            if e.code == 429:
                time.sleep(30); continue
            return {"error": e.code, "body": e.read()[:300].decode("utf-8", "replace")}, None, None
    return {"error": 429}, None, None
ids = [int(x) for x in open(idsf).read().split() if x.strip()][:lim]
res = {}
if mode == "promos":
    for i in range(0, len(ids), 100):
        d, rem, limh = call("/cpxpromo/1/getPromotionsByItemIds", {"itemIDs": ids[i:i+100]})
        res[i] = d
        print("пачка", i, "лимит", limh, "осталось", rem, flush=True)
else:
    for n, i in enumerate(ids):
        d, rem, limh = call("/cpxpromo/1/getBids/%d" % i)
        res[i] = d
        if n % 25 == 0 or "error" in d:
            print(n, i, "лимит", limh, "осталось", rem, ("ОШИБКА %s" % d) if "error" in d else "", flush=True)
        if rem is not None and int(rem) <= 1:
            time.sleep(60)
json.dump(res, open(out, "w"), ensure_ascii=False)
print("готово", len(res))
