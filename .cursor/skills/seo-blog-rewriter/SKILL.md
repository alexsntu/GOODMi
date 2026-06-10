---
name: seo-blog-rewriter
description: "Рерайт существующих статей для блога GOODMi: автоматически извлекает ключевые слова из исходника, сохраняет их частотность и смысловую структуру, но полностью перефразирует текст. Выводит SEO/GEO/AEO-оптимизированный HTML (два файла: [slug].html + [slug]-jsonld.html) в классах goodmi-styles.css."
---

# SEO Blog Rewriter — GOODMi

Рерайт существующей статьи для блога goodmi.ru/blog-xiaomi-na-russkom/.

**Цель:** максимальная структурная и тематическая близость к оригиналу при полном отсутствии текстового совпадения. Ключевые слова и их частотность — сохраняются. Предложения — переписываются.

---

## Workflow

### Step 1 — Сбор входных данных

Запросить у пользователя одним сообщением:

| Параметр | Обязательность | Описание |
|----------|----------------|----------|
| `source` | Обязательно | URL статьи **или** вставленный текст (HTML / plain text) |
| `category` | Обязательно | Одна из: `Обзоры` · `База знаний` · `Новости` · `Статьи` · `Видео` |
| `date_published` | Обязательно | Дата публикации `YYYY-MM-DD` |
| `date_modified` | Опционально | Дата обновления `YYYY-MM-DD` (если нет — равна date_published) |
| `extra_keywords` | Опционально | Дополнительные ключи, которых нет в исходнике: `фраза: N` |
| `links` | Опционально | Ссылки для перелинковки: `Анкор -> URL`. Если нет — `нет` |

> **slug генерируется автоматически** на основе темы статьи — у пользователя не запрашивается.
> Правила slug: латиница, дефисы, без стоп-слов (`kak`, `chto`, `dlya` допустимы если несут смысл), 4–6 слов, отражает главный запрос статьи.
>
> **Если `source` — URL:** загрузить страницу через WebFetch, извлечь основной текст статьи (без шапки сайта, меню, футера, рекламных блоков). Если страница недоступна или заблокирована — сообщить пользователю и попросить вставить текст вручную.
>
> Частотность ключей из исходной статьи извлекается автоматически на Step 2.
> `extra_keywords` — только для добавления ключей сверх исходника.

---

### Step 2 — Анализ источника (ПОКАЗАТЬ ПОЛЬЗОВАТЕЛЮ, ЖДАТЬ ПОДТВЕРЖДЕНИЯ)

Извлечь из исходной статьи:

1. **Структура H2/H3** — заголовки разделов
2. **Ключевые слова и частотность** — фразы от 2 слов, встречающиеся 2+ раз; одиночные слова-бренды (Xiaomi, Dreame и т.д.)
3. **Гео-упоминания** — Севастополь, Крым, Симферополь, Ялта (и их производные)
4. **Бренды** — все упомянутые марки с частотностью
5. **Ключевые утверждения** — технические факты и цифры, которые нужно сохранить дословно (цифры, названия моделей, характеристики)

**Вывести в чат сводку:**

```
Анализ исходной статьи
──────────────────────
Объём: ~[N] слов · [N] разделов H2

Ключевые слова (авто):
  - фраза: N → сохранить N вхождений
  ...

Бренды: Xiaomi · [другие] (частотность см. выше)
Гео: [нет / Севастополь: N · Крым: N]

Ключевые факты/цифры (оставить дословно):
  - [характеристика]
  ...

Предложенная структура рерайта:
  Вводный абзац
  Key Facts: [N пунктов]
  H2 1. [новый заголовок на ту же тему]
  H2 2. ...
  FAQ: [4 вопроса]
  CTA + Author

Подтвердите структуру или скорректируйте. ✍️
```

**Не генерировать HTML до получения подтверждения.**

---

### Step 3 — Рерайт

#### Правила оригинальности (обязательны)

