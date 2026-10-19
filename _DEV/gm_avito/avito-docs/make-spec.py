#!/usr/bin/env python3
"""Шаблоны категорий GOODMi по справочнику автозагрузки Авито (2026-10-19): map.tsv (категория сайта → раздел Авито) +
node-<slug>.json → templates-spec.json для apply-templates.php."""
import json
COMMON = {"Id","DateBegin","DateEnd","Address","Title","Description","Images","ImageUrls","ImageNames","Price","AdType","Condition","ContactPhone","ContactMethod"}
FASHION = {"cemodany_i_doroznye_sumki","ochki","perchatki_i_varezhki","platki_i_sharfy","riukzaki","sumki","zonty"}
EXTRA_BY_SLUG = {
    "bloki_pitaniya_i_batarei": {"GoodsType": "Блоки питания и батареи"},
    "kanceliariia": {"GoodsType": "Канцелярия"},
    "sumki": {"ApparelType": "Через плечо"},
    "plansety": {"GoodsType": "Планшеты", "ProductsType": "Планшет", "BoxSealed": "Да"},
    "mobilnye_telefony": {"BoxSealed": "Да"},
    "televizory_1147261": {"BoxSealed": "Да"},
    "svetodiodnaia_podsvetka": {"SubType": "Светодиодная лента"},
    "kompiuternye_kresla_2170273": {"ComputerChairType": "Компьютерное"},
    "stolovye_pribory": {"ProductType": "Набор"},
}
EXTRA_BY_CAT = {462: {"GoodsType": "Чехлы и плёнки"}, 1406: {"GoodsType": "Стилусы"}}
AUTO = {s: ["Brand"] for s in FASHION | {"svetodiodnaia_podsvetka"}}
paths = dict(reversed(l.rstrip("\n").split("\t")) for l in open("leaves.tsv"))
spec = []
for line in open("map.tsv"):
    cid, slug = line.split()
    cid = int(cid)
    d = json.load(open("node-%s.json" % slug))
    fields, required = {}, []
    extra = dict(EXTRA_BY_SLUG.get(slug, {}), **EXTRA_BY_CAT.get(cid, {}))
    if slug in FASHION:
        extra.setdefault("Gender", "Унисекс")
    for f in d["fields"]:
        tag = f["tag"]
        if tag in COMMON:
            continue
        for c in f["content"]:
            if not c.get("required"):
                continue
            dep = " ".join(c.get("dependencies_text") or [])
            if "Б/у" in dep or "не \x27Новое\x27" in dep:
                continue
            vals = [v["value"] for v in c.get("values") or []]
            if len(vals) == 1:
                fields.setdefault(tag, vals[0])
            elif tag in extra:
                assert not vals or extra[tag] in vals, (slug, tag, extra[tag])
            elif tag not in required:
                required.append(tag)
    for tag, value in extra.items():
        fields[tag] = value
    spec.append({"category_id": cid, "slug": slug, "fields": fields, "auto_fields": AUTO.get(slug, []),
                 "required_fields": [t for t in required if t not in fields],
                 "note": "по справочнику Авито 19.10: " + paths.get(slug, slug)})
json.dump(spec, open("templates-spec.json", "w"), ensure_ascii=False, indent=1)
for s in spec:
    print(s["category_id"], s["slug"], "|", "; ".join("%s=%s" % kv for kv in s["fields"].items())[:150], "| авто:", ",".join(s["auto_fields"]) or "-", "| обяз.:", ",".join(s["required_fields"]) or "-")
