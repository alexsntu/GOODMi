---
name: seo-shop-page-builder
description: Generates SEO-optimized HTML category page blocks for the GOODMi online store (official Xiaomi shop in Crimea). Use when the user provides a product category or device model and asks to create/write/generate an SEO page, category block, shop page, or HTML content for product category sections (NOT repair/service). Performs competitor research, GEO/AEO keyword collection, and outputs a ready-to-paste CS-Cart HTML block following the seo--for-shop-html-block standard.
---

# SEO Shop Page Builder — GOODMi

Generates a full CS-Cart HTML block for a **product category** page.
Always geo-bound to **Севастополь / Крым / GOODMi**. Delivery via СДЭК across Russia.

## Workflow (execute in order)

**Критическая цепочка без пропусков:** разбор запроса → **Step 1b (гарантия)** → анализ Step 2a–3b → **Step 4 запрос фото для карточек** → только затем Step 5 генерация HTML.

**Запрещено самовольно «закрывать» шаги:** агент **не имеет права** подставлять «Гарантия качества» или вариант карточек «только иконки», если пользователь **сам явно об этом не сказал** в текущем диалоге (см. Step 1b и Step 4). Пока Step 1b не закрыт — **не генерировать и не сохранять** финальный HTML блока категории (можно кратко описать план и задать вопросы). Пока Step 4 не закрыт — **не вставлять** секцию `.gm-services-grid` и **не подставлять** URL картинок с сайта или из файлов проекта самостоятельно.

### Step 1 — Parse the request

Extract from the user's message:
- **Category or model** (e.g., Xiaomi 15 Pro, Redmi Note 15 Pro, планшеты Xiaomi, RedmiBook 16)
- **Page type**: top-level category (Смартфоны, Планшеты, Ноутбуки) or specific model landing
- **URL slug** inferred (e.g., `smartfony`, `smartfony/xiaomi-15-pro`, `planshety`)
- **Competitor URLs** — if provided, flag for Step 2b analysis
- **Collapse** — whether to wrap the block in the collapse/expand mechanism:
  - If the user says **«с коллапсом»**, **«с раскрытием»**, or **«collapse»** → use collapse wrapper
  - If the user says **«без коллапса»**, **«без раскрытия»**, or **«no collapse»** → output `.gm-block` directly, no wrapper, no `<script>`
  - If **not specified** → use collapse by default

### Step 1a — If request includes popular reviews block (обновление 2026-03-25)

If the user asks to generate a **popular reviews** block (в витрине отзывов на главной, карточки с аватаром/иконками источников/датами), then before Step 2 you must ask the user for each review:

- `date` — дата отзыва
- `avatarUrl` — ссылка на аватарку пользователя
- `source` — источник отзыва (например: `Яндекс Карты`, `ВКонтакте`, `Авито`, `Отзовик`)
- `authorName` — имя пользователя
- `reviewText` — текст отзыва
- `sourceReviewUrl` — ссылка на конкретный отзыв на источнике

Also ask/ensure that output includes an **all-sources strip** (иконка + название + ссылка) с перечислением всех площадок и CTA на `https://goodmi.ru/testimonials/`.

### Step 1b — Обязательный вопрос о гарантии (перед любой генерацией)

**Если пользователь не указал гарантию в своём запросе** — задай вопрос и **остановись**, дождись ответа. **Не выдавать и не сохранять** финальный файл блока категории до ответа (исключения ниже).

> «Какая гарантия на товары в этой категории? Например: "1 год", "6 месяцев", "2 года" — или "нет гарантии", если на данный товар гарантия не распространяется.»

**Исключение — вопрос не задаём, сразу к Step 2a:** в первом сообщении пользователь уже указал срок («1 год», «6 месяцев»…), написал «нет гарантии», или **явно** разрешил дефолт, например: «гарантию не спрашивай, ставь гарантию качества» / «по гарантии пропусти».

**Правила обработки ответа:**

| Ответ пользователя | Формулировка в блоке |
|---|---|
| «1 год», «2 года», «6 месяцев» и т.п. | «Официальная гарантия [срок]» |
| «нет гарантии» | «Гарантия качества» |
| явное «пропусти» / «по умолчанию гарантия качества» (см. исключение выше) | «Гарантия качества» |

