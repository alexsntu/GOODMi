---
name: seo-product-description-builder
description: Rewrites product description HTML for GOODMi into SEO/GEO/AEO optimized CSS-only markup. Use when user asks to create, rework, optimize, or format product descriptions and wants output ready for CS-Cart without inline styles.
---

# SEO Product Description Builder — GOODMi

Transforms raw product description HTML into a production-ready GOODMi block with:
- premium copywriting (Apple-like concise style),
- SEO commercial semantics,
- GEO and AEO optimization,
- strict CSS-only markup using existing classes from `CSS/goodmi-styles.css`.

## Workflow (execute in order)

### Step 1 — Parse input

Extract from the user message:
- Product name and series (e.g., Xiaomi 15 Ultra)
- Product type (smartphone/tablet/watch/etc.)
- Source HTML (if provided)
- Image URLs (if provided)
- Any constraints (tone, length, required sections)

If source HTML is missing, ask for:
1) product name,
2) 3-6 key features,
3) 1-6 image URLs (or "без фото"),
4) desired output file path (default folder: `Описания товаров/`).

### Step 2 — Enforce CSS-only policy

Before generation, validate constraints:
- Use only classes already present in `CSS/goodmi-styles.css`
- No `<style>` blocks
- No inline `style=""`
- No custom class inventions outside existing CSS

If a visual pattern is needed, prefer existing classes:
- `gm-block`, `gm-section`, `gm-section-title`, `gm-section-lead`
- `gm-faq`, `gm-faq-item`, `gm-faq-question`, `gm-faq-answer`
- `gm-article-body` for text+image area with rounded images from existing CSS

### Step 3 — SEO/GEO/AEO content plan

Build text with these requirements:

**SEO**
- Commercial intent first (buy/price/official warranty/value)
- Natural keyword inclusion (no stuffing)
- Scannable structure (short paragraphs, strong benefits)
- Before adding any internal links, always ask the user for exact URLs. Never invent URLs yourself and never insert unconfirmed links to avoid 404 pages.

### Step 3.1 — Mandatory internal linking workflow

This step is REQUIRED for every product description task.

1. Analyze and propose 2-4 relevant stable destination types for internal linking, for example:
   - main product category (same device type),
   - adjacent accessory category,
   - lineup/series hub or collection,
   - warranty/service/info section.
2. Ask the user to provide exact URLs for these destinations or to send their own alternatives.
3. If URLs are not provided, do not place any `<a>` links in the final HTML.
4. Use only user-provided URLs from the current chat.
5. Never reuse or infer URLs from:
   - memory,
   - previous chats/transcripts,
   - old templates/files,
   - guessed slugs.

**GEO**
- First paragraph must contain:
  1. GOODMi,
  2. Севастополь/Крым,
  3. product name/series,
  4. transactional action ("купить", "в наличии"),
  5. purchase condition (warranty/delivery/trade-in)

**AEO**
- Add FAQ section with 3-5 questions users actually ask
- First sentence in each answer is a direct answer
- Each FAQ answer target length: 40-80 words

### Step 4 — Generate HTML structure

Use this skeleton:

```html
<article class="apple-product-description gm-block">
  <div class="gm-article-body">
    <section class="product-feature-block gm-section">
      <h3 class="gm-section-title">...</h3>
      <p>...</p>
      <figure>
        <img ...>
      </figure>
    </section>
    <section class="gm-faq gm-section" aria-label="FAQ">
      ...
    </section>
  </div>
</article>
```

Image rules:
- First image: `fetchpriority="high"`, without `loading="lazy"`
- Other images: `loading="lazy"`
- No width/height attrs
- No inline styles
- Images must be inside `.gm-article-body` so existing CSS applies rounded corners (`border-radius`)

### Step 5 — Final validation checklist

- [ ] No `<style>` tags
- [ ] No `style=""` attributes
- [ ] Only existing classes from `goodmi-styles.css`
- [ ] No H1 inside block (only H3 headings in sections)
- [ ] FAQ present and AEO-compliant
- [ ] Intro paragraph includes GEO 5-entity pattern
- [ ] Internal linking step completed: proposed 2-4 destination types and requested exact URLs from user
- [ ] No internal links added unless user provided exact URLs in current chat
- [ ] If links are present, every URL is user-provided and points to stable sections (category/hub/collection/info)
- [ ] No `.gm-trust-strip`, `.gm-highlight`, `.gm-cta` blocks in product description output
- [ ] Images are inside `.gm-article-body` (rounded corners from existing CSS)
- [ ] There is no standalone CTA/promo section in the final product description
- [ ] Final output is one HTML code block (when replying in chat)
- [ ] All internal link URLs were provided/confirmed by the user (no invented URLs)

### Step 6 — Save and response behavior

Default save target:
- Always save final HTML to `Описания товаров/` unless user explicitly requests another folder.
- Required filename pattern: `OPISANIE-[BRAND]-[MODEL]-GOODMI.html`
- Filename normalization:
  - Use uppercase Latin for `BRAND` and `MODEL`
  - Replace spaces and punctuation with `-`
  - Keep only `A-Z`, `0-9`, and `-`
  - Collapse repeated dashes to one dash
  - Example: `Xiaomi 17 Ultra` -> `OPISANIE-XIAOMI-17-ULTRA-GOODMI.html`

If user requested file output:
- Save generated HTML to requested path (if not provided, use `Описания товаров/`)
- Return short confirmation and the saved path

If user requested inline output:
- Return only one HTML code block without extra commentary
- If possible, also save a file copy in `Описания товаров/` and include path only when user asked for file path
