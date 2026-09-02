---
name: seo-shop-page-builder-multibrand
description: "[Multibrand — все бренды ассортимента] Generates SEO-optimized HTML category page blocks for the GOODMi online store — a multibrand gadgets & electronics store in Crimea, no single brand positioned as default. Base structure identical to v2 (FAQ in Block 1 always visible, 5 questions, sameAs in LocalBusiness JSON-LD, optional HowTo JSON-LD, concrete-numbers requirement). Key differences from v2: brand composition of every page comes from a per-page decision (Xiaomi included, never assumed by default), FAQ/lead copy templates are brand-neutral by default, E-E-A-T relies on review-count (7500+) and authenticity signals instead of 'фирменный/официальный магазин Xiaomi' claims, sameAs expanded to all review sources. Use when the user provides a product category or device model and asks to create/write/generate an SEO page, category block, or HTML content for product category sections, across any brand in the GOODMi assortment."
---

# SEO Shop Page Builder Multibrand — GOODMi

> **Multibrand vs v2:** структура блока (Блок 1 + FAQ всегда видим, Блок 2 коллапс, sameAs, HowTo, конкретные числа) — без изменений, см. v2. **Отличие:** GOODMi — мультибренд-магазин гаджетов и умной техники, Xiaomi — один из брендов ассортимента, не бренд по умолчанию. Практические следствия:
> 1. Состав брендов страницы определяется тем же блокирующим вопросом, что и в `seo-meta-builder-multibrand` (Step 2a-multi запускается для каждого бренда страницы, включая Xiaomi — без исключений в таблице «официальный магазин-блокер»)
> 2. FAQ-вопросы, лид-абзац и advantages-list — брендово-нейтральные по умолчанию, бренд добавляется только если зафиксирован для конкретной страницы
> 3. E-E-A-T опирается на 7500+ оценок и отзывов (6 точек Яндекс Карт + ВКонтакте + Avito + Отзовик) и «100% оригинальная техника» вместо «фирменный/официальный магазин Xiaomi» — эти слова запрещены для магазина и для любого бренда без исключения
> 4. `sameAs` расширен на все источники отзывов, не только один Яндекс-профиль
> 5. Highlights grid — модели по факту актуального ассортимента, не захардкожены под Xiaomi-эру (см. раздел в конце файла)

Generates a full CS-Cart HTML block for a **product category** page — a multibrand gadgets & electronics store.
Always geo-bound to **Севастополь / Крым / GOODMi**. Delivery via СДЭК across Russia.

---

## Workflow (execute in order)

**Критическая цепочка без пропусков:** разбор запроса → **Step 1b (гарантия)** → анализ Step 2a–3c → **Step 4 запрос фото для карточек** → только затем Step 5 генерация HTML.

**Запрещено самовольно «закрывать» шаги:** агент **не имеет права** подставлять «Гарантия качества» или вариант карточек «только иконки», если пользователь **сам явно об этом не сказал** в текущем диалоге. Пока Step 1b не закрыт — **не генерировать** финальный HTML. Пока Step 4 не закрыт — **не вставлять** секцию `.gm-services-grid`.

---

### Step 1 — Parse the request

Extract from the user's message:
- **Category or model** (e.g., Xiaomi 15 Pro, Redmi Note 15 Pro, планшеты Xiaomi, RedmiBook 16)
- **Page type**: top-level category or specific model landing
- **URL slug** inferred (e.g., `smartfony`, `planshety`)
- **Competitor URLs** — если предоставлены, отметить для Step 2b
- **Collapse** — управление двухблочной структурой:
  - Стандарт (по умолчанию): **Блок 1 (всегда видим)** = intro + trust strip + карточки + **FAQ**; **Блок 2 (коллапс)** = преимущества + CTA
  - Если пользователь говорит **«без коллапса»** или **«no collapse»** → оба блока выводятся как обычные `<div class="gm-block">` без `.gm-collapse-wrapper` и `<script>` toggle
- **HowTo** — нужна ли разметка для «Как купить» / «Как сдать по трейд-ин» (см. Step 3c)

> **[v2] FAQ всегда в Блоке 1.** Это отличие от v1, где FAQ был в Блоке 2 за коллапсом. Причина: поисковые AI-системы (Яндекс Нейро, Google AI Overviews) активно берут FAQ как источник нейроответов; CSS-скрытый контент получает меньший индексационный вес. Если пользователь явно просит вернуть FAQ в коллапс — выполнить, зафиксировать.

---

### Step 1a — Блок отзывов на главной (обновление 2026-03-25)

Если пользователь просит сгенерировать блок **витрины отзывов**, перед Step 2 запросить для каждого отзыва:
- `date`, `avatarUrl`, `source`, `authorName`, `reviewText`, `sourceReviewUrl`

Обеспечить **all-sources strip** (иконка + название + ссылка на `https://goodmi.ru/testimonials/`).

---

### Step 1b — Обязательный вопрос о гарантии

**Если пользователь не указал гарантию** — задай вопрос и **остановись**:

> «Какая гарантия на товары в этой категории? Например: "1 год", "6 месяцев", "2 года" — или "нет гарантии".»

**Исключение:** пользователь уже указал срок в первом сообщении, написал «нет гарантии», или явно разрешил дефолт.

| Ответ пользователя | Формулировка в блоке |
|---|---|
| «1 год», «2 года», «6 месяцев» | «Официальная гарантия [срок]» |
| «нет гарантии» | «Гарантия качества» |

Применять **единообразно**: лид-абзац, первый пункт преимуществ, FAQ вопрос 4 о гарантии.