**Нельзя:** трактовать молчание или отсутствие ответа как согласие на «Гарантия качества» — при отсутствии ответа снова задать вопрос или напомнить; **не генерировать** полный блок «молча».

Полученная формулировка гарантии применяется **единообразно** во всём блоке:
- лид-абзац `.gm-intro-text`
- блок преимуществ (первый пункт `.gm-advantage-item`):
  - Есть гарантия → ссылка `https://goodmi.ru/info/guarantee/`, текст «Официальная гарантия [срок]»
  - Нет гарантии → ссылка **`https://goodmi.ru/info/vozvrat-ru/`**, текст «Гарантия качества»
- FAQ-вопрос о гарантии: при «Гарантия качества» заменить на «Можно ли вернуть [категория] в GOODMi?»

**Только после получения ответа (или сработавшего исключения выше) — переходи к Step 2a.**

### Step 2a — SERP & keyword research

Run **4 WebSearch queries in parallel**:

1. `"купить [Категория/Модель] Севастополь"` — local buy intent
2. `"купить [Модель] с доставкой по России СДЭК"` — delivery signals
3. `"[Модель] цена купить GOODMi Крым"` — commercial price + geo intent
4. `"[Модель] трейд-ин рассрочка кредит Xiaomi"` — purchase condition signals

**Goal:** Identify commercial LSI terms, FAQ seeds, trust signals, how competitors structure category pages. **Do NOT use informational queries** (reviews, specs, comparisons) — this is a shop page with commercial intent only.

### Step 2b — Competitor analysis (if URLs provided)

For each URL provided by the user — run `WebFetch` and extract:

| Parameter | What to record |
|---|---|
| H1 / H2 headings | Keywords, geo mentions |
| Models / subcategories highlighted | Names, structure |
| Purchase conditions | Guarantee, delivery, installment |
| FAQ topics | Questions they answer |
| LSI terms | Words not yet in our semantics |
| Weaknesses | Missing sections, no Schema, poor structure |

Output a Gap Analysis block **before** generating HTML:

```
## Gap Analysis: [Category]

### Конкурент 1: [domain]
- Модели: ...
- LSI-находки: ...
- Условия: ...
- Слабо: ...

### Наши преимущества GOODMi:
✅ Фирменный магазин техники Xiaomi в Севастополе
✅ Трейд-ин + бонусная программа GOODMi
✅ Самовывоз в 6 точках Крыма
⚠️ Добавить в контент: «...»
```

### Step 3 — Keyword matrix

Compile internally (do NOT output to user):

| Cluster | Keywords |
|---------|----------|
| Основной запрос | купить [Категория] Севастополь, [Категория] цена Крым |
| Доставка | доставка СДЭК по России, самовывоз Севастополь |
| Условия | гарантия 1 год, трейд-ин, рассрочка, кредит |
| LSI модели | конкретные модели из топа продаж категории |
| Доверие | с 2015 года, фирменный магазин техники Xiaomi в Крыму |

Distribute: H2 (1 ключ) → лид-абзац (2–3) → карточки (1/карточка) → FAQ (вопросы = запросы).

### Step 3b — GEO & AEO requirements

**GEO (Generative Engine Optimization)** — контент должен цитироваться AI-поисковиками (ChatGPT Search, Gemini, Perplexity):

- Лид `.gm-intro-text` обязан содержать все **5 сущностей**:
  1. Бренд: «GOODMi»
  2. Город: «Севастополь» или «в Крыму»
  3. Категория/модель: «Xiaomi 15 Pro», «планшеты Xiaomi» и т.д.
  4. Действие: «купить», «в наличии», «фирменный магазин Xiaomi»
  5. Условие покупки: «гарантия 1 год» или «доставка СДЭК»
- Каждый абзац самодостаточен — понятен без контекста (AI вырезает куски дословно)
- Преимущества конкретны: не «быстрая доставка», а «доставка по России через СДЭК»
- `LocalBusiness.description` — 2–3 предложения со специализацией + гео
- `WebPage.description` — прямой ответ на главный транзакционный запрос страницы
- `speakable.cssSelector`: `[".gm-intro-text", ".gm-faq"]` — обязательно

