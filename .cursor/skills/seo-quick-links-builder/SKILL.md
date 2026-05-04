---
name: seo-quick-links-builder
description: Generates a SEO quick-links block (faceted navigation) for GOODMi category pages. Use when the user wants to add a quick links / fast navigation / tag cloud block to a Xiaomi smartphones, Redmi, POCO, tablets, smartwatches, laptops, service or any other category page. Follows the quick-links-seo-block standard with ЧПУ URLs, proper anchor texts, row-overflow JS mechanism, and CSS from goodmi-styles.css.
---

# SEO Quick Links Builder — GOODMi

Generates a `<section class="gm-quick-links">` block for any GOODMi (goodmi.ru) category page.
All links must use ЧПУ-URLs. No `rel="nofollow"`. No hard limit on link count — row-overflow mechanism handles display.

Store info: GOODMi — фирменный магазин техники Xiaomi в Крыму.
Магазины в Севастополе (ТЦ Муссон, Остров, Мандарин, Адм. Октябрьского), Симферополе (ТЦ Меганом) и Ялте (ТЦ Дом Торговли).
Доставка по всей России через СДЭК.

---

## Workflow (execute strictly in order)

### Step 1 — Request links from the user

**Always ask first — do NOT generate anything yet.**

Send this exact request to the user:

> Пришлите, пожалуйста, список всех ссылок, которые нужно добавить в блок быстрых ссылок.
>
> Формат — любой удобный, например:
> ```
> https://goodmi.ru/smartfony/xiaomi-15-pro/ — Xiaomi 15 Pro
> https://goodmi.ru/smartfony/redmi-note-15-pro/ — Redmi Note 15 Pro
> https://goodmi.ru/smart-chasy/ — Смарт-часы Xiaomi
> ```
> Или просто список URL без подписей — названия определю самостоятельно.
>
> Также укажите: для какой страницы делаем блок (например, «категория Смартфоны Xiaomi» или «ремонт Xiaomi 15 Pro»)?

Wait for the user's response before proceeding.

---

### Step 2 — Parse & validate the links

Parse each URL and its anchor text (label). If label is not provided — infer it from the URL slug.

**Duplicate URL check:**
- If two anchors share the same `href` → flag as ⚠️ duplicate URL, keep only the more descriptive anchor.

**Duplicate content check:**
- Compare each link with the cards already in `.gm-services-grid` on the target page (ask if unknown).
- Flag duplicates as ⚠️ — they must be removed from the block.

**Automatic grouping for ordering:**

| URL pattern | Group |
|---|---|
| `.../smartfony/xiaomi-*` | Серия Xiaomi |
| `.../smartfony/redmi-*` | Серия Redmi |
| `.../smartfony/poco-*` | Серия POCO |
| `.../[cat]/pamyat-*`, `*-gb*` | Память |
| `.../[cat]/ozu-*` | ОЗУ |
| `.../[cat]/8-7/`, `*diagonal*`, `*-inch*` | Диагональ |
| `.../[cat]/ekran-*`, `*-gc/` | Экран |
| `.../[cat]/s-4g-*`, `*lte*` | Связность |
| `.../[cat]/dla-*`, `*zaryadk*`, `*igr*` | Функции |
| `*processor*`, `*snapdragon*`, `*helio*` | Процессор |
| `*mp/`, `*kamera*` | Камера |
| `*korpus*` | Корпус |
| `*tsvet*`, color names (siniy, zeleniy...) | Цвет |
| `.../planshety/`, `.../smart-chasy/`, etc. | Смотрите также |
| `.../servis/remont-*/remont-*` | Другие модели в сервисе |

**Anchor text quality check:**

| Check | Pass | Fail |
|---|---|---|
| Конкретность | «Xiaomi 15 Pro», «Redmi Note 15 Pro 5G», «256 ГБ» | «устройство», «вариант» |
| Коммерческий сигнал | «POCO X7 Pro», «Xiaomi Pad 7», «RedmiBook 16» | «ноутбук», «планшет» |
| Запрещённые слова | — | «здесь», «нажмите», «смотреть», «подробнее» |

---

### Step 3 — SEO analysis & ordering

After grouping, evaluate and sort links by SEO priority for the flow.

**Priority order for the link stream (first-row = most visible):**

1. Модели — конкретные устройства (highest commercial intent)
2. Серии — Xiaomi / Redmi / POCO
3. Память (ROM)
4. Диагональ / ОЗУ
5. Экран (Гц, разрешение)
6. Связность (4G LTE)
7. Аккумулятор
8. Функции (быстрая зарядка, для игр, для фильмов)
9. Процессор
10. Камера
11. Корпус
12. Цвет

**For service pages — check:**

| Group | Recommended if |
|---|---|
| Другие модели в сервисе | Always — cross-link to sibling repair pages |
| Типы ремонта | If repair type pages exist (ekran, akkumulyator...) |
| Смежные устройства | Смарт-часы, Планшеты, Ноутбуки в ремонте |

**Identify gaps** — links missing but adding semantic value.
Prepare a list of suggested additions (URL + anchor + reason).

---

### Step 4 — Present analysis to the user

Output a structured analysis report:

```
## Анализ ссылок для блока быстрых ссылок

### Итого: [N] ссылок
Первая строка (всегда видна): модели и ключевые фильтры
Остальные ссылки: скрыты под кнопкой «Показать ещё»

**Модели** (N ссылок)
✅ Xiaomi Pad 7 Pro → https://goodmi.ru/planshetyi/xiaomi-pad-7-pro/
✅ Xiaomi Pad 7 → https://goodmi.ru/planshetyi/xiaomi-pad-7/

**Память** (N ссылок)
✅ 128 ГБ → https://goodmi.ru/planshetyi/planshety-128-gb/

**Цвет** (N ссылок)
✅ Серые → https://goodmi.ru/planshetyi/serye/

---

### ⚠️ Исключено (дубли URL): N ссылок
- «[Анкор]» → тот же URL что у «[Другой анкор]» — оставлен более конкретный

### ⚠️ Исключено (дубли из карточек выше): N ссылок
- [Модель] — уже есть в карточках моделей на странице

---

### 💡 Рекомендую добавить: N ссылок

1. **[Анкор]** → `[URL]`
   Причина: [почему это усилит семантику / какой запрос охватывает]

---

Подтвердите список или скажите «добавить предложенные» / «оставить как есть» — и я сразу сгенерирую блок.
```

**Wait for user confirmation before generating HTML.**

---

### Step 5 — Generate HTML block

After user confirms — generate the complete block with row-overflow mechanism.

Follow **all** rules in `.cursor/rules/quick-links-seo-block.mdc`.

Use `[slug]` = URL-friendly category name (e.g. `planshety`, `smartfony`, `noutbuki`).

**Full structure:**

```html
<section class="gm-quick-links gm-section" aria-labelledby="gm-ql-[slug]-heading">
  <h3 id="gm-ql-[slug]-heading" class="gm-section-title">[Заголовок]</h3>
  <nav id="gm-ql-[slug]-nav" aria-label="Быстрые ссылки по категориям" class="gm-quick-links-tags">
    <a href="[ЧПУ-URL]" class="gm-quick-link">[Анкор]</a>
    <!-- все ссылки в порядке SEO-приоритета, без div-обёрток и span-лейблов -->
  </nav>
  <div class="gm-ql-trigger">
    <button id="gm-ql-[slug]-btn" class="gm-ql-btn" aria-expanded="false" aria-controls="gm-ql-[slug]-nav">
      <span class="gm-ql-btn-text">Показать ещё</span>
      <span class="gm-ql-chevron" aria-hidden="true">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </button>
  </div>
</section>

<script>
(function () {
  var nav = document.getElementById('gm-ql-[slug]-nav');
  var btn = document.getElementById('gm-ql-[slug]-btn');
  if (!nav || !btn) return;

  var links = Array.prototype.slice.call(nav.querySelectorAll('.gm-quick-link'));
  var btnText = btn.querySelector('.gm-ql-btn-text');
  var chevron = btn.querySelector('.gm-ql-chevron');
  var expanded = false;
  var overflowLinks = [];
  var resizeTimer;

  function calcRows() {
    links.forEach(function (l) { l.classList.remove('gm-ql-hidden'); });
    overflowLinks = [];
    var firstTop = links[0].offsetTop;
    links.forEach(function (link) {
      if (link.offsetTop > firstTop + 4) { overflowLinks.push(link); }
    });
    if (!overflowLinks.length) { btn.style.display = 'none'; return; }
    btn.style.display = '';
    if (!expanded) {
      overflowLinks.forEach(function (l) { l.classList.add('gm-ql-hidden'); });
    }
  }

  function toggle() {
    expanded = !expanded;
    overflowLinks.forEach(function (l) { l.classList.toggle('gm-ql-hidden', !expanded); });
    btnText.textContent = expanded ? 'Свернуть' : 'Показать ещё';
    chevron.style.transform = expanded ? 'rotate(180deg)' : '';
    btn.setAttribute('aria-expanded', String(expanded));
  }

  btn.addEventListener('click', toggle);
  window.addEventListener('resize', function () {
    if (expanded) return;
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(calcRows, 150);
  });
  requestAnimationFrame(function () { requestAnimationFrame(calcRows); });
}());
</script>
```

**Заголовок H3 по типу страницы:**
- Категория магазина → «Быстрый выбор»
- Конкретная модель → «Смотрите также»
- Страница сервиса → «Другие устройства в сервисе»

**Порядок ссылок в потоке** (всегда соблюдать — первая строка = витрина):
1. Модели
2. Серии (Xiaomi → Redmi → POCO)
3. Память
4. Диагональ / ОЗУ
5. Экран
6. Связность + функции
7. Процессор
8. Камера + корпус
9. Цвет

---

### Step 6 — Self-check before outputting

- [ ] Нет `<style>` тегов — весь CSS в `goodmi-styles.css`
- [ ] Нет `rel="nofollow"` на ссылках
- [ ] Все ссылки — ЧПУ (нет GET-параметров)
- [ ] Нет `href="#"` — только реальные URL
- [ ] Нет двух анкоров с одинаковым `href`
- [ ] Нет дублей с карточками `.gm-services-grid`
- [ ] Нет эмодзи — только HTML-сущности (`&#8211;`, `&#215;` и т.д.)
- [ ] Анкоры конкретные (без «здесь», «нажмите»)
- [ ] H3 (не H2, не H4)
- [ ] `[slug]` уникален и заменён во всех трёх ID: `heading`, `nav`, `btn`
- [ ] `aria-expanded="false"` на кнопке
- [ ] `aria-controls` указывает на правильный ID nav

---

### Step 7 — Output

Save the block as a **separate file** in the same folder as the category SEO block.
Naming: `[category]-quick-links.html` (e.g., `planshety-quick-links.html`, `smartfony-quick-links.html`).

**Never embed** the quick links block inside the SEO description file.

Output the final HTML in a single code block — no commentary before or after.

---

## Reference

Full rules and CSS classes: `.cursor/rules/quick-links-seo-block.mdc`
CSS file: `goodmi-styles.css` — sections 12 «Quick Links» and 12b «Row Overflow»
