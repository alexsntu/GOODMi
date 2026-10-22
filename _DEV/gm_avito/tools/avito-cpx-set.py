#!/usr/bin/env python3
"""Ручные ставки Авито по списку (ЗАПИСЬ, 2026-10-22). avito-cpx-set.py <company_id> <pilot.tsv> <bids.json> <лимит ₽> [--remove]
pilot.tsv – avito_id и advice_bid (₽); actionTypeID – из getBids (bids.json). Печатает ответ Авито по каждому объявлению.
--remove – снять ставки этих объявлений (откат)."""
import csv, json, sys, time, urllib.request, urllib.parse
cid, pilot_f, bids_f, limit = sys.argv[1], sys.argv[2], sys.argv[3], int(sys.argv[4])
remove = "--remove" in sys.argv
c = json.load(open("/var/www/maxmobiles/data/private/gm_avito.json"))[cid]
data = urllib.parse.urlencode({"grant_type": "client_credentials", "client_id": c["client_id"], "client_secret": c["client_secret"]}).encode()
with urllib.request.urlopen(urllib.request.Request("https://api.avito.ru/token", data=data), timeout=30) as r:
    token = json.load(r)["access_token"]
bids = json.load(open(bids_f))
def post(path, body):
    for attempt in range(5):
        req = urllib.request.Request("https://api.avito.ru" + path, data=json.dumps(body).encode(), method="POST",
                                     headers={"Authorization": "Bearer " + token, "Content-Type": "application/json"})
        try:
            with urllib.request.urlopen(req, timeout=60) as r:
                return r.status, r.read().decode("utf-8", "replace")[:300]
        except urllib.error.HTTPError as e:
            if e.code == 429:
                time.sleep(20); continue
            return e.code, e.read().decode("utf-8", "replace")[:300]
    return 429, ""
for row in csv.DictReader(open(pilot_f), delimiter="\t"):
    item = int(row["avito_id"])
    if remove:
        code, body = post("/cpxpromo/1/remove", {"itemID": item})
    else:
        action = int(bids[row["avito_id"]]["actionTypeID"])
        bid = int(round(float(row["advice_bid"]))) * 100
        code, body = post("/cpxpromo/1/setManual", {"itemID": item, "actionTypeID": action, "bidPenny": bid, "limitPenny": limit * 100})
    print(item, row["title"][:45], "|", code, body)
    time.sleep(3.5)