| Правило | Применение |
|---------|-----------|
| Не более 3 слов подряд из оригинала | Каждое предложение |
| Новая структура каждого предложения | Меняй порядок частей, залог (актив↔пассив), тип предложения |
| Новое первое предложение каждого абзаца | Открывать иначе, чем в оригинале |
| Порядок аргументов внутри раздела можно менять | Сохраняй тему раздела, не его конкретную аргументацию |
| Числа и названия моделей — оставить дословно | Характеристики, даты, цены |
| Не добавлять и не убирать смысловые тезисы | Только перефразировать существующие |

#### Стиль GOODMi

- Тон: уверенный, ёмкий, без воды; обращение на «вы»
- Apple-style: технические характеристики → конкретные выгоды пользователя
- Нет межабзацных отсылок: «как мы писали», «см. выше», «в следующем разделе»
- Каждый абзац самодостаточен (GEO: AI вырезает куски дословно)

#### E-E-A-T сигналы (вплетать в текст как факты, не как лозунги)

| Фактор | Формулировка |
|--------|-------------|
| Experience | «с 2015 года», «более 10 лет на рынке», конкретные цифры |
| Expertise | «фирменный магазин техники Xiaomi», технические факты |
| Authoritativeness | «5.0 / 2000+ отзывов на Яндексе» |
| Trustworthiness | Реальный адрес, гарантия 1 год, доставка СДЭК |

#### Перелинковка (правило evergreen)

В статьях-гайдах, базах знаний, инструкциях и FAQ ссылки только на:
- категории, подкатегории, подборки, серии/линейки

**Нельзя:** ссылки на конкретные карточки товаров (SKU) — они меняются, уходят из продажи, вызывают 404.

**Исключение:** обзор конкретной модели или новость о ней — допустима ссылка на карточку, но обязательно добавить минимум 1 ссылку на категорию.

#### Размещение ключевых слов

| Тип ключа | Где размещать | Где НЕ размещать |
|-----------|---------------|------------------|
| Гео (Севастополь, Крым) | Только `<p>` абзацы | `alt`, заголовки H2/H3 |
| Бренды (Xiaomi, Dreame) | Везде естественно | Не подряд несколько раз |
| Коммерческие (купить, цена) | Вводный, CTA, FAQ | Key Facts |
| Информационные | H2-секции, Key Facts, FAQ | — |

**Лимиты:**
- Не более 2 ключевых фраз в одном предложении
- Расстояние между повторами одного ключа: минимум 150 символов
- Суммарная плотность ключей не более 5–7% от объёма

---

### Step 4 — Проверка ключей

Перед генерацией HTML проверить каждый ключ из Step 2:

- Фактическое количество вхождений = целевому?
- Если нет — добавить / убрать вхождения, сохранив естественность
- Нет ключей плотностью > 7%

---

### Step 5 — Генерация HTML

#### Файл 1: `Блог/[slug].html`

Структура строго в следующем порядке:

