import os
import re

BASE = r'C:\Users\Алекс\GOODMi\Категории'

category_files = []
jsonld_files = []

for folder in ['Перезалитые', 'Готовые']:
    d = os.path.join(BASE, folder)
    for f in os.listdir(d):
        path = os.path.join(d, f)
        if f.endswith('-category.html'):
            category_files.append(path)
        elif f.endswith('-jsonld.html'):
            jsonld_files.append(path)

# ── 1. Remove itemscope / itemprop / itemtype from FAQ sections ──────────────
# Strategy: strip the three attributes wherever they appear inside the gm-faq section.
# We remove them from any tag inside the FAQ block.
ATTR_RE = re.compile(
    r'\s+(?:itemscope|itemprop="[^"]*"|itemtype="[^"]*")',
    re.IGNORECASE
)

cat_changed = 0
cat_skipped = 0

for path in category_files:
    with open(path, 'r', encoding='utf-8') as f:
        original = f.read()

    # Only process within gm-faq sections to be safe
    # Find gm-faq block and strip microdata attrs from it
    def strip_microdata_in_faq(html):
        # Split on gm-faq sections
        parts = re.split(r'(class="gm-faq".*?</section>)', html, flags=re.DOTALL)
        result = []
        for i, part in enumerate(parts):
            if i % 2 == 1:  # inside gm-faq block (the captured group)
                part = ATTR_RE.sub('', part)
            result.append(part)
        return ''.join(result)

    modified = strip_microdata_in_faq(original)

    if modified != original:
        with open(path, 'w', encoding='utf-8', newline='') as f:
            f.write(modified)
        print(f'FIXED (microdata): {os.path.basename(path)}')
        cat_changed += 1
    else:
        print(f'OK (no microdata):  {os.path.basename(path)}')
        cat_skipped += 1

print(f'\nCategory files: {cat_changed} fixed, {cat_skipped} already clean\n')

# ── 2. Remove FAQPage script blocks from jsonld files ───────────────────────
# Pattern: <script type="application/ld+json">\n{\n  "@context": ...\n  "@type": "FAQPage" ...}\n</script>
FAQPAGE_RE = re.compile(
    r'<script type="application/ld\+json">\s*\{[^{}]*"@type"\s*:\s*"FAQPage"[^<]*\}\s*</script>\s*\n?',
    re.DOTALL
)

jld_changed = 0
jld_skipped = 0

for path in jsonld_files:
    with open(path, 'r', encoding='utf-8') as f:
        original = f.read()

    modified = FAQPAGE_RE.sub('', original)
    # Clean up any double blank lines left behind
    modified = re.sub(r'\n{3,}', '\n\n', modified)

    if modified != original:
        with open(path, 'w', encoding='utf-8', newline='') as f:
            f.write(modified)
        print(f'FIXED (FAQPage):   {os.path.basename(path)}')
        jld_changed += 1
    else:
        print(f'OK (no FAQPage):   {os.path.basename(path)}')
        jld_skipped += 1

print(f'\nJsonld files: {jld_changed} fixed, {jld_skipped} already clean')