---

### Step 2a — SERP & keyword research

Run **5 WebSearch queries in parallel**:

1. `"купить [Категория/Модель] Севастополь"` — local buy intent
2. `"купить [Модель] с доставкой по России СДЭК"` — delivery signals
3. `"[Модель] цена купить GOODMi Крым"` — commercial price + geo intent
4. `"[Модель] трейд-ин рассрочка кредит"` — purchase condition signals
5. `"купить [кириллический вариант бренда] [Категория] Севастополь"` — только если на странице зафиксирован бренд с распространённым кириллическим написанием (см. таблицу Step 2c); при нескольких брендах или смешанном ассортименте — пропустить этот запрос

**Проверка брендов страницы — обязательна перед запуском:**

Для каждого бренда, зафиксированного на странице (включая Xiaomi, если он один из них) — **до генерации HTML** выполнить Step 2a-multi.

### Step 2a-multi — SERP по брендам страницы

**Когда:** всегда — выполняется для каждого бренда, зафиксированного на странице, без исключения для Xiaomi.

Для каждого бренда страницы — **2 WebSearch параллельно**:
1. `"купить [Бренд] [категория] Севастополь"` — конкурентная среда по бренду
2. `"[Бренд] официальный магазин Россия"` — есть ли у бренда свой интернет-магазин?

При 2 вторичных брендах — 4 запроса параллельно. При 3 — 6 запросов.

**Результат — таблица по каждому бренду:**

```
| Бренд | Офиц. магазин в топ-10 | Агрегаторов | GOODMi в топ-10 | Флаг |
|---|---|---|---|---|
| [Бренд] | да/нет → [домен] | N | да/нет | 🎯 / 👁 / — |
```

**Правила флага (применять перед Step 3 и Step 3b):**

| Условие | Флаг | Что делать с брендом |
|---|---|---|
| Есть офиц. магазин бренда в топ-10 | **—** | Бренд → description + H1 как ассортиментный факт, **не в title** |
| Нет офиц. магазина + 0–1 агрегатор | **🎯** | Бренд → кандидат в title |
| Нет офиц. магазина + 2–3 агрегатора | **👁** | Бренд → description |
| Нет офиц. магазина + 4+ агрегаторов | **—** | Не в title |

Известные официальные магазины брендов GOODMi (автоматический флаг —):

| Бренд | Официальный магазин |
|---|---|
| Xiaomi/Redmi/POCO | `mi-shop.com`, `ru-mi.com`, `mi-xx.ru`, `mi-life.ru` |
| Dreame | `ru.dreametech.com`, `dreametech.store` |
| Roborock | `roborock-market.ru`, `roborock.market` |
| Marshall | `marshallheadphones.com/ru` |
| Amazfit/Zepp | `zepp.com` |
| JBL | `ru.jbl.com` |

Xiaomi — не исключение из этой таблицы: монобренд-магазины Xiaomi обычно держат топ по нацзапросам, но не geo-таргетированы на Севастополь/Крым — по региональным запросам проверять Step 2a-multi как для любого бренда.

Если бренд есть в этом списке → SERP-запрос по нему можно не запускать, сразу присваивать флаг **—** и экономить запросы.

---

### Step 2c — Кириллические написания бренда

Если среди брендов, зафиксированных для страницы (Step 2a-multi), есть Xiaomi / Redmi / POCO / Amazfit — **обязательно** проверять кириллические LSI-варианты. Если ни один из брендов страницы не входит в этот список (или ассортимент смешанный/меняющийся) — весь раздел пропускается, кириллика не добавляется.

| Латиница | Кириллица | Альтернативные |
|---|---|---|
| Xiaomi | Сяоми | Ксяоми, Ксиаоми |
| Redmi | Редми | Редмi |
| POCO | Поко | — |
| Amazfit | Амазфит | Амейзфит |

**Правила использования:**
1. **JSON-LD `alternateName`** — массив с кириллическими вариантами
2. **Лид `.gm-intro-text`** — один раз в скобках: «Xiaomi (Сяоми)»
3. **Один вопрос FAQ** — переформулировать с кириллическим брендом
4. **Одна карточка модели** — добавить «Сяоми» / «Редми» / «Поко» в описание (только одной)

---

### Step 2b — Competitor analysis (if URLs provided)

WebFetch каждого URL. Извлечь:

| Parameter | What to record |
|---|---|
| H1 / H2 headings | Keywords, geo |
| Models highlighted | Names, structure |
| Purchase conditions | Guarantee, delivery |
| FAQ topics | Questions they answer |
| LSI terms | Words not yet in our semantics |
| Weaknesses | Missing sections, no Schema |

Output Gap Analysis block перед генерацией HTML.

---

### Step 3 — Keyword matrix

Compile internally (NOT output to user):

| Cluster | Keywords |
|---------|----------|
| Основной запрос | купить [Категория] Севастополь, [Категория] цена Крым |
| Доставка | доставка СДЭК по России, самовывоз Севастополь |
| Условия | гарантия 1 год, трейд-ин, кредит |
| LSI модели | конкретные модели из топа продаж |
| Доверие | с 2016 года, магазин гаджетов и умной техники в Крыму, 7500+ оценок и отзывов |
| **Кириллика** | **купить Сяоми [Категория], Редми [Модель]** |

Распределить: H2 (1 ключ) → лид (2–3, вкл. кириллику в скобках) → одна карточка (кириллика) → FAQ вопрос 1 или 2 (кириллика) → JSON-LD alternateName.

---

### Step 3b — GEO & AEO requirements