```html
<div class="gm-block gm-article" itemscope itemtype="https://schema.org/Article">

  <!-- 1. Article header -->
  <header class="gm-article-header">
    <div class="gm-article-tags">
      <a class="gm-article-tag" href="https://goodmi.ru/blog-xiaomi-na-russkom/">Блог GOODMi</a>
      <a class="gm-article-tag" href="[URL категории из таблицы ниже]">[Категория]</a>
    </div>
    <!-- H1 НЕ добавлять — задаётся в CS-Cart отдельно -->
  </header>

  <!-- 2. Intro text (GEO: standalone lead, 4–6 предложений) -->
  <div class="gm-intro-text">
    <p>[Вводный абзац — самодостаточный, 5 GEO-сущностей: тема, продукт, бренд, сценарий, магазин]</p>
  </div>

  <!-- 3. Key Facts (GEO: первичный элемент цитирования AI) -->
  <div class="gm-article-key-facts">
    <p class="gm-article-key-facts-title"><svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><polyline points="3,10 8,16 17,5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>&nbsp; Ключевые факты</p>
    <ul class="gm-article-key-facts-list">
      <li>[Факт 1 — конкретный, понятен без контекста статьи]</li>
      <li>[Факт 2]</li>
      <li>[Факт 3]</li>
      <!-- 3–5 пунктов: числа, сроки, технические факты; без отсылок «как сказано выше» -->
    </ul>
  </div>

  <!-- 4. TOC — только если 4+ разделов H2 -->
  <nav class="gm-article-toc" aria-label="Содержание статьи">
    <p class="gm-article-toc-title">Содержание</p>
    <ol class="gm-article-toc-list">
      <li><a class="gm-article-toc-link" href="#section-1">Раздел 1</a></li>
      <!-- href должны совпадать с id заголовков H2 в body -->
    </ol>
  </nav>

  <!-- 5. Article body -->
  <div class="gm-article-body" itemprop="articleBody">

    <section id="section-1">
      <h2>[Заголовок раздела — не совпадает с оригиналом дословно]</h2>
      <p>[Абзац]</p>
      <!-- По необходимости: pull-quote, highlight, howto-steps, figure -->
    </section>

    <!-- Pull-quote для ключевых утверждений -->
    <blockquote class="gm-article-pullquote">
      <p>«[Ключевой тезис]»</p>
    </blockquote>

    <!-- Callout / совет -->
    <div class="gm-highlight">
      <span class="gm-highlight-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="8" r="5" stroke="currentColor" stroke-width="1.8"/><path d="M8 16h4M10 13v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
      <div class="gm-highlight-body"><p><strong>Совет:</strong> [текст].</p></div>
    </div>

    <!-- Нумерованные шаги (если тема — пошаговый процесс) -->
    <ol class="gm-howto-steps" itemscope itemtype="https://schema.org/HowTo">
      <li class="gm-howto-step" itemprop="step" itemscope itemtype="https://schema.org/HowToStep">
        <div class="gm-howto-step-body">
          <strong itemprop="name">Шаг 1</strong>
          <p itemprop="text">[Описание]</p>
        </div>
      </li>
    </ol>

    <!-- Изображение (если есть в исходнике) -->
    <figure>
      <img src="[URL]" alt="[Описание без ключей-гео]" itemprop="image" loading="lazy">
      <figcaption>[Подпись]</figcaption>
    </figure>

    <!-- Перелинковка (только если пользователь передал ссылки) -->
    <section class="gm-section" aria-labelledby="catalog-links-heading">
      <h3 id="catalog-links-heading">Что выбрать в каталоге GOODMi</h3>
      <ul>
        <li><a href="[URL из данных пользователя]">[Анкор из данных пользователя]</a></li>
      </ul>
    </section>

  </div><!-- /.gm-article-body -->

  <!-- 6. FAQ (AEO) — 4 вопроса, первое предложение = прямой ответ -->
  <section class="gm-faq" aria-labelledby="faq-heading"
           itemscope itemtype="https://schema.org/FAQPage">
    <h2 id="faq-heading" class="gm-section-title">Часто задаваемые вопросы</h2>

    <div class="gm-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
      <p class="gm-faq-question" itemprop="name">[Вопрос по теме статьи]</p>
      <div class="gm-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
        <p itemprop="text">[Прямой ответ первым предложением. Детали далее. 40–80 слов.]</p>
      </div>
    </div>

    <div class="gm-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
      <p class="gm-faq-question" itemprop="name">Где купить [тема] в Севастополе?</p>
      <div class="gm-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
        <p itemprop="text">[Ответ с упоминанием GOODMi, доставки СДЭК, Крыма]</p>
      </div>
    </div>

    <div class="gm-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
      <p class="gm-faq-question" itemprop="name">Какая гарантия на технику Xiaomi в GOODMi?</p>
      <div class="gm-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
        <p itemprop="text">[Ответ про гарантию]</p>
      </div>
    </div>

    <div class="gm-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
      <p class="gm-faq-question" itemprop="name">[Сравнение / выбор: вариант A или B?]</p>
      <div class="gm-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
        <p itemprop="text">[Ответ]</p>
      </div>
    </div>

  </section>

  <!-- 7. CTA -->
  <div class="gm-cta" role="complementary" aria-label="Контакты GOODMi">
    <div class="gm-cta-text">
      <h3>Нужна помощь с выбором?</h3>
      <p>Напишите нам на почту или воспользуйтесь онлайн-чатом на странице &#8212;
         специалисты GOODMi помогут выбрать устройство и оформить покупку. Или звоните бесплатно.</p>
    </div>
    <div class="gm-cta-actions">
      <a href="mailto:store@goodmi.ru" class="gm-btn gm-btn-primary"
         aria-label="Написать на почту GOODMi"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="1" y="3" width="14" height="10" rx="1" stroke="currentColor" stroke-width="1.5"/><polyline points="1,3 8,9 15,3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>&nbsp; store@goodmi.ru</a>
      <a href="tel:+78002501700" class="gm-btn gm-btn-secondary"
         aria-label="Позвонить бесплатно 8-800-250-17-00"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 2h3l1.5 3.5-1.5 1a8 8 0 0 0 3.5 3.5l1-1.5L14 10v3a1 1 0 0 1-1 1C5.5 14 2 7.5 2 3a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>&nbsp; 8 (800) 250-17-00</a>
    </div>
  </div>

  <!-- 8. Author box (E-E-A-T) — всегда последний -->
  <div class="gm-article-author">
    <span class="gm-article-author-icon" aria-hidden="true"><svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="12" r="6" stroke="currentColor" stroke-width="2"/><path d="M4 28c0-6.627 5.373-12 12-12s12 5.373 12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
    <div class="gm-article-author-body">
      <strong>Команда GOODMi</strong>
      <p>Специалисты фирменного магазина техники Xiaomi в Крыму. Работаем с 2015&nbsp;года &#8212; знаем технику изнутри.</p>
    </div>
  </div>

</div><!-- /.gm-block.gm-article -->
```