**AEO (Answer Engine Optimization)** — FAQ должен попадать в Featured Snippets и голосовой поиск:

- Ровно **4 вопроса**, покрывающих 4 покупательских интента:
  1. **Доставка** — «Как купить [Модель] с доставкой по России?»
  2. **Гарантия** — «Какая гарантия на технику Xiaomi в GOODMi?»
  3. **Трейд-ин** — «Как сдать старый смартфон по трейд-ин в GOODMi?»
  4. **Выбор модели** — «Чем [Модель A] отличается от [Модель B]?»
- Первое предложение каждого ответа = прямой ответ (не вводное слово, не «мы»)
- Длина ответа: **40–80 слов** — оптимум для Featured Snippet
- JSON-LD `FAQPage` содержит **те же 4 вопроса**, что и HTML (строго совпадают)
- На странице должен быть только один `FAQPage`: в JSON-LD. В HTML-секции FAQ не использовать `itemscope itemtype="https://schema.org/FAQPage"`.

### Step 4 — ОБЯЗАТЕЛЬНЫЙ запрос фото для карточек highlights grid (блокирующий шаг)

**Когда выполнять:** сразу после Step 3b — когда уже ясен список карточек (3 или 6): названия подкатегорий/моделей и порядок в сетке. **До этого шага не начинать** вёрстку блока `.gm-services-grid`.

**Обязательное действие:** задать пользователю вопрос про изображения **до** любого вывода HTML с карточками (включая JSON-LD можно готовить параллельно только если он не зависит от URL картинок — но секцию карточек в HTML не генерировать).

Вывести сообщение **строго** в таком формате:

> Для блока карточек (highlights grid) нужны изображения — **всего [N] карточек**:
>
> 1. **[Название карточки 1]** — пришлите **прямой URL** картинки (желательно фронт устройства, PNG/WebP на светлом фоне)
> 2. **[Название карточки 2]** — …
> 3. **[Название карточки 3]** — …
> _(для каждой из [N] карточек)_
>
> Пришлите **N URL в том же порядке**, что и список выше, **или** напишите **«пропустить»** — тогда все карточки с иконками (`.gm-service-card-icon`), без фото.

**Ждать ответа пользователя.** Не генерировать HTML секции с `.gm-services-grid` до получения ответа.

**Запрещено:**

- самостоятельно брать ссылки на изображения с `goodmi.ru/images/...`, CDN или карточек товаров без явного решения пользователя в диалоге;
- использовать URL из других HTML-файлов репозитория «по аналогии» без подтверждения пользователя;
- **самовольно верстать Вариант Б (иконки)** из‑за того, что пользователь не прислал фото: иконки допустимы **только** после явного **«пропустить»** / **«без фото, только иконки»** в этом диалоге.

**Исключение — Step 4 считается уже выполненным, запрос не повторять:**

- в исходном сообщении пользователь уже прислал **N URL** под **N** карточек (можно одной строкой или списком), порядок однозначен;
- или в исходном сообщении **явно** указано **«пропустить»** / **«без фото, только иконки»** для блока карточек (допустимые формулировки — только явный отказ от фото, не молчание).

**Если пользователь прислал URL фото** — использовать Вариант А карточки (`gm-service-card-img`):
```html
<img src="[URL]" alt="[Модель] — купить в GOODMi"
     class="gm-service-card-img" loading="lazy">
```
Первое фото в сетке: добавить `fetchpriority="high"` и убрать `loading="lazy"`.
Карточки в `.gm-services-grid` генерировать **без** `itemscope itemtype="https://schema.org/Product"` и без `itemprop`.

**Если пользователь написал «пропустить»** — использовать Вариант Б (эмодзи-иконка):
```html
<span class="gm-service-card-icon" aria-hidden="true">&#x1F4BB;</span>
```

**См. также:** правило `seo--for-shop-html-block.mdc` — раздел «Обязательный запрос изображений для блока карточек (highlights grid)».

### Step 5 — Generate HTML

