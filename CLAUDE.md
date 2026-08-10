# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the SEO content repository for **GOODMi** (`goodmi.ru`) — a Xiaomi specialty store in Crimea (Sevastopol, Simferopol, Yalta) with Russia-wide CDEK delivery. The repo contains HTML SEO blocks deployed into a CS-Cart e-commerce platform, plus a companion MCP server (`cscart-mcp-server/`) for programmatic CS-Cart API access.

**Store constants (use verbatim in all content):**
- Brand: `GOODMi` (never Goodmi, never GOODMI)
- Phone: `8 (800) 250-17-00` / `href="tel:+78002501700"`
- Email: `store@goodmi.ru`
- Hours: daily 10:00–21:00
- Main address: `г. Севастополь, ул. Вакуленчука, 29 — ТЦ «Муссон»`
- Pickup points (6 total): Sevastopol ТЦ Муссон · Остров · ТЦ Мандарин · Адм. Октябрьского; Simferopol ТЦ Меганом; Yalta ТЦ Дом Торговли
- Since: copywriting uses `«с 2015 года»` / `«более 10 лет на рынке»`; JSON-LD `LocalBusiness.description` uses `«с 2016 года»` — do not change either without user confirmation
- `sameAs`: `https://yandex.ru/maps/org/goodmi/81345582117/`
- Yandex rating widget iframe ID: `81345582117`
- Speciality: «фирменный магазин техники Xiaomi в Крыму»
- Prohibited: «официальный», «авторизованный» when describing the store
- Commercial terms: use «кредит», never «рассрочка без переплат» or «беспроцентная рассрочка»

**Traffic split (informs meta-tag prioritization):** Yandex ≈ 58% organic traffic / conversion 2× higher than Google; Google ≈ 41%. Optimize for Yandex first.

## Repository Structure

```
Категории/Новые/       # In-progress / skill-generated HTML (skills always save here)
Категории/Готовые/     # Ready-to-deploy category SEO HTML blocks
Акции/                 # Promotion page HTML
Блог/                  # Blog article SEO blocks
Страницы/              # Standalone page HTML (trade-in, VPN, reviews, etc.)
Быстрые ссылки/        # Quick-links blocks (separate files, never embedded in category files)
Меню/                   # Site navigation blocks (desktop catalog menu, footer columns) — see "Navigation / Footer Menu Blocks"
Описания товаров/Готовые/  # Rewritten product description blocks (OPISANIE-[BRAND]-[MODEL]-GOODMI.html)
Новый макет/            # Homepage/menu redesign mockups (in-progress, not yet deployed)
CSS/                   # goodmi-styles.css (shared, documented — edit this one), goodmi-styles.min.css (generated, no comments — upload this one), maxmobiles-styles.css
Мета-теги/             # Meta tag drafts
Расширение семантики/  # seo-core-expansion skill output — one-off working files, user deletes after reviewing
_DEV/                  # Development artifacts and task trackers (untracked)
tools/                 # Utility scripts (see below)
cscart-mcp-server/     # MCP server for CS-Cart API (Node.js/Jest)
.cursor/rules/         # Cursor rules (MDC) defining HTML/SEO standards + workflow shortcuts
.cursor/skills/        # Reusable skill definitions for content generation workflows
```

**Utility scripts in `tools/`:**
- `restructure_category_two_block.py` — splits single-block category HTML into the two-block (Block 1 / Block 2) structure
- `fix_faq_mass.py` — bulk removes FAQPage itemscope/itemtype/itemprop attributes from HTML files
- `fix_faq_schema.py` — removes FAQPage JSON-LD blocks from jsonld HTML files
- `crop_image_for_tg.py` — center-crops an image to 1080×1080 px for Telegram (requires Pillow); edit input/output paths directly in the script before running

## HTML Block Architecture

All category and page blocks follow a shared structure:

1. **JSON-LD schema** (top) — `LocalBusiness`, `BreadcrumbList`, and optionally `ItemList`. Every `Review` entity must include `"itemReviewed": { "@id": "https://goodmi.ru/#organization" }`.
2. **CSS classes** — all styles come from `goodmi-styles.css`; never write `<style>` tags inline. Two files are kept in sync: `CSS/goodmi-styles.css` is the documented source (header protocol, palette, section comments — **edit this one**) and `CSS/goodmi-styles.min.css` is a generated copy with all comments stripped (**upload this one** to Дизайн → Темы → Свой CSS — smaller payload, nothing else differs). After any edit to `goodmi-styles.css`, regenerate the `.min.css` copy (strip `/* ... */` comments and collapse the resulting blank lines) before deploying.
3. **Two-file split per page** — `[slug].html` (wysiwyg HTML only, no `<script>`) + `[slug]-jsonld.html` (JSON-LD only, placed in a separate Layout block). **Reason:** CS-Cart duplicates every `<script>` tag in wysiwyg descriptions, causing GSC duplicate-schema errors.
4. **Quick-links blocks** — always saved as separate files (`[category]-quick-links.html`, `[category]-quick-links-top.html`), never embedded in the category SEO file.
5. **No emojis** — use HTML entities instead (`&#8211;`, `&#215;`, etc.). Use inline SVG for all icons — TinyMCE in CS-Cart corrupts Unicode entities above U+00FF (renders as `???` in DB).
6. **Block 1 / Block 2 split** — Block 1 is always visible; Block 2 is wrapped in `.gm-collapse-wrapper` with `max-height`. Only Block 1 carries `itemscope itemtype="https://schema.org/WebPage"`.

### Block 1 / Block 2 content split (v1 vs v2)

| Section | v1 (seo-shop-page-builder) | v2 (seo-shop-page-builder-v2) |
|---|---|---|
| Block 1 (always visible) | intro + trust strip + cards grid | intro + trust strip + cards grid + **FAQ** |
| Block 2 (collapse, 350px) | advantages + **FAQ** + CTA | advantages + CTA |
| FAQ questions | 4 | **5** (adds informational question first) |
| Block 2 initial height | 350px | 400px |

**v2 is preferred for new pages** targeting neural answer visibility (Яндекс Нейро, Google AI Overviews).

### Key CSS Classes
- `.gm-section`, `.gm-section-title` — section wrappers
- `.gm-services-grid` — model/category card grid
- `.gm-quick-links`, `.gm-quick-links-tags`, `.gm-quick-link` — quick-links block (section 12)
- `.gm-quick-links-top`, `.gm-quick-links-top-inner` — top quick-links variant (section 12c)
- `.gm-ql-hidden`, `.gm-ql-btn`, `.gm-ql-chevron` — row-overflow JS mechanism
- `.gm-intro-text`, `.gm-faq` — referenced in `speakable.cssSelector` JSON-LD
- `.gm-trust-strip` — 4-icon USP strip
- `.gm-advantages-list` — must use selector `.gm-block .gm-advantages-list` (not just `.gm-advantages-list`) to override CS-Cart's `ul` padding

## Navigation / Footer Menu Blocks

`Меню/` holds site-chrome HTML (not SEO content blocks), deployed as separate CS-Cart Design → Layouts HTML blocks. See `Меню/CLAUDE.md` for file list and conventions.

## SEO Standards

### Hard prohibitions
- `@graph` in JSON-LD — **never use** (CS-Cart incompatible)
- `FAQPage` JSON-LD schema — **prohibited** (Google dropped FAQ rich results for commercial sites Sept 2023; causes GSC errors with no ranking benefit)
- `itemscope itemtype="Product"` on `.gm-services-grid` cards — **prohibited** (triggers GSC critical errors for missing `offers`/`review`/`aggregateRating`)
- `WebPage` in JSON-LD — **prohibited** (CS-Cart generates it automatically)
- Prices in card descriptions or JSON-LD `description` fields — **prohibited**
- `<style>` tags inside any HTML block — **prohibited**
- `transform` on hover buttons — **prohibited** (Chrome bug with `overflow: hidden`)
- Em dash `—` / `&#8212;` — **prohibited** in all HTML content; use en dash `–` / `&#8211;` instead

Rules in `.cursor/rules/` define the authoritative standards. Key rules:

