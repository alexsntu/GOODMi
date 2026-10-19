#!/usr/bin/env python3
"""Шаблоны категорий GOODMi по справочнику автозагрузки Авито (2026-10-19, версия 2): map.tsv (новые категории) и
map-derived.tsv (шаблоны из UnitXML) + node-<slug>.json → templates-spec2.json для apply-templates.php.
Поля: значения с единственным вариантом; Condition/AdType – если «Новое» / «Товар приобретен на продажу» в разделе
не допускаются, берётся допустимое «новое»; у шаблонов из UnitXML сохраняются их значения, если раздел их допускает;
обязательные поля без значения → required_fields (кроме нужных только для б/у)."""
import json
import re
GENERIC = {"BoxSealed": "Да", "ScreenCondition": "Без дефектов", "CaseCondition": "Без дефектов", "DeviceWork": "Включается", "Devicework": "Включается"}
COMMON = {"Id","DateBegin","DateEnd","Address","Title","Description","Images","ImageUrls","ImageNames","Price","ContactPhone","ContactMethod","Condition","AdType"}
FASHION = {"cemodany_i_doroznye_sumki","ochki","perchatki_i_varezhki","platki_i_sharfy","riukzaki","sumki","zonty"}
EXTRA_BY_SLUG = {
    "bloki_pitaniya_i_batarei": {"GoodsType": "Блоки питания и батареи"}, "kanceliariia": {"GoodsType": "Канцелярия"},
    "sumki": {"ApparelType": "Через плечо"}, "plansety": {"GoodsType": "Планшеты", "ProductsType": "Планшет", "BoxSealed": "Да"},
    "mobilnye_telefony": {"BoxSealed": "Да"}, "televizory_1147261": {"BoxSealed": "Да"},
    "svetodiodnaia_podsvetka": {"SubType": "Светодиодная лента"}, "kompiuternye_kresla_2170273": {"ComputerChairType": "Компьютерное"},
    "stolovye_pribory": {"ProductType": "Набор"},
}
EXTRA_BY_CAT = {234: {"GoodsType": "Другое"}}
AUTO_OK = {"Brand", "Vendor", "Model", "Color"}
CATALOG_BY_ITEM = FASHION | {"svetodiodnaia_podsvetka", "mobilnye_telefony", "plansety", "televizory_1147261", "gazonokosilki"}
paths = dict(reversed(l.rstrip("\n").split("\t")) for l in open("leaves.tsv"))
derived = {}
for line in open("derived-mm.tsv"):
    cid, fields, note = line.rstrip("\n").split("\t")
    derived[int(cid)] = json.loads(fields)

def build(cid, slug, old):
    d = json.load(open("node-%s.json" % slug))
    fields, required, allowed, auto = {}, [], {}, []
    extra = dict(EXTRA_BY_SLUG.get(slug, {}), **EXTRA_BY_CAT.get(cid, {}))
    if slug in FASHION:
        extra.setdefault("Gender", "Унисекс")
    for f in d["fields"]:
        tag = f["tag"]
        for c in f["content"]:
            vals = [v["value"] for v in c.get("values") or []]
            allowed.setdefault(tag, set()).update(vals)
            if tag in ("Condition", "AdType"):
                continue
            if tag in COMMON or not c.get("required"):
                continue
            dep = " ".join(c.get("dependencies_text") or [])
            if "Б/у" in dep or "не \x27Новое\x27" in dep:
                continue
            if len(vals) == 1:
                fields.setdefault(tag, vals[0])
            elif tag not in [r[0] for r in required]:
                required.append((tag, dep, vals))
    for tag, value in (old or {}).items():
        if tag in COMMON or tag in fields or tag not in allowed:
            continue
        if allowed[tag] and value not in allowed[tag]:
            continue
        fields[tag] = value
    for tag, value in extra.items():
        fields[tag] = value
    cond = allowed.get("Condition", set())
    if cond and "Новое" not in cond:
        fields["Condition"] = next((v for v in ("Новый", "Новое с биркой", "Новая", "Новые") if v in cond), sorted(cond)[0])
    ad = allowed.get("AdType", set())
    if ad and "Товар приобретен на продажу" not in ad:
        fields["AdType"] = next((v for v in ad if "продаж" in v and "своё" not in v and "свое" not in v), sorted(ad)[0])
    # Новый товар: в упаковке, без дефектов, включается
    for tag, dep, vals in required:
        if tag in GENERIC and tag not in fields and (not vals or GENERIC[tag] in vals):
            fields[tag] = GENERIC[tag]
    # «Применимо/Заполните, если в поле X … V» – обязательно, только если в шаблоне X = V
    def applies(dep):
        m = re.search(r"в поле (\w+)[^\x27]*\x27([^\x27]+)\x27", dep)
        if not m or m.group(1) == "Condition":
            return True
        return fields.get(m.group(1)) == m.group(2) or (m.group(1) in fields and m.group(2) in str(fields.get(m.group(1))))
    required = [t for t, dep, vals in required if t not in fields and applies(dep)]
    if slug not in CATALOG_BY_ITEM:
        auto = [t for t in required if t in AUTO_OK]
    return {"category_id": cid, "slug": slug, "fields": fields, "auto_fields": auto, "required_fields": required,
            "note": ("из UnitXML, по справочнику Авито 19.10: " if old is not None else "по справочнику Авито 19.10: ") + paths.get(slug, slug)}

spec = []
for fname, is_derived in (("map-mm.tsv", False), ("map-derived-mm.tsv", True)):
    for line in open(fname):
        cid, slug = line.split()
        cid = int(cid)
        spec.append(build(cid, slug, derived.get(cid, {}) if is_derived else None))
json.dump(spec, open("templates-spec2-mm.json", "w"), ensure_ascii=False, indent=1)
print("шаблонов:", len(spec))
for s in spec:
    if s["required_fields"] or "Condition" in s["fields"] or "AdType" in s["fields"]:
        print(s["category_id"], s["slug"][:28], "| обяз.:", ",".join(s["required_fields"]) or "-", "| авто:", ",".join(s["auto_fields"]) or "-", "|", s["fields"].get("Condition", ""), s["fields"].get("AdType", ""))