Follow **all** rules in `seo--for-shop-html-block.mdc` strictly.

Required sections in order:
1. **JSON-LD** — `@graph`: LocalBusiness + WebPage + FAQPage (+ ItemList if listing models)
2. **Collapse wrapper** (see condition below) → `.gm-collapse-content` → `.gm-block`
3. Inside `.gm-block`:
   - **Intro**: обёртка **`<section class="gm-section gm-intro" aria-labelledby="...">`** (НЕ `<header>`!) → H2 + `.gm-intro-text` (без `.gm-stat-badge`)
   - **Highlights grid**: strictly **3 or 6** `<article>` cards; секция начинается с **`<h3 class="gm-section-title">`** + обязательный **`<p class="gm-section-lead">`** под заголовком; сетка обёрнута в `.gm-services-scroll-wrap` с кнопками `gm-scroll-btn--prev` / `gm-scroll-btn--next`
   - **Advantages**: **`<ul class="gm-advantages-list" role="list">`** (атрибут `role="list"` обязателен!); секция начинается с **`<h3 class="gm-section-title">`**; последний пункт — Яндекс-виджет рейтинга
   - **FAQ**: секция начинается с **`<h3 class="gm-section-title">`**; 4 questions (delivery, guarantee, trade-in, model choice)
   - **CTA**: dark gradient block, 2 buttons — **кнопка 1 (оранжевая): онлайн-чат JivoChat** (`jivo_api.open()`), **кнопка 2 (белая): телефон 8-800-250-17-00**
4. **collapse `<script>`** at the end — **only if collapse is enabled**

**Ссылки в пунктах `.gm-advantages-list` (обязательно):**

Каждый пункт преимуществ содержит ссылку на соответствующую страницу — `<strong>` оборачивается в `<a class="gm-advantage-link">`:

| Пункт | URL |
|---|---|
| Гарантия | `https://goodmi.ru/info/guarantee/` |
| Доставка | `https://goodmi.ru/info/delivery/` |
| Трейд-ин | `https://goodmi.ru/treyd-in-goodmi/` |
| Бонусная программа | `https://goodmi.ru/bonusnaya-programma/` |
| Кредит и рассрочка | `https://goodmi.ru/credit/` |

- Трейд-ин и бонусы можно объединить в один пункт с двумя отдельными `<a>`
- **ЗАПРЕЩЕНО**: «беспроцентная рассрочка» — только «кредит и рассрочка»
- Ссылки внутренние — без `target="_blank"`

**Яндекс-виджет рейтинга (обязательно в последнем пункте `.gm-advantages-list`):**

```html
<li class="gm-advantage-item" role="listitem">
  <span class="gm-advantage-icon" aria-hidden="true">&#x2B50;</span>
  <div class="gm-advantage-body">
    <strong>Более 500 отзывов на Яндексе</strong> &#8212; GOODMi работает с 2015 года и является крупнейшим фирменным магазином техники Xiaomi в Крыму.
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:10px;padding:8px 12px;background:#F9F9F9;border-radius:10px;border:1px solid #EEEEEE;">
      <iframe src="https://yandex.ru/sprav/widget/rating-badge/81345582117?type=rating"
              width="150" height="50" frameborder="0"
              title="Рейтинг GOODMi на Яндекс Картах" loading="lazy"
              style="display:block;border:none;flex-shrink:0;"></iframe>
      <a href="https://yandex.ru/maps/org/goodmi/81345582117/reviews/"
         target="_blank" rel="nofollow noopener"
         style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#FF6900;color:#fff;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;white-space:nowrap;"
         aria-label="Посмотреть все отзывы о GOODMi на Яндексе">Посмотреть все отзывы &#8594;</a>
    </div>
  </div>
</li>
```

**Highlights grid — обязательная HTML-обёртка со скроллером:**

```html
<div class="gm-services-scroll-wrap">
  <button class="gm-scroll-btn gm-scroll-btn--prev" aria-label="Предыдущие модели" type="button">&#8592;</button>
  <div class="gm-services-grid" role="list">
    <!-- 3 или 6 article.gm-service-card -->
  </div><!-- /.gm-services-grid -->
  <button class="gm-scroll-btn gm-scroll-btn--next" aria-label="Следующие модели" type="button">&#8594;</button>
</div><!-- /.gm-services-scroll-wrap -->
```

