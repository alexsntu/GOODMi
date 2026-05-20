import os

# ===== SKILL.MD =====
skill_path = r'C:\Users\Алекс\GOODMi\.cursor\skills\seo-shop-page-builder\SKILL.md'
with open(skill_path, 'r', encoding='utf-8') as f:
    s = f.read()

changes_skill = 0

# 1. AEO section — remove FAQPage bullets
old = ('- JSON-LD `FAQPage` содержит **те же 4 вопроса**, что и HTML (строго совпадают)\n'
       '- На странице должен быть только один `FAQPage`: в JSON-LD. В HTML-секции FAQ не использовать '
       '`itemscope itemtype="https://schema.org/FAQPage"`.')
new = ('- В HTML-секции FAQ **не использовать** `itemscope`, `itemtype`, `itemprop` — '
       'FAQPage разметка не применяется (Google с 2023 г. не показывает FAQ rich results для коммерческих сайтов)')
if old in s: s = s.replace(old, new); changes_skill += 1
else: print('WARN: AEO bullets not found')

# 2. Block 2 FAQ description
old = ('- **FAQ**: `<section class="gm-faq">` → `<h3 class="gm-section-title">` → 4 вопроса '
       '(delivery, guarantee, trade-in, model choice). HTML-версия за collapse допустима — FAQ покрыт JSON-LD `FAQPage` в Layout-блоке.')
new = ('- **FAQ**: `<section class="gm-faq">` → `<h3 class="gm-section-title">` → 4 вопроса '
       '(delivery, guarantee, trade-in, model choice). Без `itemscope`/`itemtype`/`itemprop` — FAQPage разметка не используется.')
if old in s: s = s.replace(old, new); changes_skill += 1
else: print('WARN: Block2 FAQ desc not found')

# 3. Logic explanation
old = ('- Блок 2 (преимущества + FAQ + CTA) — FAQ покрыт JSON-LD `FAQPage` в Layout-блоке, '
       'поэтому скрытие за collapse не влияет на SEO; коллапс даёт пользователю выбор без ущерба для ранжирования')
new = ('- Блок 2 (преимущества + FAQ + CTA) — FAQ текст полезен для ранжирования; '
       'скрытие за collapse не критично для индексации')
if old in s: s = s.replace(old, new); changes_skill += 1
else: print('WARN: Logic explanation not found')

# 4. Step 6 checklist — remove FAQPage item
old = ('- [ ] В `[slug]-jsonld.html` нет `@graph` — каждый тип в отдельном `<script>` блоке\n'
       '- [ ] В `[slug]-jsonld.html` нет `WebPage` (CS-Cart генерирует сам)\n'
       '- [ ] `FAQPage` в jsonld-файле содержит `@id` (`https://goodmi.ru/[slug]/#faq`) и `mainEntity`')
new = ('- [ ] В `[slug]-jsonld.html` нет `@graph` — каждый тип в отдельном `<script>` блоке\n'
       '- [ ] В `[slug]-jsonld.html` нет `WebPage` (CS-Cart генерирует сам)')
if old in s: s = s.replace(old, new); changes_skill += 1
else: print('WARN: Step6 FAQPage item not found')

# 5. AEO checklist
old = ('**AEO checklist:**\n'
       '- [ ] FAQ обёрнут в `<section class="gm-faq" ...>` и находится в **Блоке 2**\n'
       '- [ ] 4 вопроса покрывают 4 интента: доставка · гарантия · трейд-ин · выбор модели\n'
       '- [ ] В JSON-LD `FAQPage` заполнено поле `"name"`\n'
       '- [ ] Первое предложение каждого ответа = прямой ответ\n'
       '- [ ] Длина каждого ответа: 40–80 слов\n'
       '- [ ] JSON-LD FAQPage содержит те же 4 вопроса, что и HTML (строго совпадают)\n'
       '- [ ] В HTML FAQ нет `itemscope`, `itemtype`, `itemprop` (во избежание дубля `FAQPage`)')