- **`seo--meta-tags.mdc`** — H1/title/description templates and length rules
- **`seo--for-shop-html-block.mdc`** — full HTML block generation standard (research → GEO → E-E-A-T → structure); includes all SVG icon templates, CSS color palette, collapse JS
- **`quick-links-seo-block.mdc`** — quick-links block markup and JS
- **`quick-links-top.mdc`** — top quick-links variant (no H3, bordered, inline button)
- **`seo--for-blog-article.mdc`** — blog SEO block standard
- **`seo-html-copywriting.mdc`** *(alwaysApply)* — premium copywriting rules for product descriptions; governs GEO/AEO style and CSS-only HTML structure
- **`seo-internal-linking-longevity.mdc`** *(alwaysApply)* — internal linking standard for blog: evergreen articles must link only to categories/collections, never to individual product cards

### Meta Tag Rules (summary)
- **H1 (category):** 15–45 chars, UX-first, no geo, no "Купить". Example: `Планшеты Xiaomi`
- **H1 (product page):** 45–70 chars, SEO-commercial, with geo. Example: `Купить Xiaomi 15 Pro в Севастополе — GOODMi`
- **title:** 50–70 chars, format `[Query] — [geo] | GOODMi`; geo in title ≠ geo in H1. Yandex (58% traffic) shows up to ~70 chars fully; Google may truncate `| GOODMi` in the tail — acceptable since the brand is visible in the URL. Default to double-geo `«в Севастополе, Крыму»`; fall back to single geo only when title would exceed ~70 chars.
- **description:** 150–250 chars; first ~155 = key message (USP + models + CTA); 155–250 = extended for AI Overviews
- Cyrillic brand variants (Сяоми, Редми, Поко) — only include if confirmed present in competitors' top-5 titles

### Approved USPs
`гарантия 1 год` · `доставка СДЭК` · `самовывоз в Крыму` · `трейд-ин` · `кредит` · `с 2016 года` · `бонусная программа GOODMi`

### Canonical URL patterns
```
Смартфоны:         https://goodmi.ru/smartfonyi/
Планшеты:          https://goodmi.ru/planshetyi/
Ноутбуки:          https://goodmi.ru/noutbuki/
Смарт-часы:        https://goodmi.ru/smart-chasy/
Наушники (беспроводные): https://goodmi.ru/besprovodnye-naushniki/
Наушники (проводные):    https://goodmi.ru/provodnye-naushniki/
Колонки:           https://goodmi.ru/akustika-i-kolonki/
Пылесосы:          https://goodmi.ru/pylesosy-i-uborka/
Роботы-пылесосы:   https://goodmi.ru/roboty-pylesosy/
Вертикальные пылесосы: https://goodmi.ru/vertikalnye-pylesosy/
Телевизоры:        https://goodmi.ru/televizory-xiaomi/
Умный дом:         https://goodmi.ru/smart-ustroystva-i-umnyiy-dom/
Аксессуары:        https://goodmi.ru/aksessuaryi/
Мониторы:          https://goodmi.ru/monitory-xiaomi/
Повербанки:        https://goodmi.ru/power-banks/
Аэрогриль:         https://goodmi.ru/gril/
Гарантия:          https://goodmi.ru/info/guarantee/
Возврат:           https://goodmi.ru/info/vozvrat-ru/
Доставка:          https://goodmi.ru/info/delivery/
Трейд-ин:          https://goodmi.ru/treyd-in-goodmi/
Кредит:            https://goodmi.ru/credit/
Бонусы:            https://goodmi.ru/bonusnaya-programma/
Блог:              https://goodmi.ru/blog-xiaomi-na-russkom/
```

## Skills (Cursor / Claude workflows)

Skills in `.cursor/skills/` define step-by-step workflows. Always follow their workflow order.

**Claude Code invocation shortcuts** (use `/user:skill-name` syntax in Claude Code — the `user:` prefix groups all GOODMi commands together in the `/` autocomplete list).

Each entry below is a thin proxy `SKILL.md` under `.claude/skills/user:<name>/` that reads the full workflow from `.cursor/skills/<skill>/SKILL.md` and executes it step by step.

