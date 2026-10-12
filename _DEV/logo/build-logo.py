import io, re, sys
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.boundsPen import BoundsPen
from fontTools.pens.transformPen import TransformPen
from fontTools.svgLib.path import parse_path
from fontTools.pens.recordingPen import RecordingPen

src = io.open('logo-orig.svg', encoding='utf-8').read()
elem = re.compile(r'[ \t]*<(path|polygon)\b[^>]*class="fil2"[^>]*/>\n?')
old = list(elem.finditer(src))
xmin = ymin = 1e9; xmax = ymax = -1e9
for m in old:
    tag = m.group(0)
    if m.group(1) == 'path':
        rec = RecordingPen(); parse_path(re.search(r'\sd="([^"]+)"', tag).group(1), rec)
        bp = BoundsPen(None); rec.replay(bp); b = bp.bounds
    else:
        pts = [tuple(map(float, p.split(','))) for p in re.search(r'points="([^"]+)"', tag).group(1).split()]
        b = (min(p[0] for p in pts), min(p[1] for p in pts), max(p[0] for p in pts), max(p[1] for p in pts))
    xmin = min(xmin, b[0]); ymin = min(ymin, b[1]); xmax = max(xmax, b[2]); ymax = max(ymax, b[3])
print('старая подпись: букв-кривых %d, x %.1f..%.1f, y %.1f..%.1f' % (len(old), xmin, xmax, ymin, ymax))

font = TTFont('roboto_regular.woff2')
cmap = font.getBestCmap(); gs = font.getGlyphSet(); upm = font['head'].unitsPerEm; hmtx = font['hmtx']
text = sys.argv[1] if len(sys.argv) > 1 else 'электроника и гаджеты'
missing = [c for c in text if ord(c) not in cmap]
assert not missing, 'в шрифте нет символов: %r' % missing
def measure(t):
    x = 0; bx0 = None; bx1 = 0; by0 = 1e9; by1 = -1e9
    for c in t:
        g = cmap[ord(c)]; bp = BoundsPen(gs); gs[g].draw(bp)
        if bp.bounds:
            if bx0 is None: bx0 = x + bp.bounds[0]
            bx1 = x + bp.bounds[2]; by0 = min(by0, bp.bounds[1]); by1 = max(by1, bp.bounds[3])
        x += hmtx[g][0]
    return bx0, bx1, by0, by1
ox0, ox1, oy0, oy1 = measure('фирменный магазин')
nx0, nx1, ny0, ny1 = measure(text)
old_scale = (xmax - xmin) / (ox1 - ox0)
baseline = ymax + oy0 * old_scale
print('проверка шрифта: высота старой подписи по кривым %.1f, по Roboto при том же масштабе %.1f (совпадение = шрифт тот же)' % (ymax - ymin, (oy1 - oy0) * old_scale))
scale = (xmax - xmin) / (nx1 - nx0)
print('старая: кегль %.0f px; новая: кегль %.0f px (%.0f%% от прежнего); базовая линия y=%.1f' % (old_scale * upm, scale * upm, 100 * scale / old_scale, baseline))
paths = []; x = 0
for c in text:
    g = cmap[ord(c)]
    pen = SVGPathPen(gs, ntos=lambda v: ('%.2f' % v).rstrip('0').rstrip('.'))
    gs[g].draw(TransformPen(pen, (scale, 0, 0, -scale, xmin + (x - nx0) * scale, baseline)))
    if pen.getCommands():
        paths.append(pen.getCommands())
    x += hmtx[g][0]
new_d = ' '.join(paths)
out = src[:old[0].start()] + '  <path class="fil2" d="' + new_d + '"/>\n' + src[old[-1].end():]
# между первой и последней буквой других элементов нет – проверяем
assert not re.search(r'class="fil[01]"', src[old[0].start():old[-1].end()])
io.open('logo-new.svg', 'w', encoding='utf-8').write(out)
rec = RecordingPen(); parse_path(new_d, rec); bp = BoundsPen(None); rec.replay(bp)
print('новая подпись: x %.1f..%.1f, y %.1f..%.1f' % (bp.bounds[0], bp.bounds[2], bp.bounds[1], bp.bounds[3]))
print('размер файла: было %d, стало %d байт; осталось fil2-элементов: %d' % (len(src.encode()), len(out.encode()), out.count('class="fil2"')))