**GEO (Generative Engine Optimization)** — контент должен цитироваться AI-поисковиками:

- Лид `.gm-intro-text` обязан содержать все **5 сущностей**:
  1. Бренд: «GOODMi»
  2. Город: «Севастополь» или «в Крыму»
  3. Категория/модель
  4. Действие: «купить», «в наличии», «выбор из нескольких брендов»
  5. Условие покупки: «гарантия 1 год» или «доставка СДЭК»
- **[v2] Конкретные числа** в лиде — обязательно минимум одно:
  - «6 точек самовывоза» / «4 точки в Севастополе»
  - «с 2016 года» / «более 9 лет»
  - «365 дней гарантии» (вместо или вместе с «1 год»)
  - «доставка за 2–7 дней СДЭК» (если подтверждено)
- Каждый абзац самодостаточен — понятен без контекста
- Преимущества конкретны: не «быстрая доставка», а «доставка по России через СДЭК»
- `speakable.cssSelector`: `[".gm-intro-text", ".gm-faq"]` — обязательно
- `LocalBusiness.description` — 2–3 предложения со специализацией + гео + конкретный факт
- `WebPage.description` — прямой ответ на главный транзакционный запрос, НЕ начинать с «GOODMi»/«Мы»

**sameAs в LocalBusiness — обязательно, полный набор источников:**

```json
"sameAs": [
  "https://yandex.ru/maps/org/goodmi/81345582117/",
  "https://yandex.ru/maps/org/goodmi/219323553091/",
  "https://yandex.ru/maps/org/goodmi/41033084263/",
  "https://yandex.ru/maps/org/goodmi/95861137013/",
  "https://yandex.ru/maps/org/goodmi/183196216973/",
  "https://yandex.ru/maps/org/goodmi/63307304488/",
  "https://vk.ru/reviews-126411469",
  "https://www.avito.ru/brands/i155162702/all?sellerId=557ad28f61641d9114ad5ca6531fa735",
  "https://otzovik.com/reviews/mi92_ru-internet-magazin_tehniki_xiaomi"
]
```

`aggregateRating.reviewCount` — только по flagship-профилю `81345582117` (не сумма по всем источникам, см. правило в `seo-meta-builder-multibrand`).

**AEO (Answer Engine Optimization)** — FAQ в Блоке 1 (всегда видим):

- Ровно **5 вопросов**, покрывающих 5 интентов:
  1. **Информационный** — «Что важно учесть при выборе [категория]?» (+ бренд в скобках, только если 1–2 бренда зафиксированы для страницы)
  2. **Сравнение моделей** — «Чем [Модель A] отличается от [Модель B]?»
  3. **Доставка** — «Как купить [категория] с доставкой по России?»
  4. **Гарантия** — «Какая гарантия на технику в GOODMi?»
  5. **Трейд-ин** — «Как сдать старый смартфон по трейд-ин в GOODMi?»
- Первое предложение каждого ответа = прямой ответ
- Длина ответа: **40–80 слов** — оптимум для Featured Snippet и нейроответа
- Вопрос 1 (информационный) и вопрос 2 (сравнение) — **приоритет для нейроответов** (AI-системы берут их чаще коммерческих)
- Один вопрос из пяти переформулировать с кириллическим написанием бренда
- В HTML-секции FAQ **не использовать** `itemscope`, `itemtype`, `itemprop` — FAQPage разметка не применяется

> **Почему информационный вопрос первым:** AI-системы чаще цитируют начало списка FAQ. Информационный вопрос («как выбрать») попадает в нейроответ гораздо чаще транзакционного («как купить»).

---

### [v2] Step 3c — HowTo JSON-LD (опционально)

**Когда добавлять:** пользователь запросил HowTo в Step 1 ИЛИ FAQ содержит ответ о процедуре покупки или трейд-ин.

HowTo-разметка помогает AI-поисковикам цитировать пошаговые инструкции. Добавляется в `[slug]-jsonld.html` как отдельный `<script>`.

**Триггер A — «Как купить с доставкой»:**

```json
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "Как купить [категория] с доставкой по России",
  "description": "Покупка [категория] в GOODMi с доставкой СДЭК или самовывозом в Крыму.",
  "step": [
    {
      "@type": "HowToStep",
      "position": 1,
      "name": "Выберите модель",
      "text": "Откройте каталог GOODMi на goodmi.ru, выберите нужную модель из наличия."
    },
    {
      "@type": "HowToStep",
      "position": 2,
      "name": "Оформите заказ",
      "text": "Добавьте товар в корзину, укажите адрес доставки СДЭК или выберите самовывоз в Севастополе, Симферополе или Ялте."
    },
    {
      "@type": "HowToStep",
      "position": 3,
      "name": "Получите товар",
      "text": "Доставка СДЭК по всей России — 2–7 дней. Самовывоз в 6 точках Крыма ежедневно с 10:00 до 21:00."
    }
  ]
}
```

**Триггер B — «Как сдать по трейд-ин»:**

```json
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "Как сдать старый смартфон по трейд-ин в GOODMi",
  "description": "Трейд-ин в GOODMi: оцените старое устройство и получите скидку на новый гаджет.",
  "step": [
    {
      "@type": "HowToStep",
      "position": 1,
      "name": "Оцените устройство",
      "text": "Принесите старый смартфон в любую точку GOODMi в Севастополе, Симферополе или Ялте — оценка бесплатна."
    },
    {
      "@type": "HowToStep",
      "position": 2,
      "name": "Выберите новое устройство",
      "text": "Получите скидку на любой товар GOODMi в размере оценочной стоимости сданного устройства."
    },
    {
      "@type": "HowToStep",
      "position": 3,
      "name": "Оформите покупку",
      "text": "Доплатите разницу наличными или картой, получите новое устройство с гарантией 1 год."
    }
  ]
}
```

