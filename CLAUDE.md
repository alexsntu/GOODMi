# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the SEO content repository for **GOODMi** (`goodmi.ru`) — a Xiaomi specialty store in Crimea (Sevastopol, Simferopol, Yalta) with Russia-wide CDEK delivery. The repo contains HTML SEO blocks deployed into a CS-Cart e-commerce platform, plus a companion MCP server (`cscart-mcp-server/`) for programmatic CS-Cart API access.

**Store constants (use verbatim in all content):**
- Brand: `GOODMi` (never Goodmi, never GOODMI)
- Phone: `8 (800) 250-17-00` / `href="tel:+78002501700"`
- Email: `store@goodmi.ru`
- Hours: daily 10:00–21:00
- Main address: `г. Севастополь, ул. Вакуленчука, 29 — ТЦ «Муссон»`; self-pickup at 6 points in Crimea (Sevastopol 4×, Simferopol, Yalta)
- Since: copywriting uses `«с 2015 года»` / `«более 10 лет на рынке»`; JSON-LD `LocalBusiness.description` uses `«с 2016 года»` — do not change either without user confirmation
- Speciality: «фирменный магазин техники Xiaomi в Крыму»
- Prohibited: «официальный», «авторизованный» when describing the store
- Commercial terms: use «кредит», never «рассрочка без переплат»

## Repository Structure

```
Категории/Готовые/     # Ready-to-deploy category SEO HTML blocks
Акции/                 # Promotion page HTML
Блог/                  # Blog article SEO blocks
Страницы/              # Standalone page HTML (trade-in, VPN, reviews, etc.)
Быстрые ссылки/        # Quick-links blocks (separate files, never embedded in category files)
CSS/                   # goodmi-styles.css (shared), maxmobiles-styles.css
Мета-теги/             # Meta tag drafts
tools/                 # Utility scripts: restructure_category_two_block.py, fix_faq_mass.py, fix_faq_schema.py
cscart-mcp-server/     # MCP server for CS-Cart API (Node.js/Jest)
.cursor/rules/         # Cursor rules (MDC) defining HTML/SEO standards
.cursor/skills/        # Reusable skill definitions for content generation workflows
```

## HTML Block Architecture

All category and page blocks follow a shared structure:

1. **JSON-LD schema** (top) — `LocalBusiness`, `WebPage`, `FAQPage`, `BreadcrumbList`, and optionally `ItemList` for product schemas. Every `Review` entity must include `"itemReviewed": { "@id": "https://goodmi.ru/#organization" }`.
2. **CSS classes** — all styles come from `goodmi-styles.css`; never write `<style>` tags inline.
3. **Two-file split per page** — `[slug].html` (wysiwyg HTML only, no `<script>`) + `[slug]-jsonld.html` (JSON-LD only, placed in a separate Layout block). **Reason:** CS-Cart duplicates every `<script>` tag in wysiwyg descriptions, causing GSC duplicate-schema errors.
4. **Quick-links blocks** — always saved as separate files (`[category]-quick-links.html`, `[category]-quick-links-top.html`), never embedded in the category SEO file.
5. **No emojis** — use HTML entities instead (`&#8211;`, `&#215;`, etc.). Use inline SVG for all icons — TinyMCE in CS-Cart corrupts Unicode entities above U+00FF (renders as `???` in DB).
6. **Block 1 / Block 2 split** — Block 1 (intro + trust strip + cards grid) is always visible; Block 2 (advantages + FAQ + CTA) is wrapped in `.gm-collapse-wrapper` with `max-height: 350px`. Only Block 1 carries `itemscope itemtype="https://schema.org/WebPage"`.