---

#### Файл 2: `Блог/[slug]-jsonld.html`

Два отдельных `<script>` блока. **Запрещено:** `@graph`, `FAQPage` в JSON-LD.

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "@id": "https://goodmi.ru/blog-xiaomi-na-russkom/[slug]/#article",
  "headline": "[TITLE статьи — не из HTML, берётся из CS-Cart]",
  "description": "[150–160 символов: прямой ответ на главный вопрос + бренд + гео]",
  "datePublished": "[YYYY-MM-DD]",
  "dateModified": "[YYYY-MM-DD]",
  "inLanguage": "ru",
  "url": "https://goodmi.ru/blog-xiaomi-na-russkom/[slug]/",
  "keywords": ["ключ1", "ключ2", "Xiaomi", "GOODMi"],
  "author": { "@type": "Organization", "name": "GOODMi", "url": "https://goodmi.ru" },
  "publisher": {
    "@type": "Organization",
    "name": "GOODMi",
    "logo": { "@type": "ImageObject", "url": "https://goodmi.ru/images/logos/goodmi-logo.png" }
  },
  "mainEntityOfPage": { "@type": "WebPage", "@id": "https://goodmi.ru/blog-xiaomi-na-russkom/[slug]/" },
  "speakable": {
    "@type": "SpeakableSpecification",
    "cssSelector": [".gm-article-key-facts", ".gm-faq"]
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Главная", "item": "https://goodmi.ru/" },
    { "@type": "ListItem", "position": 2, "name": "Блог", "item": "https://goodmi.ru/blog-xiaomi-na-russkom/" },
    { "@type": "ListItem", "position": 3, "name": "[TITLE]", "item": "https://goodmi.ru/blog-xiaomi-na-russkom/[slug]/" }
  ]
}
</script>
```

---

### Step 6 — Генерация мета-тегов

Сразу после сохранения HTML-файлов сгенерировать мета-теги для CS-Cart.
Выводить **каждое поле в отдельном code-блоке** для копирования.

#### Правила для статей блога

**H1** (= заголовок статьи, задаётся в CS-Cart отдельно):
- 40–70 символов
- Информационный, описательный — без «Купить», без гео
- Это и есть заголовок статьи; генерировать из темы и главного ключа

**title**:
- 50–70 символов (Яндекс показывает ≤70 полностью — приоритет)
- Шаблоны по категории:
  - База знаний / Статьи: `[Ключевой запрос] — [гайд/инструкция] | GOODMi`
  - Обзоры: `Обзор [модель] [год] — [тип устройства] | GOODMi`
  - Новости: `[Событие/новость] | Блог GOODMi`
- `GOODMi` или `Блог GOODMi` — всегда в конце через `|`
- Гео в title — если статья с локальным интентом (например, «где купить в Севастополе»); для чисто информационных — необязательно

**description**:
- 150–250 символов
- Первые ~155 симв.: прямой ответ на главный вопрос статьи + упоминание GOODMi и 1–2 USP
- 155–250 симв.: расширенный контекст для AI Overviews — E-E-A-T сигнал («фирменный магазин Xiaomi с 2015 года»), дополнительные темы статьи
- CTA в конце: «Читайте в блоге GOODMi» или «Подробнее в гайде»
- Запрещено: цены в ₽, капслок, «официальный магазин», «авторизованный»

#### Формат вывода

```
H1:
```
[текст H1]
```