Адаптировать `name` и `step[].text` под конкретную категорию. Добавить в `[slug]-jsonld.html` после остальных schema-блоков.

---

### Step 4 — ОБЯЗАТЕЛЬНЫЙ запрос фото для карточек (блокирующий шаг)

**Когда выполнять:** сразу после Step 3c — когда ясен список карточек (3 или 6). **До этого шага не начинать** вёрстку `.gm-services-grid`.

Вывести сообщение **строго** в таком формате:

> Для блока карточек (highlights grid) нужны изображения — **всего [N] карточек**:
>
> 1. **[Название карточки 1]** — пришлите **прямой URL** картинки
> 2. **[Название карточки 2]** — …
>
> Пришлите **N URL в том же порядке**, или напишите **«пропустить»** — тогда все карточки с иконками.

**Запрещено:**
- Самостоятельно брать ссылки с `goodmi.ru/images/...` без явного решения пользователя
- Использовать URL из других файлов репозитория без подтверждения
- Верстать иконки без явного «пропустить»

**Если пользователь прислал URL:** использовать Вариант А (`gm-service-card-img`). Первое фото: `fetchpriority="high"` без `loading="lazy"`. Остальные: `loading="lazy"`.

**Если «пропустить»:** использовать Вариант Б (`gm-service-card-icon` с SVG-символом из BMP-таблицы).

Карточки в `.gm-services-grid` — **без** `itemscope itemtype="https://schema.org/Product"` и без `itemprop`.

---

### Step 5 — Generate HTML

Следовать всем правилам `seo--for-shop-html-block.mdc`.

**Два файла на каждую страницу:**

| Файл | Куда в CS-Cart | Что содержит |
|---|---|---|
| `[slug].html` | Wysiwyg-описание категории | Только HTML (Блок 1 + Блок 2 + `<script>` collapse) |
| `[slug]-jsonld.html` | Дизайн → Макеты → HTML-блок | Только JSON-LD (отдельные `<script>` per type, без `@graph`, без `WebPage`) |

---

#### [v2] Структура Блока 1 — SEO-блок + FAQ (всегда видимый)

```html
<!-- Блок 1: Всегда видимый — intro + trust strip + карточки + FAQ -->
<div class="gm-block" itemscope itemtype="https://schema.org/WebPage">

  <section class="gm-section gm-intro" aria-labelledby="[id]-heading">
    <!-- H2 + .gm-intro-text (5 сущностей + конкретное число) -->
  </section>

  <div class="gm-trust-strip" role="list" aria-label="Преимущества GOODMi">
    <!-- 4 trust items -->
  </div>

  <section class="gm-section" aria-labelledby="models-heading">
    <!-- h3 + gm-section-lead + gm-services-scroll-wrap → gm-services-grid (3 или 6 карточек) -->
  </section>

  <!-- FAQ в Блоке 1 — всегда виден, не за коллапсом -->
  <section class="gm-faq" aria-labelledby="faq-heading">
    <h3 id="faq-heading" class="gm-section-title">Вопросы о [категория]</h3>

    <!-- Вопрос 1: информационный — бренд в скобках только если 1-2 зафиксированы для страницы -->
    <div class="gm-faq-item">
      <p class="gm-faq-question">Что важно учесть при выборе [категория]?</p>
      <div class="gm-faq-answer">
        <p>[Прямой ответ: 40–80 слов. Ключевые параметры выбора — без воды.]</p>
      </div>
    </div>

    <!-- Вопрос 2: сравнение моделей (с кириллическим вариантом если нужно) -->
    <div class="gm-faq-item">
      <p class="gm-faq-question">Чем [Модель A] отличается от [Модель B]?</p>
      <div class="gm-faq-answer">
        <p>[Прямой ответ: конкретные технические отличия.]</p>
      </div>
    </div>

    <!-- Вопрос 3: доставка -->
    <div class="gm-faq-item">
      <p class="gm-faq-question">Как купить [категория] с доставкой по России?</p>
      <div class="gm-faq-answer">
        <p>[Прямой ответ: оформить на goodmi.ru, доставка СДЭК, самовывоз в 6 точках Крыма.]</p>
      </div>
    </div>

    <!-- Вопрос 4: гарантия -->
    <div class="gm-faq-item">
      <p class="gm-faq-question">Какая гарантия на [категория] в GOODMi?</p>
      <div class="gm-faq-answer">
        <p>[Прямой ответ с формулировкой из Step 1b.]</p>
      </div>
    </div>

    <!-- Вопрос 5: трейд-ин -->
    <div class="gm-faq-item">
      <p class="gm-faq-question">Как сдать старый смартфон по трейд-ин в GOODMi?</p>
      <div class="gm-faq-answer">
        <p>[Прямой ответ: принести в точку → оценка → скидка на новое.]</p>
      </div>
    </div>

  </section>

</div><!-- /.gm-block (SEO + FAQ: всегда видимы) -->
```

> **Правило:** все 5 вопросов FAQ — целиком в Блоке 1. Не переносить FAQ в Блок 2. FAQ не использует `itemscope`, `itemtype`, `itemprop`.

---

#### [v2] Структура Блока 2 — коллапс (преимущества + CTA)