new = ('**AEO checklist:**\n'
       '- [ ] FAQ обёрнут в `<section class="gm-faq" ...>` и находится в **Блоке 2**\n'
       '- [ ] 4 вопроса покрывают 4 интента: доставка · гарантия · трейд-ин · выбор модели\n'
       '- [ ] Первое предложение каждого ответа = прямой ответ\n'
       '- [ ] Длина каждого ответа: 40–80 слов\n'
       '- [ ] В HTML FAQ нет `itemscope`, `itemtype`, `itemprop`')
if old in s: s = s.replace(old, new); changes_skill += 1
else: print('WARN: AEO checklist not found')

# 6. Яндекс-виджет — fix star entity to SVG
old = '  <span class="gm-advantage-icon" aria-hidden="true">&#x2B50;</span>'
new = ('  <span class="gm-advantage-icon" aria-hidden="true">'
       '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">'
       '<polygon points="10,2 12.5,7.5 18.5,8.2 14,12.5 15.5,18.5 10,15.5 4.5,18.5 6,12.5 1.5,8.2 7.5,7.5"/>'
       '</svg></span>')
if old in s: s = s.replace(old, new); changes_skill += 1
else: print('WARN: Star entity in widget not found')

# 7. Trust strip table — replace bad entities with descriptive names
old = ('| `&#x2714;` | Гарантия 1 год | на новую технику |\n'
       '| `&#x26A1;` | Доставка СДЭК | по всей России |\n'
       '| `&#x21BA;` | Трейд-ин | сдайте старое |\n'
       '| `&#x260E;` | GOODMi | с 2016 года |\n'
       '| `&#x2714;` | Фирменный магазин | техники Xiaomi |\n'
       '| `&#x2B50;` | Бонусная программа | GOODMi |')
new = ('| галочка (checkmark SVG) | Гарантия 1 год | на новую технику |\n'
       '| молния (lightning SVG) | Доставка СДЭК | по всей России |\n'
       '| стрелки обмена (exchange SVG) | Трейд-ин | сдайте старое |\n'
       '| галочка (checkmark SVG) | GOODMi | с 2016 года |\n'
       '| галочка (checkmark SVG) | Фирменный магазин | техники Xiaomi |\n'
       '| звезда (star SVG) | Бонусная программа | GOODMi |')
if old in s: s = s.replace(old, new); changes_skill += 1
else: print('WARN: Trust strip table not found')

# 8. Кириллика checklist — remove FAQPage reference
old = ('- [ ] Один вопрос FAQ переформулирован с кириллическим написанием бренда '
       '— вопрос в JSON-LD FAQPage совпадает с HTML')
new = '- [ ] Один вопрос FAQ переформулирован с кириллическим написанием бренда'
if old in s: s = s.replace(old, new); changes_skill += 1
else: print('WARN: Кириллика checklist item not found')

with open(skill_path, 'w', encoding='utf-8', newline='') as f:
    f.write(s)
print(f'SKILL.md: {changes_skill}/8 changes applied')

# ===== RULE FILE =====
rule_path = r'C:\Users\Алекс\GOODMi\.cursor\rules\seo--for-shop-html-block.mdc'
with open(rule_path, 'r', encoding='utf-8') as f:
    r = f.read()

changes_rule = 0

# 1. JSON-LD structure — remove FAQPage script block
old = ('<script type="application/ld+json">\n'
       '{\n'
       '  "@context": "https://schema.org",\n'
       '  "@type": "FAQPage",\n'
       '  "@id": "https://goodmi.ru/[slug]/#faq",\n'
       '  "mainEntity": [...]\n'
       '}\n'
       '</script>\n'
       '\n'
       '<script type="application/ld+json">\n'
       '{\n'
       '  "@context": "https://schema.org",\n'
       '  "@type": "ItemList",')
new = ('<script type="application/ld+json">\n'
       '{\n'
       '  "@context": "https://schema.org",\n'
       '  "@type": "ItemList",')
if old in r: r = r.replace(old, new); changes_rule += 1
else: print('WARN: FAQPage script block not found')

# 2. Rules section — remove FAQPage rule
old = '- `FAQPage` — обязательны `@id` и `mainEntity`\n- `LocalBusiness`'
new = '- `LocalBusiness`'
if old in r: r = r.replace(old, new); changes_rule += 1
else: print('WARN: FAQPage rule line not found')

