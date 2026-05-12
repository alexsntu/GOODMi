# -*- coding: utf-8 -*-
"""Restructure GOODMi category HTML: SEO block (intro+FAQ) outside collapse per seo-shop-page-builder."""
from __future__ import annotations

import re
import sys
from pathlib import Path

SKIP = {"smartfonyi-category.html"}

PREFIX_RE = re.compile(
    r"^\s*<div class=\"gm-collapse-wrapper\">\s*"
    r"<div class=\"gm-collapse-content\">\s*"
    r"<div class=\"gm-block\"(?:\s+itemscope itemtype=\"https://schema.org/WebPage\")?\s*>\s*",
    re.DOTALL,
)

INTRO_RE = re.compile(
    r"^(?:\s*<!--[^>]*-->\s*)*<section class=\"gm-section gm-intro\"[^>]*>.*?</section>\s*",
    re.DOTALL,
)

FAQ_RE = re.compile(
    r"<section class=\"gm-section gm-faq\"[^>]*>.*?</section>\s*"
    r"|<section class=\"gm-faq\"[^>]*>.*?</section>\s*",
    re.DOTALL,
)


def extract_outermost_div(html: str, pos: int) -> str | None:
    if pos < 0 or not html.startswith("<div", pos):
        return None
    depth = 0
    i = pos
    n = len(html)
    while i < n:
        if html.startswith("<div", i) and (i + 4 >= n or html[i + 4] in " \t\n\r/>"):
            depth += 1
            i += 1
            continue
        if html.startswith("</div>", i):
            depth -= 1
            i += 6
            if depth == 0:
                return html[pos:i]
            continue
        i += 1
    return None


def split_script(html: str) -> tuple[str, str]:
    marker = "function gmBlockToggle"
    ti = html.find(marker)
    if ti == -1:
        raise ValueError("gmBlockToggle not found")
    script_start = html.rfind("<script>", 0, ti)
    if script_start == -1:
        raise ValueError("<script> before gmBlockToggle not found")
    return html[:script_start].rstrip(), html[script_start:]


def fix_toggle_script(script: str) -> str:
    script = script.replace(
        "if (label) label.textContent = 'Показать больше';",
        "if (label) label.textContent = 'Популярные модели и условия покупки';",
    )
    script = script.replace(
        'if (label) label.textContent = "Показать больше";',
        'if (label) label.textContent = "Популярные модели и условия покупки";',
    )
    return script


def restructure_main(main: str) -> str:
    m = PREFIX_RE.match(main)
    if not m:
        raise ValueError("Expected gm-collapse-wrapper > gm-collapse-content > gm-block WebPage prefix")

    rest = main[m.end() :]
    intro_m = INTRO_RE.match(rest)
    if not intro_m:
        raise ValueError("gm-intro section not found at start of inner block")

    intro = intro_m.group(0).rstrip() + "\n\n"
    tail = rest[intro_m.end() :]

    faq_m = FAQ_RE.search(tail)
    if not faq_m:
        raise ValueError("gm-faq section not found")

    middle = tail[: faq_m.start()].rstrip() + "\n\n"
    faq = faq_m.group(0).rstrip() + "\n\n"
    after_faq = tail[faq_m.end() :].lstrip()

    cta_pos = after_faq.find('<div class="gm-cta"')
    if cta_pos == -1:
        raise ValueError("gm-cta block not found")

    cta = extract_outermost_div(after_faq, cta_pos)
    if not cta:
        raise ValueError("Could not parse gm-cta div")

    # After CTA: original has "</div>" closing gm-block + fade + ... — do not lstrip
    # remainder (lstrip would break indentation of gm-collapse-fade).
    remainder = after_faq[len(cta) :]
    close_block_m = re.match(r"\s*</div>(?:\s*<!--[^>]*-->)?\s*", remainder)
    if not close_block_m:
        raise ValueError("Expected closing </div> for gm-block after CTA")
    remainder = remainder[close_block_m.end() :]

    block1 = (
        "<!-- Блок 1: Всегда видимый — SEO-критичный (интро + FAQ) -->\n"
        '<div class="gm-block" itemscope itemtype="https://schema.org/WebPage">\n'
        f"{intro}"
        f"{faq}"
        "</div><!-- /.gm-block (SEO-блок: всегда видим) -->\n\n"
        "<!-- Блок 2: Коллапс — визуальные секции (карточки, преимущества, CTA) -->\n"
        '<div class="gm-collapse-wrapper">\n'
        '  <div class="gm-collapse-content">\n'
        "    <div class=\"gm-block\">\n"
        f"{middle}"
        f"{cta.rstrip()}\n"
        "    </div><!-- /.gm-block (коллапс-блок: карточки + преимущества + CTA) -->\n"
        f"{remainder}"
    )

    # Collapse button label
    block1 = block1.replace(
        '<span class="gm-collapse-btn-text">Показать больше</span>',
        '<span class="gm-collapse-btn-text">Популярные модели и условия покупки</span>',
    )
    return block1


def process_file(path: Path) -> bool:
    if path.name in SKIP:
        return False
    text = path.read_text(encoding="utf-8")
    if "<!-- Блок 1:" in text and "SEO-блок: всегда видим" in text:
        return False
    if "gm-collapse-wrapper" not in text:
        return False

    script_end = text.find("</script>")
    if script_end == -1:
        raise ValueError(f"No </script> in {path}")
    head = text[: script_end + len("</script>")]
    body = text[script_end + len("</script>") :].lstrip("\n")

    main_html, script_part = split_script(body)
    new_main = restructure_main(main_html)
    new_script = fix_toggle_script(script_part)
    path.write_text(head + "\n\n" + new_main + "\n" + new_script, encoding="utf-8")
    return True


def main() -> int:
    root = Path(__file__).resolve().parent.parent / "Категории" / "Готовые"
    if not root.is_dir():
        print("Directory not found:", root)
        return 1
    done = 0
    for p in sorted(root.glob("*.html")):
        try:
            if process_file(p):
                print("OK", p.name)
                done += 1
            else:
                print("SKIP", p.name)
        except Exception as e:
            print("FAIL", p.name, e)
            return 1
    print("Updated", done, "files")
    return 0


if __name__ == "__main__":
    sys.exit(main())