На десктопе кнопки скрыты (`display: none`). На мобильном (≤640px) сетка становится горизонтальным скроллером, кнопки появляются по бокам.

**JS для скроллера** — добавить в `<script>` блок в конце файла **рядом** с `gmBlockToggle`:

```javascript
/* --- Горизонтальный скроллер карточек моделей --- */
(function () {
  var wrap = document.querySelector('.gm-services-scroll-wrap');
  if (!wrap) return;
  var grid = wrap.querySelector('.gm-services-grid');
  var btnPrev = wrap.querySelector('.gm-scroll-btn--prev');
  var btnNext = wrap.querySelector('.gm-scroll-btn--next');
  function isMobile() { return window.innerWidth <= 640; }
  function getScrollAmount() {
    var card = grid.querySelector('.gm-service-card');
    return card ? card.offsetWidth + 12 : 272;
  }
  function updateArrows() {
    if (!isMobile()) return;
    var atStart = grid.scrollLeft <= 4;
    var atEnd = grid.scrollLeft >= grid.scrollWidth - grid.clientWidth - 4;
    btnPrev.style.opacity = atStart ? '0.35' : '1';
    btnNext.style.opacity = atEnd ? '0.35' : '1';
    btnPrev.style.pointerEvents = atStart ? 'none' : '';
    btnNext.style.pointerEvents = atEnd ? 'none' : '';
  }
  if (btnPrev) btnPrev.addEventListener('click', function () { grid.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' }); });
  if (btnNext) btnNext.addEventListener('click', function () { grid.scrollBy({ left: getScrollAmount(), behavior: 'smooth' }); });
  grid.addEventListener('scroll', updateArrows);
  window.addEventListener('resize', updateArrows);
  updateArrows();
}());
```

**Collapse condition (from Step 1):**
- **Collapse ON** (default): wrap `.gm-block` in `.gm-collapse-wrapper → .gm-collapse-content`, add `.gm-collapse-fade`, `.gm-collapse-trigger`, and the `<script>` block at the end
- **Collapse OFF**: output `.gm-block` directly, no `.gm-collapse-wrapper`, no `.gm-collapse-fade`, no `<script>` toggle block

**Advantages block — Layout rules:**

Блок преимуществ обёрнут в `<section class="gm-section">`. Список `<ul class="gm-advantages-list">` использует flexbox.

| Элемент | CSS (goodmi-styles.css) |
|---|---|
| `.gm-block .gm-advantages-list` | `display: flex; flex-wrap: wrap; gap: 8px; padding: 0; margin: 0` — **двойной селектор** для перекрытия CS-Cart `ul` стилей |
| `.gm-advantages-list > li` | `flex: 0 0 calc(50% - 4px); max-width: calc(50% - 4px)` — 2 в ряд на ПК |
| `.gm-advantages-list > li:last-child` | `flex: 0 0 100%; max-width: 100%` — полная ширина |
| `.gm-advantage-item` | `flex: 1` — равные высоты карточек в ряду |
| Мобильный `li`, `li:last-child` | `flex: 0 0 100%; max-width: 100%` — 1 колонка |

Список всегда содержит **ровно 5 пунктов**: 4 преимущества (гарантия, доставка, трейд-ин+бонусы, кредит) + Яндекс-виджет последним (полная ширина).

**Internal linking (обязательно):**
Each model/subcategory card **must** include a `.gm-service-link` pointing to its catalog page:
```html
<a href="https://goodmi.ru/[slug]/" class="gm-service-link"
   aria-label="Купить [Модель] в GOODMi">Выбрать модель &#8594;</a>
```
If the URL is unknown — use `href="#"` with `<!-- TODO: вставить URL -->`.

### Step 6 — Self-check before outputting