title:
```
[текст title]
```

description:
```
[текст description]
```

Длины: H1 — [N симв.] · title — [N симв.] · description — [N симв.]
```

---

### Step 7 — Сохранение и отчёт

Сохранить файлы в папку `Блог/`:
- `Блог/[slug].html`
- `Блог/[slug]-jsonld.html`

HTML-код **не выводить в чат** — только краткий отчёт, затем мета-теги:

```
Рерайт готов. Файлы сохранены:
  Блог/[slug].html
  Блог/[slug]-jsonld.html

Проверка ключей:
  фраза       цель → факт
  ─────────────────────────
  [ключ 1]     N  →  N  ✓
  [ключ 2]     N  →  N  ✓
  ...
```

После отчёта сразу выводить мета-теги (Step 6).

---

## Чеклист перед генерацией

**Оригинальность:**
- [ ] Нет 4+ слов подряд из оригинала (кроме технических терминов и названий)
- [ ] Первое предложение каждого абзаца не совпадает с оригиналом
- [ ] Структура предложений изменена (порядок, залог, тип)

**HTML:**
- [ ] Нет `<style>` тегов, нет сырых эмодзи — только HTML-сущности (`&#8212;`, `&#183;`)
- [ ] Все иконки — только inline SVG (никаких Unicode-символов в `<span>`)
- [ ] Нет `<h1>` внутри HTML-блока
- [ ] Каждый H2 в body имеет уникальный `id`
- [ ] TOC добавлен если 4+ разделов H2; href совпадают с `id` заголовков
- [ ] `.gm-intro-text` перед Key Facts присутствует
- [ ] Key Facts: 3–5 пунктов, standalone, без отсылок к тексту
- [ ] FAQ: 4 вопроса, первое предложение = прямой ответ, 40–80 слов
- [ ] Блок перелинковки добавлен только если пользователь передал ссылки
- [ ] Author box — последний блок перед закрывающим `</div>`

**JSON-LD:**
- [ ] Нет `@graph` — только отдельные `<script>` на тип
- [ ] Нет `FAQPage` в JSON-LD (запрещено в проекте)
- [ ] `BlogPosting.description` — 150–160 символов
- [ ] `speakable.cssSelector` = `[".gm-article-key-facts", ".gm-faq"]`
- [ ] BreadcrumbList — ровно 3 уровня

