---
name: seo-homepage-reviews-block-builder
description: Builds/updates the GOODMi homepage “Reviews buyers” витрина (exactly 6 cards) by asking user for 6 reviews and replacing only card data in HOMEPAGE-REVIEWS-SHORT-BLOCK-2026-03-24.html.
---

# SEO Homepage Reviews Block Builder — GOODMi

## Workflow (execute in order)
### Step 1 — Parse request
Detect that the user wants to update the homepage reviews block (витрина отзывов).
Base file:
- `HOMEPAGE-REVIEWS-SHORT-BLOCK-2026-03-24.html`

### Step 2 — Ask for 6 reviews (mandatory)
Перед карточками запроси данные для блока рейтинга:
1. `ratingValue` — оценка (например `5.0`)
2. `ratingBasedReviewsCount` — количество оценок для текста `На основе {COUNT} оценок`

Request exactly 6 items in this format for each review:
1. `authorName` — Имя пользователя
2. `avatarUrl` — Картинка пользователя (URL)
3. `source` — источник отзыва (выбирается только из списка):
   - `Авито`
   - `Яндекс Карты`
   - `ВКонтакте`
   - `Отзовик`
   - `Сайт`
4. `date` — дата отзыва
5. `stars` — количество звёзд (целое 1..5)
6. `reviewText` — текст отзыва

Important:
- `source` must be one of the 5 options above.
- If the user не указал URL аватара, ask to provide it (avoid broken images).

### Step 3 — Generate/Update HTML
Using rule `seo-homepage-reviews-block.mdc`:
1. Keep the rest of the file stable: sources strip, CTA, and the end `<script>` logic.
2. Update the rating summary block:
   - set ratingValue
   - set ratingBasedReviewsCount → `На основе COUNT оценок`
3. Replace only the 6 review cards inside `.gm-services-grid`:
   - set avatar + authorName + date + reviewText
   - set stars rating (X из 5) in `.gm-review-stars` using exactly 5 stars:
     - for positions 1..X use `<span class="gm-review-star gm-review-star--filled">★</span>`
     - for positions X+1..5 use `<span class="gm-review-star gm-review-star--empty">★</span>`
   - set the source icon using `source → icon mapping`
   - in `.gm-review-source-anchor` place icon image and static label `Посмотреть →`
   - set `href` for source using `source → URL mapping`
4. Update JSON-LD in the same file:
   - for every `Review` object add:
     - `"itemReviewed": { "@id": "https://goodmi.ru/#organization" }`
   - do not leave any `Review` without `itemReviewed` (GSC critical error otherwise).
5. Ensure:
   - exactly 6 cards
   - each card contains:
     - `.gm-review-text-collapsed` wrapper
     - `.gm-review-show-more-btn` button
     - `.gm-review-source-anchor` link

### Step 4 — Output format (new file version)
Output the full updated content for:
- `HOMEPAGE-REVIEWS-SHORT-BLOCK-2026-03-24.html`

No additional explanations—only the updated file content.

---

## Self-check
- All 6 reviews appear (1..6).
- Each card uses the correct icon for its `source`.
- No changes to class names or JS structure.
- JSON-LD: all `Review` entities include `itemReviewed` linked to `https://goodmi.ru/#organization`.