```html
<!-- Блок 2: Коллапс — только преимущества + CTA (FAQ перенесён в Блок 1) -->
<div class="gm-collapse-wrapper">
  <div class="gm-collapse-content">
    <div class="gm-block">  <!-- без itemscope! -->

      <section class="gm-section" aria-labelledby="advantages-heading">
        <h3 id="advantages-heading" class="gm-section-title">Почему выбирают GOODMi</h3>
        <ul class="gm-advantages-list" role="list">
          <!-- 5 пунктов: гарантия + доставка + трейд-ин+бонусы + кредит + Яндекс-виджет -->
        </ul>
      </section>

      <!-- CTA -->
      <div class="gm-cta">
        <div class="gm-cta-text">
          <h3>Нужна помощь с выбором?</h3>
          <p>Наши консультанты помогут подобрать [категория] под ваши задачи и бюджет.</p>
        </div>
        <div class="gm-cta-actions">
          <a href="#" class="gm-btn gm-btn-primary"
             onclick="if(typeof jivo_api!=='undefined'){jivo_api.open();}return false;"
             aria-label="Написать в чат GOODMi">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M2 2h12v8H9.5l-2.5 3V10H2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Написать в чат
          </a>
          <a href="tel:+78002501700" class="gm-btn gm-btn-secondary"
             aria-label="Позвонить в GOODMi">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 2h3l1.5 3.5-1.5 1a8 8 0 0 0 3.5 3.5l1-1.5L14 10v3a1 1 0 0 1-1 1C5.5 14 2 7.5 2 3a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            8 (800) 250-17-00
          </a>
        </div>
      </div>

    </div><!-- /.gm-block -->
    <div class="gm-collapse-fade"></div>
  </div><!-- /.gm-collapse-content -->

  <div class="gm-collapse-trigger">
    <button class="gm-collapse-btn" onclick="gmBlockToggle(this)" aria-expanded="false">
      <span class="gm-collapse-btn-text">Читать подробнее</span>
      <span class="gm-collapse-chevron" aria-hidden="true">
        <svg width="14" height="9" viewBox="0 0 14 9" fill="none">
          <path d="M1 1.5L7 7.5L13 1.5" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </button>
  </div>
</div><!-- /.gm-collapse-wrapper -->

<script>
(function () {
  var allContents = document.querySelectorAll('.gm-collapse-content');
  var content = allContents[allContents.length - 1];
  if (!content) return;
  content.style.maxHeight = '400px';
  content.dataset.gmState = 'collapsed';
  var fade = content.querySelector('.gm-collapse-fade');
  if (fade) fade.style.opacity = '1';
})();

function gmBlockToggle(btn) {
  var trigger = btn.parentElement;
  var content = trigger.previousElementSibling;
  var fade    = content.querySelector('.gm-collapse-fade');
  var label   = btn.querySelector('.gm-collapse-btn-text');
  var wrapper = trigger.parentElement;

  if (content.dataset.gmState !== 'expanded') {
    content.style.transition = 'max-height 0.55s cubic-bezier(0.4, 0, 0.2, 1)';
    content.style.maxHeight  = content.scrollHeight + 'px';
    content.dataset.gmState  = 'expanded';
    if (fade) { fade.style.transition = 'opacity 0.25s ease'; fade.style.opacity = '0'; }
    btn.classList.add('gm-is-expanded');
    btn.setAttribute('aria-expanded', 'true');
    if (label) label.textContent = 'Скрыть';
    setTimeout(function () {
      if (content.dataset.gmState === 'expanded') {
        content.style.transition = '';
        content.style.maxHeight  = 'none';
      }
    }, 580);
  } else {
    content.style.transition = '';
    content.style.maxHeight  = content.scrollHeight + 'px';
    content.dataset.gmState  = 'collapsing';
    void content.offsetHeight;
    content.style.transition = 'max-height 0.55s cubic-bezier(0.4, 0, 0.2, 1)';
    content.style.maxHeight  = '400px';
    content.dataset.gmState  = 'collapsed';
    if (fade) { fade.style.transition = 'opacity 0.3s ease 0.2s'; fade.style.opacity = '1'; }
    btn.classList.remove('gm-is-expanded');
    btn.setAttribute('aria-expanded', 'false');
    if (label) label.textContent = 'Читать подробнее';
    setTimeout(function () {
      wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 120);
  }
}
</script>
```

> **Важно:** этот `<script>` идёт строго после `</div><!-- /.gm-collapse-wrapper -->` в конце файла `[slug].html`. Использовать этот шаблон дословно — не упрощать. Ранее использовавшийся вариант через `classList.contains('gm-is-expanded')` не работает в CS-Cart.

---

#### Блок 1 — детальные правила (без изменений v1)

Содержит строго:
- **Intro**: `<section class="gm-section gm-intro" aria-labelledby="...">` → H2 + `.gm-intro-text`
- **Trust strip**: `<div class="gm-trust-strip" role="list">` → 4 иконки с подписями
- **Highlights grid**: строго **3 или 6** `<article>` карточек; `<h3 class="gm-section-title">` + `<p class="gm-section-lead">`; сетка в `.gm-services-scroll-wrap` с кнопками `gm-scroll-btn--prev` / `gm-scroll-btn--next`
- **[v2] FAQ**: `<section class="gm-faq">` → 5 вопросов (см. структуру выше)

> **Правило карточек:** 3 или 6 — целиком в Блоке 1. Не разрезать. Внутренние ссылки в карточках обязательны.

**Highlights grid — HTML-обёртка со скроллером:**

