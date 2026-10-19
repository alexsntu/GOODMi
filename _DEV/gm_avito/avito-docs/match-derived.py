#!/usr/bin/env python3
"""Шаблоны из UnitXML → раздел справочника Авито (листья tree.json): кандидаты по совпадению значений полей."""
import json, collections
leaves = [l.rstrip("\n").split("\t") for l in open("leaves.tsv")]
out = {}
for line in open("derived.tsv"):
    cid, fields, note = line.rstrip("\n").split("\t")
    f = json.loads(fields)
    vals = [v for k, v in f.items() if isinstance(v, str)]
    best = []
    for path, slug in leaves:
        parts = path.split(" / ")
        if f.get("Category") not in parts:
            continue
        score = sum(1 for v in vals if v in parts)
        if parts[-1] in vals:
            score += 2
        best.append((score, -len(parts), path, slug))
    best.sort(reverse=True)
    out[cid] = {"fields": f, "candidates": [(b[0], b[2], b[3]) for b in best[:3]]}
json.dump(out, open("derived-candidates.json", "w"), ensure_ascii=False, indent=1)
for cid, o in out.items():
    c = o["candidates"]
    print(cid, "|", " / ".join(v for k, v in o["fields"].items() if k in ("Category","GoodsType","GoodsSubType","ProductType","ProductsType","ProductSubType","AccessoryType","TourismType","GoodsSubCategory")), "→", c[0][1] if c else "НЕТ", c[0][0] if c else "", "|", (c[1][1] + " " + str(c[1][0])) if len(c) > 1 and c[1][0] == c[0][0] else "")
