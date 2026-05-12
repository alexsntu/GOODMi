---
name: seo-meta-builder
description: Generates H1, title, and meta description for GOODMi store pages (goodmi.ru). Use when the user asks to create, write, generate, or optimize meta tags, H1, title, or meta description for product category pages, model landing pages, or any other store page. Outputs ready-to-use meta tags following the seo--meta-tags.mdc standard with GOODMi branding, Xiaomi product focus, and Crimea geo-targeting.
---

# SEO Meta Builder — GOODMi

Generates H1, title, and meta description for **goodmi.ru** pages.
Always geo-bound to **Севастополь / Крым / GOODMi**.

---

## Workflow (execute in order)

### Step 1 — Parse the request

Extract from the user's message:
- **Page type**: конкретная модель / серия / топовая категория / аксессуары / сервис
- **Product name / category** (e.g., Xiaomi 15 Pro, Redmi Note 15 Pro, планшеты, ноутбуки, наушники)
- **Primary geo target**: Севастополь (default) or Крым
- **Special focus**: условие покупки (рассрочка, трейд-ин) — if mentioned, prioritize in description
- **Quantity**: single page or batch (list of products/categories)

For batch requests — process all items in a single output table.

---

### Step 2 — SERP research (optional, for important pages)

If the page is a **top-level category** (Смартфоны, Планшеты, Ноутбуки) or the user requests it — run **3 WebSearch queries in parallel**:

1. `"купить [Категория/Модель] Севастополь"` — check top-5 title patterns
2. `"[Модель] цена Крым"` — identify competitor title formulas
3. `"[Кириллический вариант бренда/модели] купить"` — проверка частотности кириллических написаний (например: «Сяоми», «Редми», «Поко», «Амазфит» и т.д.)

**Goal:** Check typical title lengths, identify unused keyword angles, detect strong CTR patterns.

---

### Step 2b — Кириллические варианты брендов (обязательно)

После Step 2 оценить результаты третьего запроса:

- Если кириллический вариант бренда встречается в **заголовках сайтов из топ-5** поиска — значит запрос частотный и его стоит учесть.
- Если кириллический вариант присутствует **только в UGC** (форумы, отзывы, Q&A) — игнорировать в title/H1, но можно упомянуть в description как дополнительный хвост.
- Если кириллический вариант **не встречается в топ-5** вообще — не использовать.

**Правило применения:**
- В `title` и `H1` — только латинское написание бренда (Xiaomi, Redmi, POCO, Amazfit).
- В `meta description` — кириллический вариант допустим **только при подтверждённой частотности** (встречается в title конкурентов из топ-5).
- Никогда не добавлять кириллический вариант бренда «на всякий случай» без проверки.

**Известные кириллические варианты для проверки:**

| Бренд | Кириллический вариант | Проверять как |
|---|---|---|
| Xiaomi | Сяоми | `"Сяоми [категория] купить"` |
| Redmi | Редми | `"Редми [серия] купить"` |
| POCO | Поко | `"Поко телефон купить"` |
| Amazfit | Амазфит | `"Амазфит часы купить"` |
| Marshall | Маршалл | `"Маршалл колонка купить"` |

---

### Step 3 — Generate meta tags

Apply the rules from `seo--meta-tags.mdc`. For each page, produce:

**H1:**

Стратегия зависит от типа страницы:

- **Категория / серия** → UX-first: короткий, навигационный, без продающих слов и без гео
  - Шаблон: `[Категория] [Бренд]`
  - Длина: 15–45 символов
  - Без «Купить», «—», «фирменный магазин» и **без гео** (магазин работает по всему Крыму и России — гео в H1 отпугивает покупателей из других городов)
  - Примеры: `Планшеты Xiaomi`, `Смарт-часы Xiaomi и Amazfit`, `Ноутбуки Xiaomi RedmiBook`
- **Конкретная модель** → SEO-коммерческий: с «Купить», гео, брендом
  - Шаблон: `Купить [Модель] в Севастополе — GOODMi`
  - Длина: 45–70 символов
  - Одно гео-слово (Севастополь OR Крым, not both)
- No «официальный», no «авторизованный» в обоих случаях

**title:**
- Template match → fill with product/category + geo + `| GOODMi`
- Length: 50–65 characters
- GOODMi always last, after `|`
- Different geo than H1 (if H1 = Севастополь → title prefers Крым and vice versa)

**meta description:**
- Structure: `[Product] в GOODMi — [USP-1], [USP-2]. [Detail]. [CTA]. [Extra context for AI.]`
- Length: **150–250 characters**
  - Первые ~155 симв. = ключевое сообщение (USP + модели + CTA) — визуальный сниппет
  - 155–250 симв. = расширенный модельный ряд, E-E-A-T (с 2015 года), гео-уточнения — для AI Overviews, Яндекс, ChatGPT
- First sentence = direct answer to commercial query (GEO/AEO principle)
- Include 2–3 USPs from the approved list:
  - «гарантия 1 год»
  - «доставка СДЭК» / «доставка по России через СДЭК»
  - «самовывоз в Крыму» / «самовывоз в Севастополе, Симферополе, Ялте»
  - «трейд-ин»
  - «рассрочка без переплат»
  - «фирменный магазин техники Xiaomi»
  - «с 2015 года» / «более 10 лет»