```html
<div class="gm-services-scroll-wrap">
  <button class="gm-scroll-btn gm-scroll-btn--prev" aria-label="Предыдущие модели" type="button">&#8592;</button>
  <div class="gm-services-grid" role="list">
    <!-- 3 или 6 article.gm-service-card -->
  </div>
  <button class="gm-scroll-btn gm-scroll-btn--next" aria-label="Следующие модели" type="button">&#8594;</button>
</div>
```

**JS для скроллера** — в `<script>` блоке в конце файла рядом с `gmBlockToggle`:

```javascript
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

#### Блок 2 — детальные правила (обновлены для v2)

Содержит строго:
- **Advantages**: `<ul class="gm-advantages-list" role="list">` → ровно 5 пунктов (4 преимущества + Яндекс-виджет)
- **CTA**: dark gradient block, 2 кнопки — чат (JivoChat) + телефон
- **FAQ отсутствует** — он в Блоке 1

**Последний пункт `.gm-advantages-list` — Яндекс-виджет:**

```html
<li class="gm-advantage-item" role="listitem">
  <span class="gm-advantage-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><polygon points="10,2 12.5,7.5 18.5,8.2 14,12.5 15.5,18.5 10,15.5 4.5,18.5 6,12.5 1.5,8.2 7.5,7.5"/></svg></span>
  <div class="gm-advantage-body">
    <strong>7500+ оценок и отзывов</strong> &#8211; GOODMi работает с 2016 года, магазин гаджетов и умной техники в Крыму.
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

**Ссылки в пунктах `.gm-advantages-list`:**

| Пункт | URL |
|---|---|
| Гарантия | `https://goodmi.ru/info/guarantee/` |
| Доставка | `https://goodmi.ru/info/delivery/` |
| Трейд-ин | `https://goodmi.ru/treyd-in-goodmi/` |
| Бонусная программа | `https://goodmi.ru/bonusnaya-programma/` |
| Кредит и рассрочка | `https://goodmi.ru/credit/` |

**Запрещено:** «беспроцентная рассрочка» — только «кредит и рассрочка».

#### [v2] JSON-LD — шаблон LocalBusiness с sameAs

В `[slug]-jsonld.html`:

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "GOODMi",
  "url": "https://goodmi.ru/[slug]/",
  "telephone": "+78002501700",
  "email": "store@goodmi.ru",
  "description": "GOODMi — магазин гаджетов и умной техники в Крыму с 2016 года, 7500+ оценок и отзывов. Самовывоз в 6 точках: 4 в Севастополе, Симферополь, Ялта. Доставка СДЭК по всей России, трейд-ин, покупка в кредит.",
  "sameAs": [
    "https://yandex.ru/maps/org/goodmi/81345582117/",
    "https://yandex.ru/maps/org/goodmi/219323553091/",
    "https://yandex.ru/maps/org/goodmi/41033084263/",
    "https://yandex.ru/maps/org/goodmi/95861137013/",
    "https://yandex.ru/maps/org/goodmi/183196216973/",
    "https://yandex.ru/maps/org/goodmi/63307304488/",
    "https://vk.ru/reviews-126411469",
    "https://www.avito.ru/brands/i155162702/all?sellerId=557ad28f61641d9114ad5ca6531fa735",
    "https://otzovik.com/reviews/mi92_ru-internet-magazin_tehniki_xiaomi"
  ],
  "alternateName": ["Гаджеты GOODMi", "[Категория] Крым", "Купить [Категория] Севастополь"],
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "ул. Вакуленчука, 29 — ТЦ «Муссон»",
    "addressLocality": "Севастополь",
    "addressRegion": "Республика Крым",
    "addressCountry": "RU"
  },
  "openingHours": "Mo-Su 10:00-21:00",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": 5.0,
    "reviewCount": 2000
  },
  "areaServed": {
    "@type": "GeoCircle",
    "geoMidpoint": {
      "@type": "GeoCoordinates",
      "latitude": 44.616,
      "longitude": 33.525
    },
    "geoRadius": "300000"
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "[H2 заголовок категории]",
  "description": "[WebPage.description — прямой ответ, НЕ начинать с GOODMi/Мы, содержит конкретное число]",
  "url": "https://goodmi.ru/[slug]/",
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Главная", "item": "https://goodmi.ru/" },
      { "@type": "ListItem", "position": 2, "name": "[Категория]", "item": "https://goodmi.ru/[slug]/" }
    ]
  },
  "speakable": {
    "@type": "SpeakableSpecification",
    "cssSelector": [".gm-intro-text", ".gm-faq"]
  }
}
</script>