**Перелинковка:**
- [ ] Все ссылки ведут на категории/подкатегории — не на карточки товаров (SKU)
- [ ] Минимум 2 ссылки в блоке «Что выбрать в каталоге GOODMi»

**Ключевые слова:**
- [ ] Каждый ключ использован ровно целевое количество раз
- [ ] Гео-ключи только в `<p>`, не в заголовках и не в `alt`
- [ ] Плотность ключей не превышает 5–7%

**Мета-теги (Step 6):**
- [ ] H1: 40–70 симв., информационный, без «Купить» и гео
- [ ] title: 50–70 симв., шаблон по категории, `GOODMi` в конце через `|`
- [ ] description: 150–250 симв., первые ~155 = прямой ответ + USP, CTA в конце
- [ ] Каждое поле выведено в отдельном code-блоке

---

## URL категорий блога

| Категория | URL |
|-----------|-----|
| Обзоры | `https://goodmi.ru/blog-xiaomi-na-russkom/obzory/` |
| База знаний | `https://goodmi.ru/blog-xiaomi-na-russkom/poleznoe/` |
| Новости | `https://goodmi.ru/blog-xiaomi-na-russkom/novosti/` |
| Статьи | `https://goodmi.ru/blog-xiaomi-na-russkom/stati/` |
| Видео | `https://goodmi.ru/blog-xiaomi-na-russkom/video/` |

---

## CSS-классы (белый список)

```
Обёртка:      gm-block · gm-article
Header:       gm-article-header · gm-article-tags · gm-article-tag
Intro:        gm-intro-text
Key Facts:    gm-article-key-facts · gm-article-key-facts-title · gm-article-key-facts-list
TOC:          gm-article-toc · gm-article-toc-title · gm-article-toc-list · gm-article-toc-link
Body:         gm-article-body
Pull-quote:   gm-article-pullquote
Callout:      gm-highlight · gm-highlight-icon · gm-highlight-body
HowTo:        gm-howto-steps · gm-howto-step · gm-howto-step-body
Section:      gm-section · gm-section-title
FAQ:          gm-faq · gm-faq-item · gm-faq-question · gm-faq-answer
CTA:          gm-cta · gm-cta-text · gm-cta-actions · gm-btn · gm-btn-primary · gm-btn-secondary
Author:       gm-article-author · gm-article-author-icon · gm-article-author-body
```

---

## SVG-иконки (inline only)

**Галочка (Key Facts):**
```html
<svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><polyline points="3,10 8,16 17,5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
```

**Лампочка (Highlight/callout):**
```html
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="8" r="5" stroke="currentColor" stroke-width="1.8"/><path d="M8 16h4M10 13v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
```

**Email (CTA):**
```html
<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="1" y="3" width="14" height="10" rx="1" stroke="currentColor" stroke-width="1.5"/><polyline points="1,3 8,9 15,3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
```

**Телефон (CTA):**
```html
<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 2h3l1.5 3.5-1.5 1a8 8 0 0 0 3.5 3.5l1-1.5L14 10v3a1 1 0 0 1-1 1C5.5 14 2 7.5 2 3a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
```

**Автор:**
```html
<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="12" r="6" stroke="currentColor" stroke-width="2"/><path d="M4 28c0-6.627 5.373-12 12-12s12 5.373 12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
```

---

## Константы GOODMi

```
Бренд:       GOODMi (не Goodmi, не GOODMI)
Телефон:     8 (800) 250-17-00  /  href="tel:+78002501700"
Email:       store@goodmi.ru
Адрес:       г. Севастополь, ул. Вакуленчука, 29 — ТЦ «Муссон»
Часы:        ежедневно 10:00–21:00
Доставка:    СДЭК по всей России
Самовывоз:   6 точек в Крыму (4 Севастополь, Симферополь, Ялта)
Рейтинг:     5.0 / 2000+ отзывов на Яндексе
С 2015 года: фирменный магазин техники Xiaomi в Крыму
Блог:        https://goodmi.ru/blog-xiaomi-na-russkom/
```