- **Доставку и самовывоз указывать вместе**, если позволяет длина: «доставка СДЭК, самовывоз в Крыму» — иначе пользователь считывает только один способ получения
- CTA last: «Заказывайте онлайн» / «Оформите заказ» / «Выбирайте в каталоге»

---

### Step 4 — Self-check before output

| Check | Rule |
|---|---|
| H1 длина (категория) | 15–45 символов, без «Купить» и рекламной лексики |
| H1 длина (модель) | 45–70 символов, с «Купить» и гео |
| title length | 50–65 символов |
| description длина | 150–250 символов; первые ~155 = ключевое сообщение |
| Geo в H1 ≠ гео в title | Разные слова: Севастополь / Крым |
| H1 ≠ title | Не совпадают дословно |
| description ≠ title | Нет дословного дублирования |
| «официальный» | Отсутствует во всех трёх тегах |
| «авторизованный» | Отсутствует во всех трёх тегах |
| Цены в ₽ | Отсутствуют |
| Восклицательные знаки | Отсутствуют |
| GOODMi в title | Всегда последнее слово после `\|` |
| Минимум 2 USP | Присутствуют в description |
| CTA | Присутствует в конце description |

---

### Step 5 — Output format

**Single page:**

```
H1:          [текст]                                                    ([N] симв.)
title:       [текст]                                                    ([N] симв.)
description: [текст]                                                    ([N] симв.)
```

Затем — 1–2 строки комментария: какие ключи использованы, какие USP включены, на что обратить внимание.

**Batch (несколько страниц):**

Markdown-таблица:

| Страница | H1 | title | description |
|---|---|---|---|
| [Модель/категория] | … | … | … |

После таблицы — общий комментарий о паттернах и возможных улучшениях.

---

## E-E-A-T mapping

| Сигнал | Где ставить | Фраза |
|---|---|---|
| Experience | description (конец) | «фирменный магазин Xiaomi с 2015 года» |
| Expertise | description (середина) | «фирменный магазин техники Xiaomi» |
| Authoritativeness | description | «крупнейший магазин Xiaomi в Крыму» / «5.0 / 500+ отзывов» |
| Trustworthiness | description (USP) | «гарантия 1 год», «трейд-ин», «доставка СДЭК» |

Не используй более одного E-E-A-T сигнала одного типа — охватывай разные стороны.

---

## Brand constants (always use verbatim)

```
Store name:  GOODMi
Site:        https://goodmi.ru
Phone:       8 (800) 250-17-00
Email:       store@goodmi.ru
Since:       2015
Geo primary: Севастополь
Geo secondary: Крым
Delivery:    СДЭК по всей России
Pickup:      Севастополь (4 точки) · Симферополь · Ялта
Speciality:  фирменный магазин техники Xiaomi в Крыму
Service:     фирменный магазин техники Xiaomi в Севастополе
```

**Запрещённые слова для описания магазина:** «официальный», «авторизованный»

---

## Approved USP list

Используй только эти формулировки (дословно или с незначительной адаптацией под длину):

| USP | Краткая версия | Полная версия |
|---|---|---|
| Гарантия | «гарантия 1 год» | «гарантия производителя 1 год» |
| Доставка | «доставка СДЭК» | «доставка по России через СДЭК» |
| Трейд-ин | «трейд-ин» | «трейд-ин: сдайте старый смартфон» |
| Рассрочка | «рассрочка» | «рассрочка без переплат» |
| Сервис | «сервисный центр Xiaomi» | «фирменный магазин техники Xiaomi» |
| История | «с 2015 года» | «фирменный магазин Xiaomi с 2015 года» |
| Самовывоз | «самовывоз Севастополь» | «самовывоз в Севастополе, Симферополе, Ялте» |
| Бонусы | «бонусная программа» | «бонусная программа GOODMi» |

---

## H1/title templates by page type

| Тип страницы | H1 | title |
|---|---|---|
| Конкретная модель | `Купить [Модель] в Севастополе — GOODMi` | `Купить [Модель] в Севастополе \| GOODMi` |
| Топовая категория | `[Категория] [Бренд]` или `[Категория] [Бренд] в Севастополе` | `[Категория] [Бренд] — цена, купить в Крыму \| GOODMi` |
| Серия / линейка | `[Серия]` или `[Серия] [Бренд]` | `Купить [Серия] в Севастополе \| GOODMi` |
| Аксессуары | `[Категория] для [устройство]` или `[Категория] Xiaomi` | `[Категория] — купить в Крыму \| GOODMi` |
| С условием покупки | `Купить [Модель] в Севастополе — рассрочка, трейд-ин` | `[Модель] — рассрочка, трейд-ин, гарантия \| GOODMi` |

---

## Reference

Full meta tags standard: `seo--meta-tags.mdc`
CSS classes (if generating HTML): `goodmi-styles.css`