# 3. FAQPage section — replace with deprecation note
old = ('### FAQPage\n'
       '- Обязательны `@id` (`"https://goodmi.ru/[slug]/#faq"`) и `mainEntity`\n'
       '- 4 реальных вопроса из контента страницы\n'
       '- Охватывают **4 типа покупательских интентов** (AEO): доставка · гарантия · трейд-ин · выбор модели\n'
       '- Каждый `acceptedAnswer` начинается с прямого ответа (первое предложение = вся суть)\n'
       '- В HTML-секции FAQ не добавлять `itemscope itemtype="https://schema.org/FAQPage"` — дублирует GSC')
new = ('### FAQPage — не используется\n\n'
       'Google с сентября 2023 г. не показывает FAQ rich results для коммерческих сайтов. '
       'FAQPage JSON-LD и HTML-микроразметка (`itemscope`/`itemtype`/`itemprop`) в секции FAQ '
       '**запрещены** — вызывают ошибки дублирования в GSC без пользы для ранжирования. '
       'FAQ-текст остаётся на странице и ранжируется как обычный контент.')
if old in r: r = r.replace(old, new); changes_rule += 1
else: print('WARN: FAQPage section not found')

# 4. Block 2 table — update FAQ row
old = '| `gm-faq` (4 вопроса) | HTML-версия за collapse допустима при наличии JSON-LD | Покрыт JSON-LD `FAQPage` |'
new = '| `gm-faq` (4 вопроса) | FAQ-текст ранжируется как контент; FAQPage разметка не используется | — |'
if old in r: r = r.replace(old, new); changes_rule += 1
else: print('WARN: Block2 table FAQ row not found')

# 5. Block 2 FAQ description in Structure section
old = ('5. **FAQ** — `<section class="gm-faq">` → `<h3 class="gm-section-title">` → 4 вопроса '
       '(delivery, guarantee, trade-in, model choice). Без `itemscope/itemtype/itemprop` — '
       'во избежание дубля `FAQPage`. Покрыт JSON-LD в Layout-блоке.')
new = ('5. **FAQ** — `<section class="gm-faq">` → `<h3 class="gm-section-title">` → 4 вопроса '
       '(delivery, guarantee, trade-in, model choice). Без `itemscope`/`itemtype`/`itemprop` — '
       'FAQPage разметка не используется.')
if old in r: r = r.replace(old, new); changes_rule += 1
else: print('WARN: Block2 FAQ description not found')

# 6. AEO checklist
old = ('**AEO (Answer Engine Optimization):**\n'
       '- [ ] FAQ-секция обёрнута в `<section class="gm-faq" ...>` и находится в **Block 2**\n'
       '- [ ] 4 вопроса покрывают 4 интента: доставка · гарантия · трейд-ин · выбор модели\n'
       '- [ ] В JSON-LD `FAQPage` заполнено поле `"name"`\n'
       '- [ ] Каждый `acceptedAnswer` начинается с прямого ответа (не вводного слова, не «мы»)\n'
       '- [ ] Длина каждого ответа: 40–80 слов\n'
       '- [ ] JSON-LD `FAQPage` содержит те же 4 вопроса, что и HTML (не расходятся!)\n'
       '- [ ] В HTML FAQ нет `itemscope`, `itemtype`, `itemprop` (во избежание дубля `FAQPage`)')
new = ('**AEO (Answer Engine Optimization):**\n'
       '- [ ] FAQ-секция обёрнута в `<section class="gm-faq" ...>` и находится в **Block 2**\n'
       '- [ ] 4 вопроса покрывают 4 интента: доставка · гарантия · трейд-ин · выбор модели\n'
       '- [ ] Каждый ответ начинается с прямого ответа (не вводного слова, не «мы»)\n'
       '- [ ] Длина каждого ответа: 40–80 слов\n'
       '- [ ] В HTML FAQ нет `itemscope`, `itemtype`, `itemprop`')
if old in r: r = r.replace(old, new); changes_rule += 1
else: print('WARN: AEO checklist not found')

with open(rule_path, 'w', encoding='utf-8', newline='') as f:
    f.write(r)
print(f'seo--for-shop-html-block.mdc: {changes_rule}/6 changes applied')