**Structure & code:**
- [ ] **Step 1b выполнен:** пользователь ответил по гарантии или в первом сообщении были срок / «нет гарантии» / явное «пропусти гарантию»; нет самовольной подстановки «Гарантия качества» без явного разрешения
- [ ] **Step 4 выполнен:** пользователь прислал N URL под N карточек, или явно написал «пропустить» (фото), или эти данные были в первом сообщении; нет самовольных URL с каталога и нет иконок без явного «пропустить»
- [ ] No `<style>` tags inside the block
- [ ] No concrete prices in ₽
- [ ] No emoji — only HTML entities
- [ ] Cards count is exactly 3 or 6
- [ ] Highlights cards do **not** use `itemscope itemtype="https://schema.org/Product"` and do not contain `itemprop` (to avoid GSC Product errors `offers/review/aggregateRating`)
- [ ] Every card has a `.gm-service-link` with a valid internal URL
- [ ] Каждый пункт `.gm-advantage-item` содержит `<a class="gm-advantage-link">` на соответствующую страницу
- [ ] Слова «беспроцентная рассрочка» отсутствуют — только «кредит и рассрочка»
- [ ] **Гарантия**: формулировка из ответа пользователя (или «Гарантия качества» если не указана) применена единообразно во всём блоке — лид, преимущества, FAQ
- [ ] Сетка обёрнута в `.gm-services-scroll-wrap` с кнопками `.gm-scroll-btn--prev` / `.gm-scroll-btn--next`
- [ ] JS скроллера добавлен в `<script>` блок рядом с `gmBlockToggle`
- [ ] Карточки с фото: первый `<img>` имеет `fetchpriority="high"` без `loading="lazy"`; остальные — `loading="lazy"`
- [ ] Карточки без фото: используется `<span class="gm-service-card-icon">`, не `<img>`
- [ ] CTA кнопка 1 (primary): открывает JivoChat через `jivo_api.open()`, `href="#"`, `onclick="if(typeof jivo_api!=='undefined'){jivo_api.open();}return false;"`
- [ ] CTA кнопка 2 (secondary): телефон `href="tel:+78002501700"`, текст «8 (800) 250-17-00»
- [ ] Email (store@goodmi.ru) в CTA **не используется** — только чат + телефон
- [ ] H1 absent (only H2 → H3 hierarchy)
- [ ] Collapse script uses `gmBlockToggle` and `data-gm-state` (not `mm-`)
- [ ] Intro-секция: обёртка `<section class="gm-section gm-intro">`, НЕ `<header>`
- [ ] Все `<h3>` заголовки секций имеют `class="gm-section-title"`
- [ ] После `<h3 class="gm-section-title">` в секции highlights grid присутствует `<p class="gm-section-lead">`
- [ ] `<ul class="gm-advantages-list">` содержит атрибут `role="list"`

**GEO checklist:**
- [ ] Лид `.gm-intro-text` содержит все 5 сущностей: бренд + город + категория/модель + действие + условие покупки
- [ ] `LocalBusiness.description` — 2–3 предложения, не просто название магазина
- [ ] JSON-LD `LocalBusiness.aggregateRating`: `ratingValue` и `reviewCount` заданы числами (без кавычек) и отсутствуют `bestRating`/`worstRating`
- [ ] `WebPage.description` — прямой ответ на главный транзакционный запрос
- [ ] `speakable.cssSelector` = `[".gm-intro-text", ".gm-faq"]`
- [ ] Преимущества конкретны (не «быстро», а «через СДЭК»; не «официально», а «гарантия 1 год»)

**AEO checklist:**
- [ ] FAQ обёрнут в `<section class="gm-faq" ...>`
- [ ] 4 вопроса покрывают 4 интента: доставка · гарантия · трейд-ин · выбор модели
- [ ] Первое предложение каждого ответа = прямой ответ
- [ ] Длина каждого ответа: 40–80 слов
- [ ] JSON-LD FAQPage содержит те же 4 вопроса, что и HTML (строго совпадают)
- [ ] В HTML нет `itemtype="https://schema.org/FAQPage"` (чтобы не дублировать JSON-LD FAQPage)

