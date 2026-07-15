Run the seo-shop-page-builder-v2 skill for GOODMi store category pages (v2 — with neural answer targeting).

Arguments (optional): $ARGUMENTS

Invoke the `seo-shop-page-builder-v2` skill. If $ARGUMENTS is provided, treat it as the product category or model to generate an HTML block for. If no arguments are given, ask the user which category or model they need an SEO block for before starting the workflow.

Key differences from v1 (seo-shop-page-builder):
- FAQ moved to Block 1 (always visible, not behind collapse)
- 5 FAQ questions instead of 4: informational question added first ("Что важно учесть при выборе...")
- sameAs in LocalBusiness JSON-LD (Яндекс Карты link) — mandatory
- Step 3c: optional HowTo JSON-LD templates for "Как купить" and "Как сдать по трейд-ин"
- Concrete numbers required in .gm-intro-text (6 точек, с 2016 года, etc.)
- WebPage.description must not start with "GOODMi"/"Мы"
- Block 2 collapse contains only advantages + CTA (no FAQ)