<!-- [v2] HowTo — добавить если Step 3c активирован -->
<!-- <script type="application/ld+json">{ "@context": ..., "@type": "HowTo", ... }</script> -->
```

---

### Step 6 — Self-check before outputting

**Два файла:**
- [ ] Сгенерировано **два файла**: `[slug].html` и `[slug]-jsonld.html`
- [ ] В `[slug].html` **нет** `<script type="application/ld+json">`
- [ ] В `[slug]-jsonld.html` нет `@graph`
- [ ] В `[slug]-jsonld.html` **нет** отдельного `WebPage` блока — только `LocalBusiness` и `WebPage` в рамках разрешённых типов

**[v2] Двухблочная структура — обновлено:**
- [ ] **Блок 1** содержит: intro-секция + trust strip + highlights grid + **FAQ (5 вопросов)**
- [ ] **Блок 2** содержит: преимущества + CTA — **без FAQ**
- [ ] `itemscope itemtype="https://schema.org/WebPage"` только на `.gm-block` Блока 1
- [ ] В Блоке 2 `.gm-block` **нет** `itemscope`/`itemtype`/`itemprop`
- [ ] При collapse ON — Блок 2 обёрнут в `.gm-collapse-wrapper`, Блок 1 — нет
- [ ] Текст кнопки: `«Читать подробнее»` / JS восстанавливает при сворачивании

**Structure & code:**
- [ ] Step 1b выполнен: пользователь ответил по гарантии
- [ ] Step 4 выполнен: пользователь прислал URL или написал «пропустить»
- [ ] Нет `<style>` тегов
- [ ] Нет цен в ₽
- [ ] Иконки — только inline SVG (не HTML-сущности Unicode выше U+00FF)
- [ ] Карточек ровно 3 или 6
- [ ] Карточки без `itemscope itemtype="https://schema.org/Product"` и без `itemprop`
- [ ] Каждая карточка имеет `.gm-service-link` с валидным URL
- [ ] Каждый `.gm-advantage-item` содержит `<a class="gm-advantage-link">` на соответствующую страницу
- [ ] Слова «беспроцентная рассрочка» отсутствуют
- [ ] Гарантия из Step 1b применена единообразно: лид + преимущества + FAQ вопрос 4
- [ ] Сетка в `.gm-services-scroll-wrap` с кнопками
- [ ] JS скроллера добавлен рядом с `gmBlockToggle`
- [ ] Карточки с фото: первый `<img>` имеет `fetchpriority="high"` без `loading="lazy"`
- [ ] CTA кнопка 1: JivoChat `onclick="if(typeof jivo_api!=='undefined'){jivo_api.open();}return false;"`
- [ ] CTA кнопка 2: `href="tel:+78002501700"`
- [ ] H1 absent (только H2 → H3 иерархия)
- [ ] Intro-секция: `<section class="gm-section gm-intro">`, не `<header>`

**[v2] GEO checklist — обновлено:**
- [ ] Лид `.gm-intro-text` содержит все 5 сущностей: бренд + город + категория + действие + условие
- [ ] **[v2] Лид содержит конкретное число** (6 точек / с 2016 года / 365 дней / и т.д.)
- [ ] `LocalBusiness.description` — 2–3 предложения, не просто название магазина
- [ ] `LocalBusiness.sameAs` содержит все 9 источников (6 точек Яндекс Карт + VK + Avito + Отзовик)
- [ ] `JSON-LD aggregateRating`: `ratingValue` и `reviewCount` — числа без кавычек
- [ ] `WebPage.description` — прямой ответ, **НЕ начинается с «GOODMi»/«Мы»**
- [ ] **[v2] `WebPage.description` содержит конкретное число**
- [ ] `speakable.cssSelector` = `[".gm-intro-text", ".gm-faq"]`
- [ ] Преимущества конкретны («через СДЭК», «гарантия 1 год»)

**[v2] AEO checklist — обновлено:**
- [ ] FAQ обёрнут в `<section class="gm-faq">` и находится в **Блоке 1** (не в Блоке 2)
- [ ] **5 вопросов** — информационный + сравнение + доставка + гарантия + трейд-ин
- [ ] Первый вопрос — информационный («Что важно учесть при выборе...»)
- [ ] Первое предложение каждого ответа = прямой ответ
- [ ] Длина каждого ответа: 40–80 слов
- [ ] В HTML FAQ нет `itemscope`, `itemtype`, `itemprop`
- [ ] Один вопрос FAQ переформулирован с кириллическим брендом
- [ ] **[v2] Если Step 3c активирован** — HowTo JSON-LD добавлен в `[slug]-jsonld.html`

**Кириллические LSI-запросы (применимо только если один из брендов страницы — Xiaomi/Redmi/POCO/Amazfit, см. Step 2c):**
- [ ] JSON-LD `LocalBusiness.alternateName` содержит кириллические варианты, если применимо
- [ ] Лид `.gm-intro-text` содержит кириллический вариант бренда страницы, если применимо
- [ ] Один вопрос FAQ — с кириллическим написанием бренда, если применимо
- [ ] Одна карточка — кириллический вариант в описании, если применимо
- [ ] Кириллика отсутствует в `alt` и `title` изображений

**E-E-A-T checklist:**
- [ ] **Experience**: «с 2016 года», «более 9 лет», конкретные факты
- [ ] **Expertise**: «магазин гаджетов и умной техники в Крыму», технические детали → выгоды
- [ ] **Authoritativeness**: «7500+ оценок и отзывов», рейтинг 5.0
- [ ] **Trustworthiness**: гарантия 1 год, трейд-ин, бонусная программа GOODMi, реальный адрес, «100% оригинальная техника, без подделок»

**Виджет отзывов:**
- [ ] Последний пункт `.gm-advantages-list` содержит `<iframe>` виджет Яндекс рейтинга (ID `81345582117`)
- [ ] Рядом — ссылка с `rel="nofollow noopener"` на reviews

---

### Step 6b — CSS Validation

Проверь каждый CSS-класс против `goodmi-styles.css`. Разрешены только классы из белого списка:

```
Основа:        gm-block
Intro:         gm-intro · gm-intro-text · gm-stat-badge · gm-badge-item · gm-badge-sep
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
               gm-collapse-btn-text