| Slash command | Skill | Trigger use case |
|---|---|---|
| `/user:seo-meta` | `seo-meta-builder` | Generate H1 / title / meta description for any store page |
| `/user:seo-meta2` | `seo-meta-builder-v2` | Same, but with neural answer targeting (Яндекс Нейро / AI Overviews) |
| `/user:seo-core-expansion` | `seo-core-expansion` | Expand a category's semantic core via Wordstat (broad+quoted) LSI/word-form discovery, cross-checked against PixelPlus for dupes |
| `/user:seo-shop` | `seo-shop-page-builder` | Full category HTML block v1 (FAQ in Block 2) |
| `/user:seo-shop2` | `seo-shop-page-builder-v2` | Full category HTML block v2 (FAQ in Block 1, 5 questions, `sameAs`) — preferred |
| `/user:seo-description` | `seo-product-description-builder` | Rewrite product description into SEO/GEO/AEO optimized CS-Cart block |
| `/user:seo-quick-links` | `seo-quick-links-builder` | Generate `gm-quick-links` section block (with H3, orange strip) |
| `/user:seo-quick-links-top` | `seo-quick-links-top-builder` | Generate `gm-quick-links-top` block (no title, inline button, bordered) |
| `/user:seo-reviews` | `seo-homepage-reviews-block-builder` | Update homepage reviews витрина (exactly 6 cards) |
| `/user:seo-blog-poster` | `seo-blog-poster-builder` | Generate branded blog post header in Xiaomi/GOODMi style |
| `/user:seo-blog-rewrite` | `seo-blog-rewriter` | Rewrite an existing article preserving keyword density; outputs two-file blog HTML |

All skills: **ask for input first, show analysis, wait for confirmation, then generate HTML.**

**Mandatory blocking steps (do not skip for any shop-page-builder variant):**
- **Step 1b** — ask warranty duration before generating any HTML; no default assumed. Waiting for user answer = block; do not generate final HTML until answered.
- **Step 4** — ask for card image URLs (or explicit «пропустить») before generating `.gm-services-grid`. Silence ≠ «пропустить».

### Text-shortcut triggers

A bare one-word message (with or without trailing `.`) launches the corresponding workflow end-to-end — gather missing inputs, generate, save the file, and reply in chat with only a short confirmation + file path (no need to restate the whole skill output):

| Shortcut | Rule | Skill invoked | Saves to |
|---|---|---|---|
| `Категория` | `category-shortcut-trigger.mdc` | `seo-shop-page-builder` | `Категории/Новые/` |
| `Описание` | `description-shortcut-trigger.mdc` | `seo-product-description-builder` | `Описания товаров/OPISANIE-[BRAND]-[MODEL]-GOODMI.html` |
| `Отзывы` | `reviews-shortcut-trigger.mdc` | `seo-homepage-reviews-block-builder` | `Страницы/HOMEPAGE-REVIEWS-SHORT-BLOCK-2026-03-24.html` |
| `блог` | `blog-shortcut-trigger.mdc` | blog article workflow | `Блог/` |

If the triggering message already includes the needed data (category/model, competitor links, warranty terms, image URLs, etc.), skip straight to generation and only ask for genuinely missing required fields — but the blocking steps above (warranty, card images) still apply for `Категория`.

## Git Commit Messages

Enforced by `AGENTS.md` and `.cursor/rules/git-commit-language.mdc` (`alwaysApply: true`):

- Always write commit messages **in Russian**, 1–2 sentences, no English text in the body — even if the diff/filenames are in English.
- Focus on *why*, not a file-by-file listing.
- No Conventional Commits prefixes (`feat:`, `fix:`, etc.); if a prefix is needed, use Russian (`Добавлено:`, `Исправлено:`).

## MCP Server (`cscart-mcp-server/`)

Node.js MCP server exposing CS-Cart API as MCP tools. See `cscart-mcp-server/CLAUDE.md` for full details and dev commands.

Requires `.env` with `CSCART_API_URL`, `CSCART_API_EMAIL`, `CSCART_API_KEY`. PixelPlus SEO tools also require `PIXELPLUS_API_TOKEN`.

**PixelPlus project for goodmi.ru:** `project_id = 63589`. Used in `seo-meta-builder-v2` Step 6a to submit the semantic core after user confirmation. Tier 0 (informational/neural queries) are NOT submitted to PixelPlus — tracked separately via the Step 6b monitoring checklist.