### Key CSS Classes
- `.gm-section`, `.gm-section-title` — section wrappers
- `.gm-services-grid` — model/category card grid
- `.gm-quick-links`, `.gm-quick-links-tags`, `.gm-quick-link` — quick-links block (section 12)
- `.gm-quick-links-top`, `.gm-quick-links-top-inner` — top quick-links variant (section 12c)
- `.gm-ql-hidden`, `.gm-ql-btn`, `.gm-ql-chevron` — row-overflow JS mechanism
- `.gm-intro-text`, `.gm-faq` — referenced in `speakable.cssSelector` JSON-LD

## SEO Standards

### Hard prohibitions
- `@graph` in JSON-LD — **never use** (CS-Cart incompatible)
- `FAQPage` JSON-LD schema — **prohibited** (Google dropped FAQ rich results for commercial sites Sept 2023; causes GSC errors with no ranking benefit)
- `itemscope itemtype="Product"` on `.gm-services-grid` cards — **prohibited** (triggers GSC critical errors for missing `offers`/`review`/`aggregateRating`)
- `WebPage` in JSON-LD — **prohibited** (CS-Cart generates it automatically)
- Prices in card descriptions or JSON-LD `description` fields — **prohibited**

Rules in `.cursor/rules/` define the authoritative standards. Key rules:

- **`seo--meta-tags.mdc`** — H1/title/description templates and length rules
- **`seo--for-shop-html-block.mdc`** — full HTML block generation standard (research → GEO → E-E-A-T → structure)
- **`quick-links-seo-block.mdc`** — quick-links block markup and JS
- **`quick-links-top.mdc`** — top quick-links variant (no H3, bordered, inline button)
- **`seo--for-blog-article.mdc`** — blog SEO block standard

### Meta Tag Rules (summary)
- **H1 (category):** 15–45 chars, UX-first, no geo, no "Купить". Example: `Планшеты Xiaomi`
- **H1 (product page):** 45–70 chars, SEO-commercial, with geo. Example: `Купить Xiaomi 15 Pro в Севастополе — GOODMi`
- **title:** 50–65 chars, format `[Query] — [geo] | GOODMi`; geo in title ≠ geo in H1
- **description:** 150–250 chars; first ~155 = key message (USP + models + CTA); 155–250 = extended for AI Overviews
- Cyrillic brand variants (Сяоми, Редми, Поко) — only include if confirmed present in competitors' top-5 titles

### Approved USPs
`гарантия 1 год` · `доставка СДЭК` · `самовывоз в Крыму` · `трейд-ин` · `кредит` · `с 2016 года` · `бонусная программа GOODMi`

## Skills (Cursor / Claude workflows)

Skills in `.cursor/skills/` define step-by-step workflows. Always follow their workflow order:

| Skill | Trigger use case |
|---|---|
| `seo-meta-builder` | Generate H1 / title / meta description for any store page |
| `seo-shop-page-builder` | Full category HTML block (competitor research → GEO/AEO → HTML + JSON-LD) |
| `seo-product-description-builder` | Rewrite product description into SEO/GEO/AEO optimized CS-Cart block |
| `seo-quick-links-builder` | Generate `gm-quick-links` section block (with H3, orange strip) |
| `seo-quick-links-top-builder` | Generate `gm-quick-links-top` block (no title, inline button, bordered) |
| `seo-homepage-reviews-block-builder` | Update homepage reviews витрина (exactly 6 cards) |

All skills: **ask for input first, show analysis, wait for confirmation, then generate HTML.**

**`seo-shop-page-builder` mandatory blocking steps** (do not skip):
- **Step 1b** — ask warranty duration before generating any HTML; no default assumed
- **Step 4** — ask for card image URLs (or explicit «пропустить») before generating `.gm-services-grid`

## MCP Server (`cscart-mcp-server/`)

Node.js MCP server exposing CS-Cart API as MCP tools. See `cscart-mcp-server/CLAUDE.md` for full details.

```bash
cd cscart-mcp-server
npm start        # production
npm run dev      # nodemon watch mode
npm test         # Jest tests
```

Requires `.env` with `CSCART_API_URL`, `CSCART_API_EMAIL`, `CSCART_API_KEY`.
