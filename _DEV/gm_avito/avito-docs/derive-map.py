import json
c = json.load(open("derived-candidates.json"))
over = {"456": "tv_pristavki", "473": "pusko_zaryadnye_ustroistva", "548": "poverbanki", "572": "elektricheskie_obogrevateli_2304782",
        "578": "drugoe_1210658", "579": "drugoe_1210658", "580": "drugoe_1210658", "581": "drugoe_1210658", "2208": "elektroinstrument_drugoe",
        "621": "fonari_i_lampy_turizm", "611": "mylnicy_dozatory", "634": "oborudovanie_dlya_manikyura_i_pedikyura",
        "637": "ukhod_za_polostyu_rta", "640": "ukhod_za_polostyu_rta", "642": "pribory_i_aksessuary_150095", "644": "pribory_i_aksessuary_150095",
        "717": "pribory_i_aksessuary_150095", "645": "termometry", "539": "nausniki", "565": "avtomaticheskie_osvezhiteli_i_aromadiffuzory"}
with open("map-derived.tsv", "w") as f:
    for cid, o in c.items():
        f.write("%s\t%s\n" % (cid, over.get(cid) or o["candidates"][0][2]))
