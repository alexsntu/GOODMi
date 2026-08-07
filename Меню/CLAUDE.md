# CLAUDE.md — Меню/

Guidance for files under this directory. See the root `CLAUDE.md` for store constants, general SEO standards, and skill workflows.

`Меню/` holds site-chrome HTML (not SEO content blocks) — deployed as separate CS-Cart Design → Layouts HTML blocks, one per file/location. Different architecture from the category/page blocks documented in the root file: these files carry **no inline `<style>`** — all their CSS lives in `CSS/goodmi-styles.css` (sections 16–17, `.goodmi-*` classes), loaded globally via Дизайн → Темы → Свой CSS. Footer files use Smarty template syntax for links (`{"..."|fn_url}`, `page_id=N`) but no longer need `{literal}...{/literal}` since there's no `<style>` block left to protect from the Smarty parser.

`Контакты.html` (desktop) and `Контакты МОБ.html` (mobile) share the base class `.goodmi-footer-contacts` but render on the same page simultaneously (theme toggles visibility via CSS breakpoints) — the mobile file adds a `.goodmi-footer-contacts--mobile` modifier class on its wrapper `<div>` so the two don't collide in the shared stylesheet. Keep that modifier when editing either file.

```
Меню/Дополнительное меню/menu-desktop.html   # Desktop catalog nav — 10 flat items + adaptive "ещё" overflow menu; CSS/JS scaffolding for mega-panels ready (add via data-mega-trigger/data-mega-panel, no script changes needed)
Меню/Футер/Информация.html                    # Legal links (оферта, конфиденциальность, карта сайта) + Yandex Maps rating widget
Меню/Футер/Контакты.html                      # Desktop footer contacts: phone, email, socials (MAX/VK/TG/Zen/Rutube/YouTube), address
Меню/Футер/Контакты МОБ.html                  # Mobile variant of the above — always expanded, not collapsed into an accordion
Меню/Футер/О нас.html                         # Footer column: отзывы, почему мы, о нас, бренды, сервис, контакты, как оставить отзыв
Меню/Футер/Покупателям.html                   # Footer column: каталог, блог, акции, бонусы, доставка, возврат, гарантия, кредит, трейд-ин, FAQ
```

Shared conventions across all files in `Меню/`:
- CSS custom properties `--goodmi-text-link` (#a1a1a6), `--goodmi-accent` (#ff6900), `--goodmi-font` — scoped per block, dark-footer-background palette
- Underline-on-hover link effect (`::before` pseudo-element, orange, left-to-right)
- Store constants (phone, email, address, socials, `sameAs`) must match the verbatim values in the root `CLAUDE.md`'s Project Overview section
- Em dash prohibition applies to visible copy, not CSS/JS comments — comments in `menu-desktop.html` use `—` freely and that's fine