**E-E-A-T checklist:**
- [ ] **Experience**: «с 2015 года», «более 10 лет», конкретные факты
- [ ] **Expertise**: «фирменный магазин техники Xiaomi», технические детали → выгоды
- [ ] **Authoritativeness**: «крупнейший магазин Xiaomi в Крыму», рейтинг «5.0 / 500+ отзывов»
- [ ] **Trustworthiness**: гарантия 1 год, трейд-ин, бонусная программа GOODMi, реальный адрес
- [ ] Ни один E-E-A-T сигнал не выглядит как рекламный лозунг

**Виджет отзывов:**
- [ ] Последний пункт `.gm-advantages-list` содержит `<iframe>` виджет Яндекс рейтинга (ID `81345582117`)
- [ ] Рядом с виджетом — ссылка `https://yandex.ru/maps/org/goodmi/81345582117/reviews/` с `rel="nofollow noopener"`
- [ ] Статичный текст «Рейтинг 5.0» в пункте отзывов отсутствует

### Step 6b — CSS Validation (обязательно перед выводом)

Проверь каждый CSS-класс в сгенерированном HTML против `goodmi-styles.css`.
**Разрешены только классы из этого списка.** Если использован класс не из списка — сообщи пользователю и исправь.

**Белый список классов `goodmi-styles.css`:**

```
Основа:        gm-block
Intro:         gm-intro · gm-intro-text · gm-stat-badge · gm-stat-badge--mobile-2line · gm-badge-item · gm-badge-sep
Trust strip:   gm-trust-strip · gm-trust-item · gm-trust-icon · gm-trust-label
Section:       gm-section · gm-section-title · gm-section-lead
Cards:         gm-services-scroll-wrap · gm-scroll-btn · gm-scroll-btn--prev · gm-scroll-btn--next
               gm-services-grid · gm-service-card · gm-service-card-icon · gm-service-card-img · gm-service-link
Advantages:    gm-advantages-list · gm-advantage-item · gm-advantage-icon
               gm-advantage-num · gm-advantage-body · gm-advantage-link
Highlight box: gm-highlight · gm-highlight-icon · gm-highlight-body
FAQ:           gm-faq · gm-faq-item · gm-faq-question · gm-faq-answer
CTA:           gm-cta · gm-cta-text · gm-cta-actions · gm-btn · gm-btn-primary · gm-btn-secondary
HowTo:         gm-howto-steps · gm-howto-step · gm-howto-step-body
Quick links:   gm-quick-links · gm-quick-links-tags · gm-quick-link
               gm-ql-hidden · gm-ql-trigger · gm-ql-btn · gm-ql-chevron
Collapse:      gm-collapse-wrapper · gm-collapse-content · gm-collapse-fade
               gm-collapse-trigger · gm-collapse-btn · gm-collapse-chevron · gm-is-expanded
               gm-collapse-btn-text  ← JS-only селектор, CSS-правила нет, стилизация не нужна
Repair photo:  gm-repair-photo
```

**Критические правила, нарушение которых = баг:**

| Элемент | Обязательно | Запрещено |
|---|---|---|
| CTA заголовок | `<div class="gm-cta-text"><h3>` | standalone `<h2>` внутри `.gm-cta` |
| FAQ вопрос | `<p class="gm-faq-question">` | `<h4>` без этого класса |
| Заголовок карточки | `<h3>` внутри `.gm-service-card` | `<h4>` |
| Пункт преимущества | `<li class="gm-advantage-item">` + `gm-advantage-icon` + `gm-advantage-body` | `<li><strong>...</strong> текст</li>` |
| Trust label | `<strong>Жирное</strong> обычный текст` | plain текст без `<strong>` |
| Секция-обёртка | `class="gm-section"` | самодельные классы типа `gm-advantages-section` |
| Collapse state | `data-gm-state` | `data-mm-state` |
| Intro-секция | `<section class="gm-section gm-intro" aria-labelledby="...">` | `<header class="gm-intro">` |
| Trust strip элемент | `<div class="gm-trust-item" role="listitem">` | `<span class="gm-trust-item">` |
| H3 заголовки секций | `<h3 class="gm-section-title">` | `<h3>` без класса |
| Лид секции highlights | `<p class="gm-section-lead">` после `<h3>` | отсутствие `gm-section-lead` |
| Список преимуществ | `<ul class="gm-advantages-list" role="list">` | `<ul>` без `role="list"` |