Repair photo:  gm-repair-photo
```

**Критические правила:**

| Элемент | Обязательно | Запрещено |
|---|---|---|
| CTA заголовок | `<div class="gm-cta-text"><h3>` | standalone `<h2>` внутри `.gm-cta` |
| FAQ вопрос | `<p class="gm-faq-question">` | `<h4>` без этого класса |
| Заголовок карточки | `<h3>` внутри `.gm-service-card` | `<h4>` |
| Пункт преимущества | `<li class="gm-advantage-item">` + icon + body | `<li><strong>...</strong> текст</li>` |
| Intro-секция | `<section class="gm-section gm-intro" aria-labelledby="...">` | `<header class="gm-intro">` |
| Trust strip элемент | `<div class="gm-trust-item" role="listitem">` | `<span class="gm-trust-item">` |
| Collapse state | `data-gm-state` | `data-mm-state` |
| Список преимуществ | `<ul class="gm-advantages-list" role="list">` | `<ul>` без `role="list"` |

---

### Step 7 — Output

Output **two** code blocks:

1. **`[slug].html`** — HTML-контент (Блок 1 с FAQ + Блок 2 + collapse script). Без JSON-LD.
2. **`[slug]-jsonld.html`** — только JSON-LD (LocalBusiness + WebPage + опционально HowTo).

**После вывода code blocks — сохранить оба файла** в папку `C:\Users\Алекс\GOODMi\Категории\Новые\` с именами `[slug].html` и `[slug]-jsonld.html`.

---

## SVG-иконки для блоков GOODMi

**Критическое ограничение CS-Cart:** TinyMCE декодирует HTML-сущности до сохранения — символы выше U+00FF заменяются на `???`. Использовать **только inline SVG**.

### Иконки для `.gm-advantages-list`

**Гарантия:**
```html
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><polyline points="3,10 8,16 17,5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
```

**Доставка:**
```html
<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><polygon points="11,2 5,11 10,11 9,18 15,9 10,9"/></svg>
```

**Трейд-ин:**
```html
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 6h12M14 6l-3-3m3 3l-3 3M18 14H6m0 0l3-3m-3 3l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
```

**Кредит:**
```html
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><polyline points="3,10 8,16 17,5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
```

**Звезда (рейтинг):**
```html
<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><polygon points="10,2 12.5,7.5 18.5,8.2 14,12.5 15.5,18.5 10,15.5 4.5,18.5 6,12.5 1.5,8.2 7.5,7.5"/></svg>
```

### Иконки для кнопок CTA

**Написать в чат:**
```html
<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M2 2h12v8H9.5l-2.5 3V10H2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
```

**Позвонить:**
```html
<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 2h3l1.5 3.5-1.5 1a8 8 0 0 0 3.5 3.5l1-1.5L14 10v3a1 1 0 0 1-1 1C5.5 14 2 7.5 2 3a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
```

---

## Brand constants

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
Pickup count: 6 точек
Rating:      5.0 / 7500+ оценок и отзывов (Яндекс Карты 6 точек + ВКонтакте + Avito + Отзовик)
Since:       2016
Speciality:  магазин гаджетов и умной техники в Крыму (мультибренд — ни один бренд не позиционируется как основной; запрещено «официальный»/«авторизованный»/«фирменный» для магазина и для любого бренда)
sameAs:      https://yandex.ru/maps/org/goodmi/81345582117/ (flagship для aggregateRating; полный список 9 источников — см. Step 3b)
```

---

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
Телевизоры:     https://goodmi.ru/tv-foto-video/
Умный дом:      https://goodmi.ru/umnyj-dom/
```

## Highlights grid — источник данных

**Не полагаться на захардкоженный список моделей.** Ассортимент расширяется на новые бренды и меняется чаще, чем раньше — модели по каждой категории уточнять у пользователя (или брать из его исходного запроса), не изобретать и не переносить бренд/модель без подтверждения, что она реально в каталоге.

Архивная справка — ассортимент Xiaomi-эры (мог устареть, использовать только как пример формата, не как источник данных):
```
Смартфоны Xiaomi → топ 3: Xiaomi 15 Pro, Xiaomi 15, Xiaomi 15 Ultra; топ 6 + Xiaomi 15T Pro
Смартфоны Redmi → Redmi Note 15 Pro Plus, Redmi Note 15 Pro, Redmi Note 15; + Redmi 15 для 6
Смартфоны POCO → Poco F8 Ultra, Poco X8 Pro Max, Poco X7 Pro (3 карточки)
Планшеты → Xiaomi Pad 7 Pro, Xiaomi Pad 7, Redmi Pad 2 (3); + Redmi Pad SE, Poco Pad для 6
Ноутбуки → RedmiBook Pro 16, RedmiBook 16, RedmiBook 14 (3 карточки)
Смарт-часы → Amazfit (флагман), Xiaomi Smart Band 10 Pro, Xiaomi Smart Band 10 (3 карточки)
Наушники → Marshall (флагман), JBL, Xiaomi Redmi Buds (3 карточки)
Пылесосы → топ робот-пылесос, вертикальный, автомобильный (3 карточки)
```

## Trust strip

```html
<span class="gm-trust-label"><strong>Гарантия 1 год</strong> на новую технику</span>
<span class="gm-trust-label"><strong>Доставка СДЭК</strong> по всей России</span>
<span class="gm-trust-label"><strong>Трейд-ин</strong> сдайте старое</span>
<span class="gm-trust-label"><strong>GOODMi</strong> с 2016 года</span>
```

---

## Reference

Full HTML structure standard: `seo--for-shop-html-block.mdc`
CSS file: `goodmi-styles.css`
Meta tags (multibrand): `seo-meta-builder-multibrand` skill
Legacy skills (Xiaomi-only positioning, kept for reference on existing pages): `seo-shop-page-builder-v2`, `seo-shop-page-builder` (v1)
