---
name: seo-quick-links-top-builder
description: Generates the "Быстрые ссылки вверху" (quick links at top) block for GOODMi. Use when the user wants a compact quick-links block for the top of a category page — no title, no orange strip, button "Показать ещё" on the same line as links, bordered rounded box. Follows quick-links-top.mdc; same SEO rules and link workflow as seo-quick-links-builder, different markup and CSS (goodmi-styles.css section 12c).
---

# Быстрые ссылки вверху — GOODMi

Генерирует блок `<section class="gm-quick-links-top">` для размещения **в верхней части** страницы категории или сервиса.
Визуально: без заголовка «Быстрый выбор», без оранжевой полоски, кнопка «Показать ещё» в одну строку с пунктами, обводка со скруглёнными краями.

Магазин: GOODMi — фирменный магазин техники Xiaomi в Крыму (goodmi.ru).

---

## Workflow (выполнять строго по порядку)

### Step 1 — Запросить ссылки у пользователя

**Сначала спросить — не генерировать блок.**

Отправить пользователю:

> Пришлите, пожалуйста, список всех ссылок для блока быстрых ссылок вверху.
>
> Формат — любой удобный, например:
> ```
> https://goodmi.ru/smartfony/xiaomi-15-pro/ — Xiaomi 15 Pro
> https://goodmi.ru/smartfony/redmi-note-15-pro/ — Redmi Note 15 Pro
> ```
> Или только URL — названия определю по слагу.
>
> Укажите также: для какой страницы делаем блок (например, «категория Смартфоны Xiaomi»)?

Дождаться ответа перед продолжением.

---

### Step 2 — Разбор и проверка ссылок

Как в скилле **seo-quick-links-builder**: парсинг URL и анкоров, проверка дублей URL, проверка дублей с карточками `.gm-services-grid`, группировка по типам (модели, серии, память и т.д.), проверка качества анкоров.

---

### Step 3 — SEO-анализ и порядок ссылок

Как в **seo-quick-links-builder**: приоритет потока (модели → серии → память → диагональ/ОЗУ → экран → связность → функции → процессор → камера → корпус → цвет). Для сервисных страниц — учёт «Другие модели в сервисе» и т.д.

---

### Step 4 — Представить анализ пользователю

Вывести отчёт по образцу из **seo-quick-links-builder** (итого N ссылок, группы, исключённые дубли, рекомендуемые добавления). **Дождаться подтверждения** перед генерацией HTML.

---

### Step 5 — Сгенерировать HTML-блок

После подтверждения — сгенерировать блок по **всем** правилам из `.cursor/rules/quick-links-top.mdc`.

**Структура (без H3, с обводкой, кнопка в строку):**

```html
<section class="gm-quick-links-top gm-section" aria-label="Быстрые ссылки по категориям">
  <div class="gm-quick-links-top-inner">
    <nav id="gm-ql-[slug]-nav" aria-label="Быстрые ссылки по категориям" class="gm-quick-links-tags">
      <a href="[ЧПУ-URL]" class="gm-quick-link">[Анкор]</a>
      <!-- все ссылки в порядке SEO-приоритета -->
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

**`[slug]`** — уникальный идентификатор (например `planshety-top`, `smartfony-top`), чтобы не пересекаться с блоком быстрых ссылок внизу страницы.

Порядок ссылок в потоке — как в **quick-links-seo-block**: модели → серии → память → диагональ/ОЗУ → экран → связность/функции → процессор → камера/корпус → цвет.

---

### Step 6 — Самопроверка перед выдачей

- [ ] Нет `<style>` — весь CSS в `goodmi-styles.css` (секция 12c)
- [ ] Нет H3 и оранжевой полоски
- [ ] Классы: `gm-quick-links-top`, `gm-quick-links-top-inner`
- [ ] Нет `rel="nofollow"`, только ЧПУ-URL, нет `href="#"`
- [ ] Нет дублей URL и дублей с `.gm-services-grid`
- [ ] `[slug]` уникален и подставлен в ID: `gm-ql-[slug]-nav`, `gm-ql-[slug]-btn`
- [ ] `aria-expanded="false"`, `aria-controls` указывает на ID nav

---

### Step 7 — Вывод

Сохранить блок в **отдельный файл**. Именование: `[category]-quick-links-top.html` (например `planshety-quick-links-top.html`, `smartfony-quick-links-top.html`).

Не встраивать блок в файл SEO-описания категории.

Выдать финальный HTML одним блоком кода без комментариев до/после.

---

## Справка

- Правила и разметка: `.cursor/rules/quick-links-top.mdc`
- CSS: `goodmi-styles.css` — секция **12c** «Quick Links Top»
- Логика ссылок и порядок: как в `.cursor/rules/quick-links-seo-block.mdc`
