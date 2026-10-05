from pathlib import Path
import json,re
root=Path(__file__).resolve().parents[1]
p=root/'Категории/Новые измененные'
h=(p/'xiaomi-17t-pro.html').read_text(encoding='utf-8')
s=(p/'xiaomi-17t-pro-jsonld.html').read_text(encoding='utf-8')
j=[json.loads(x) for x in re.findall(r'<script[^>]*>(.*?)</script>',s,re.S)]
assert len(j[0]['sameAs'])==9
assert h.count('class="gm-faq-question"')==5
assert h.index('class="gm-faq"')<h.index('class="gm-collapse-wrapper"')
assert not re.search(r'Pro Pro|фирменн|официальн|рассроч|aggregateRating|FAQPage|2000 отзыв',h+s,re.I)
for suffix in ['.html','-jsonld.html']:
    assert not (root/'Категории/Готовые'/('xiaomi-17t-pro'+suffix)).exists()
original=(root/'_DEV/update_xiaomi17t_pro_rebrand.py').read_text(encoding='utf-8')
assert 'xiaomi-smartfony' in (root/'_DEV/positioning-rebrand-task.md').read_text(encoding='utf-8')
counts=[[426,417,1567,114502],[140,181,536,20247],[36,20,125,11088],[27,26,97,5724],[1,10,37,739]]
scores=[sum(row[i]/sum(r[i] for r in counts)*w for i,w in enumerate([.55,.2,.15,.1])) for row in counts]
print('Weighted shares:',[round(x*100,2) for x in scores])
print('Verified: JSON-LD, 9 sameAs, 5 visible FAQs, clean positioning, pair moved, next category saved.')
print(j[1]['description'])
