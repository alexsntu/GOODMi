Run the seo-meta-builder-v2 skill for GOODMi store pages (v2 — with neural answer targeting).

Arguments (optional): $ARGUMENTS

Invoke the `seo-meta-builder-v2` skill. If $ARGUMENTS is provided, treat it as the page or product description to generate meta tags for. If no arguments are given, ask the user which page or product they need meta tags for before starting the workflow.

Key differences from /seo-meta (v1):
- Detects informational intent and activates Step 2d (neural answer SERP research)
- Strict WebPage.description rules: must not start with "GOODMi"/"Мы", must contain a concrete number
- sameAs added to LocalBusiness JSON-LD (Яндекс Карты link)
- Step 6b: manual neural answer monitoring checklist
- Bridge to seo-shop-page-builder-v2 in Step 7