**Если найден незарегистрированный класс** — перед выводом HTML сообщи:
> ⚠️ Класс `[имя]` отсутствует в `goodmi-styles.css` — заменён на `[правильный класс]`.

### Step 7 — Output

Output **only** the final HTML in a single code block. No commentary before or after.

---

## Brand constants (always use verbatim)

```
Name:        GOODMi
Free phone:  8 (800) 250-17-00  /  href="tel:+78002501700"
Email:       store@goodmi.ru
Address:     г. Севастополь, ул. Вакуленчука, 29 — ТЦ «Муссон»
Hours:       ежедневно 10:00–21:00
Site:        https://goodmi.ru
Delivery:    СДЭК по всей России
Pickup:      Севастополь (ТЦ Муссон, Остров, ТЦ Мандарин, Адм. Октябрьского)
             Симферополь (ТЦ Меганом) · Ялта (ТЦ Дом Торговли)
Rating:      5.0 / 500+ отзывов
Since:       2015
Speciality:  фирменный магазин техники Xiaomi в Крыму
```

## URL patterns

```
Смартфоны:      https://goodmi.ru/smartfony/
Xiaomi серия:   https://goodmi.ru/smartfony/xiaomi-15-pro/
Redmi серия:    https://goodmi.ru/smartfony/redmi-note-15-pro/
POCO серия:     https://goodmi.ru/smartfony/poco-x7-pro/
Планшеты:       https://goodmi.ru/planshety/
Ноутбуки:       https://goodmi.ru/noutbuki/
Смарт-часы:     https://goodmi.ru/smart-chasy/
Наушники:       https://goodmi.ru/naushniki-i-kolonki/
Пылесосы:       https://goodmi.ru/pylesos/
Аксессуары:     https://goodmi.ru/aksessuary/
Зарядные:       https://goodmi.ru/zaryadnye-ustroystva/
Телевизоры:     https://goodmi.ru/tv-foto-video/
Умный дом:      https://goodmi.ru/umnyj-dom/
```

## Highlights grid — what to include per category

**Смартфоны Xiaomi** → топ 3: Xiaomi 15 Pro, Xiaomi 15, Xiaomi 15 Ultra; топ 6 + Xiaomi 15T Pro
**Смартфоны Redmi** → Redmi Note 15 Pro Plus, Redmi Note 15 Pro, Redmi Note 15; + Redmi 15 для 6
**Смартфоны POCO** → Poco F8 Ultra, Poco X8 Pro Max, Poco X7 Pro (3 карточки)
**Планшеты** → Xiaomi Pad 7 Pro, Xiaomi Pad 7, Redmi Pad 2 (3 карточки); + Redmi Pad SE, Poco Pad для 6
**Ноутбуки** → RedmiBook Pro 16, RedmiBook 16, RedmiBook 14 (3 карточки)
**Смарт-часы** → Amazfit (флагман), Xiaomi Smart Band 10 Pro, Xiaomi Smart Band 10 (3 карточки)
**Наушники** → Marshall (флагман), JBL, Xiaomi Redmi Buds (3 карточки)
**Пылесосы** → топ робот-пылесос, вертикальный, автомобильный (3 карточки)

Choose 3 if category has 3–4 key models; choose 6 if 5+.

## Trust strip — recommended for GOODMi shop

Always use the two-level label structure — CSS `.gm-trust-label strong` bolds the first line:

```html
<span class="gm-trust-label"><strong>Гарантия 1 год</strong> на новую технику</span>
```

| Icon | `<strong>` text | Plain suffix |
|---|---|---|
| `&#x1F6E1;` | Гарантия 1 год | на новую технику |
| `&#x1F69A;` | Доставка СДЭК | по всей России |
| `&#x1F504;` | Трейд-ин | сдайте старое |
| `&#x1F4F1;` | GOODMi | с 2015 года |
| `&#x1F50D;` | Фирменный магазин | техники Xiaomi |
| `&#x1F381;` | Бонусная программа | GOODMi |

## Reference

Full HTML structure standard: `seo--for-shop-html-block.mdc`
CSS file: `goodmi-styles.css`
