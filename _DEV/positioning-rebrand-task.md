# Ребрендинг позиционирования: «магазин электроники и гаджетов в Крыму»

**Статус:** стандарт зафиксирован (2026-09-03). Правка файлов — в процессе, по одному файлу за раз.

**2026-09-04 — исправление:** первый скан (regex без учёта падежных окончаний) нашёл только 86 файлов. Пользователь заметил пропуск («крупнейшим фирменным магазином техники Xiaomi» — творительный падеж не совпадал с паттерном). Пересканировано паттерном `фирменн\w*[^.]{0,40}(Xiaomi|Сяоми)` (без учёта регистра) — реальный список **130 файлов**. Таблица 1 ниже — исправленная, полная.

## Новый стандарт (зафиксирован)

- **Основная фраза везде:** «магазин электроники и гаджетов в Крыму» (заменяет старую «магазин гаджетов и умной техники в Крыму» и «фирменный магазин техники Xiaomi»)
- **«умная техника»** — НЕ используется как повсеместный boilerplate (Wordstat: ~65 показов на весь Крым). Точечно — только в IoT/smart-категориях: умный дом, роботы-пылесосы, смарт-часы, IP-камеры.
- Превосходная степень («крупнейшим») в связке с брендом тоже убирается — не только «фирменный».
- **Обоснование (Wordstat regions 959/146/977):** «магазин электроники» — 5002 показа в Крыму; «магазин гаджетов» — 1263, но affinity-индекс 1343 (аномально высокий локальный резонанс); «умная техника» — 65 показов на весь Крым.

## Что уже обновлено (эталонные источники)

- [x] `CLAUDE.md`, `.agents/product-marketing.md`
- [x] `seo-meta-builder-multibrand` + `seo-shop-page-builder-multibrand` (обе копии — `.cursor/skills/` и `.agents/skills/`)
- [x] `Категории/Новые/smartfonyi.html` + `-jsonld.html`, `Категории/Новые/igrovye-pristavki.html` + `-jsonld.html` (сгенерированы до смены стандарта, поправлены следом)

Legacy-скиллы (v1/v2) не трогали — reference-only, не используются для новых генераций.

## Процесс правки файлов

**Строго по одному файлу за раз** (подтверждено пользователем 2026-09-04 — важно видеть, что сделано и что перезалито, даже когда список вырос до 130). Обрабатываю → показываю (текст + новые title/description/JSON-LD description в чат) → пользователь заливает в CS-Cart → подтверждает → следующий файл.

**Статусы:** `⬜` не начато · `🔶` обработан и лежит в `Новые измененные/`, ждёт подтверждения заливки от пользователя · `✅` заливка подтверждена, можно двигаться дальше.

**Текущая точка возврата (2026-09-04):** файлы #1–4 подтверждены (✅). Файл #5 (`portativnye-elektrostancii-ru.html`) обработан и лежит в `Новые измененные/`, ждёт подтверждения. При возобновлении сессии — сначала уточнить у пользователя, залил ли он #5, и либо отметить ✅ и перейти к #6, либо ждать.

**Куда сохранять результат:** копия правится и сохраняется в `Категории/Новые измененные/` — оригинал в `Готовые/`/`Новые/` не трогается, пока пользователь не подтвердит заливку.

Отмечать статус: `⬜ pending` → `✅ done` (в новой папке `Новые измененные`, заливка подтверждена).

---

## Таблица 1 — «фирменный магазин Xiaomi/Сяоми» (любые падежи) → «магазин электроники и гаджетов» (130 файлов)

| # | Файл | Статус |
|---|---|---|
| 1 | Категории/Новые/redmi-note-17-jsonld.html | ✅ |
| 2 | Категории/Новые/redmi-note-17.html | ✅ |
| 3 | Категории/Готовые/portativnye-elektrostancii-ru-dlya-dachi-jsonld.html | ✅ |
| 4 | Категории/Готовые/portativnye-elektrostancii-ru-dlya-dachi.html | ✅ |
| 5 | Категории/Готовые/portativnye-elektrostancii-ru.html | 🔶 обработан, лежит в `Новые измененные/`, ждёт подтверждения заливки |
| 6 | Категории/Готовые/portativnye-elektrostancii-ru-dlya-doma-jsonld.html | ⬜ |
| 7 | Категории/Готовые/portativnye-elektrostancii-ru-dlya-doma.html | ⬜ |
| 8 | Категории/Готовые/portativnye-elektrostancii-ru-moshchnye-jsonld.html | ⬜ |
| 9 | Категории/Готовые/portativnye-elektrostancii-ru-moshchnye.html | ⬜ |
| 10 | Категории/Готовые/portativnye-elektrostancii-ru-dji-jsonld.html | ⬜ |
| 11 | Категории/Готовые/portativnye-elektrostancii-ru-dji.html | ⬜ |
| 12 | Категории/Готовые/portativnye-elektrostancii-ru-bluetti-jsonld.html | ⬜ |
| 13 | Категории/Готовые/portativnye-elektrostancii-ru-bluetti.html | ⬜ |
| 14 | Категории/Готовые/portativnye-elektrostancii-ru-ecoflow-jsonld.html | ⬜ |
| 15 | Категории/Готовые/portativnye-elektrostancii-ru-ecoflow.html | ⬜ |
| 16 | Категории/Готовые/portativnye-elektrostancii-ru-jsonld.html | ⬜ |
| 17 | Категории/Готовые/shops-category-v1-flat.html | ⬜ |
| 18 | Категории/Готовые/shops-category-v2-grouped-jsonld.html | ⬜ |
| 19 | Категории/Готовые/shops-category-v2-grouped.html | ⬜ |
| 20 | Категории/Готовые/shops-category-v1-flat-jsonld.html | ⬜ |
| 21 | Категории/Готовые/adaptery-pitaniya-zaryadnyie-ustroystva.html | ⬜ |
| 22 | Категории/Готовые/adaptery-pitaniya-zaryadnyie-ustroystva-jsonld.html | ⬜ |
| 23 | Категории/Готовые/aksessuaryi-jsonld.html | ⬜ |
| 24 | Категории/Готовые/aksessuaryi.html | ⬜ |
| 25 | Категории/Готовые/portativnye-zaradnye-stancii-jsonld.html | ⬜ |
| 26 | Категории/Готовые/portativnye-zaradnye-stancii.html | ⬜ |
| 27 | Категории/Готовые/konstruktory-aviatsiia-jsonld.html | ⬜ |
| 28 | Категории/Готовые/konstruktory-aviatsiia.html | ⬜ |
| 29 | Категории/Готовые/konstruktory-wange-jsonld.html | ⬜ |
| 30 | Категории/Готовые/konstruktory-wange.html | ⬜ |
| 31 | Категории/Готовые/konstruktory.html | ⬜ |
| 32 | Категории/Готовые/konstruktory-jsonld.html | ⬜ |
| 33 | Категории/Готовые/detskie-tovaryi-jsonld.html | ⬜ |
| 34 | Категории/Готовые/detskie-tovaryi.html | ⬜ |
| 35 | Категории/Готовые/redmi-a7-pro.html | ⬜ |
| 36 | Категории/Готовые/redmi-a7-pro-jsonld.html | ⬜ |
| 37 | Категории/Готовые/komplektuyushhie-dlya-pylesosov-jsonld.html | ⬜ |
| 38 | Категории/Готовые/komplektuyushhie-dlya-pylesosov.html | ⬜ |
| 39 | Категории/Готовые/roboty-mojshhiki-okon-jsonld.html | ⬜ |
| 40 | Категории/Готовые/roboty-mojshhiki-okon.html | ⬜ |
| 41 | Категории/Готовые/avtomobilnye-pylesosy-jsonld.html | ⬜ |
| 42 | Категории/Готовые/avtomobilnye-pylesosy.html | ⬜ |
| 43 | Категории/Готовые/vertikalnye-pylesosy-jsonld.html | ⬜ |
| 44 | Категории/Готовые/vertikalnye-pylesosy.html | ⬜ |
| 45 | Категории/Готовые/roboty-pylesosy-jsonld.html | ⬜ |
| 46 | Категории/Готовые/roboty-pylesosy.html | ⬜ |
| 47 | Категории/Готовые/tovary-dlya-doma-jsonld.html | ⬜ |
| 48 | Категории/Готовые/tovary-dlya-doma.html | ⬜ |
| 49 | Категории/Готовые/aksessuary-dlya-naushnikov-jsonld.html | ⬜ |
| 50 | Категории/Готовые/aksessuary-dlya-naushnikov.html | ⬜ |
| 51 | Категории/Готовые/soundbary-xiaomi-jsonld.html | ⬜ |
| 52 | Категории/Готовые/soundbary-xiaomi.html | ⬜ |
| 53 | Категории/Готовые/aksessuaryi-foto-i-video-jsonld.html | ⬜ |
| 54 | Категории/Готовые/aksessuaryi-foto-i-video.html | ⬜ |
| 55 | Категории/Готовые/proektoryi-jsonld.html | ⬜ |
| 56 | Категории/Готовые/proektoryi.html | ⬜ |
| 57 | Категории/Готовые/tv-pristavki-xiaomi-jsonld.html | ⬜ |
| 58 | Категории/Готовые/tv-pristavki-xiaomi.html | ⬜ |
| 59 | Категории/Готовые/tv-i-igryi-jsonld.html | ⬜ |
| 60 | Категории/Готовые/tv-i-igryi.html | ⬜ |
| 61 | Категории/Готовые/wi-fi-routeryi.html | ⬜ |
| 62 | Категории/Готовые/wi-fi-routeryi-jsonld.html | ⬜ |
| 63 | Категории/Готовые/kovriki-dlya-myshi-xiaomi.html | ⬜ |
| 64 | Категории/Готовые/kovriki-dlya-myshi-xiaomi-jsonld.html | ⬜ |
| 65 | Категории/Готовые/kompyuternye-myshi-xiaomi.html | ⬜ |
| 66 | Категории/Готовые/kompyuternye-myshi-xiaomi-jsonld.html | ⬜ |
| 67 | Категории/Готовые/komplekty-klaviatura-i-mysh.html | ⬜ |
| 68 | Категории/Готовые/komplekty-klaviatura-i-mysh-jsonld.html | ⬜ |
| 69 | Категории/Готовые/klaviatury-xiaomi-jsonld.html | ⬜ |
| 70 | Категории/Готовые/klaviatury-xiaomi.html | ⬜ |
| 71 | Категории/Готовые/kompyuternaya-periferiya-jsonld.html | ⬜ |
| 72 | Категории/Готовые/kompyuternaya-periferiya.html | ⬜ |
| 73 | Категории/Готовые/ultrabuki-jsonld.html | ⬜ |
| 74 | Категории/Готовые/ultrabuki.html | ⬜ |
| 75 | Категории/Готовые/metallicheskie-braslety-jsonld.html | ⬜ |
| 76 | Категории/Готовые/metallicheskie-braslety.html | ⬜ |
| 77 | Категории/Готовые/remeshki-i-braslety-jsonld.html | ⬜ |
| 78 | Категории/Готовые/remeshki-i-braslety.html | ⬜ |
| 79 | Категории/Готовые/xiaomi-buds-6-jsonld.html | ⬜ |
| 80 | Категории/Готовые/xiaomi-buds-6.html | ⬜ |
| 81 | Категории/Готовые/xiaomi-watch-s5-jsonld.html | ⬜ |
| 82 | Категории/Готовые/xiaomi-watch-s5.html | ⬜ |
| 83 | Категории/Готовые/xiaomi-smart-band-10-pro-jsonld.html | ⬜ |
| 84 | Категории/Готовые/xiaomi-smart-band-10-pro.html | ⬜ |
| 85 | Категории/Готовые/xiaomi-17t-series.html | ⬜ |
| 86 | Категории/Готовые/xiaomi-17t-series-jsonld.html | ⬜ |
| 87 | Категории/Готовые/redmi-note-15-pro-plus-5g-jsonld.html | ⬜ |
| 88 | Категории/Готовые/redmi-note-15-pro-plus-5g.html | ⬜ |
| 89 | Категории/Готовые/xiaomi-17t-jsonld.html | ⬜ |
| 90 | Категории/Готовые/xiaomi-17t.html | ⬜ |
| 91 | Категории/Готовые/xiaomi-17t-pro-jsonld.html | ⬜ |
| 92 | Категории/Готовые/xiaomi-17t-pro.html | ⬜ |
| 93 | Категории/Готовые/xiaomi-smartfony-jsonld.html | ⬜ |
| 94 | Категории/Готовые/xiaomi-smartfony-category.html | ⬜ |
| 95 | Категории/Готовые/poco-category-jsonld.html | ⬜ |
| 96 | Категории/Готовые/poco-category.html | ⬜ |
| 97 | Категории/Готовые/redmi-category-jsonld.html | ⬜ |
| 98 | Категории/Готовые/redmi-category.html | ⬜ |
| 99 | Категории/Готовые/smartfony-redmi-note-jsonld.html | ⬜ |
| 100 | Категории/Готовые/smartfony-redmi-note-category.html | ⬜ |
| 101 | Категории/Готовые/xiaomi-17-ultra-jsonld.html | ⬜ |
| 102 | Категории/Готовые/xiaomi-17-jsonld.html | ⬜ |
| 103 | Категории/Готовые/pylesosy-i-uborka-jsonld.html | ⬜ |
| 104 | Категории/Готовые/noutbuki-dlja-ucheby-jsonld.html | ⬜ |
| 105 | Категории/Готовые/naushniki-jsonld.html | ⬜ |
| 106 | Категории/Готовые/smartfonyi-jsonld.html | ⬜ |
| 107 | Категории/Готовые/planshety-jsonld.html | ⬜ |
| 108 | Категории/Готовые/smart-chasy-jsonld.html | ⬜ |
| 109 | Категории/Готовые/noutbuki-jsonld.html | ⬜ |
| 110 | Категории/Готовые/monitory-xiaomi-jsonld.html | ⬜ |
| 111 | Категории/Готовые/fitnes-braslety-xiaomi-jsonld.html | ⬜ |
| 112 | Категории/Готовые/gadjety-jsonld.html | ⬜ |
| 113 | Категории/Готовые/akustika-i-kolonki-jsonld.html | ⬜ |
| 114 | Категории/Готовые/besprovodnye-naushniki-jsonld.html | ⬜ |
| 115 | Категории/Готовые/pylesosy-i-uborka-category.html | ⬜ |
| 116 | Категории/Готовые/noutbuki-dlja-ucheby-category.html | ⬜ |
| 117 | Категории/Готовые/naushniki-category.html | ⬜ |
| 118 | Категории/Готовые/smartfonyi-category.html | ⬜ |
| 119 | Категории/Готовые/planshety-category.html | ⬜ |
| 120 | Категории/Готовые/noutbuki-category.html | ⬜ |
| 121 | Категории/Готовые/monitory-xiaomi-category.html | ⬜ |
| 122 | Категории/Готовые/gadjety-category.html | ⬜ |
| 123 | Категории/Готовые/fitnes-braslety-xiaomi-category.html | ⬜ |
| 124 | Категории/Готовые/besprovodnye-naushniki-category.html | ⬜ |
| 125 | Категории/Готовые/akustika-i-kolonki-category.html | ⬜ |
| 126 | Категории/Готовые/televizory-xiaomi-jsonld.html | ⬜ |
| 127 | Категории/Готовые/televizory-xiaomi-category.html | ⬜ |
| 128 | Категории/Готовые/xiaomi-17-ultra.html | ⬜ |
| 129 | Категории/Готовые/xiaomi-17.html | ⬜ |
| 130 | Категории/Готовые/smart-chasy-category.html | ⬜ |

## Таблица 2 — «6 магазинов» / «шести магазинов» → «6 точек выдачи в Крыму» (20 файлов, пересекаются с Таблицей 1)

| # | Файл | Статус |
|---|---|---|
| 1 | Категории/Готовые/roboty-mojshhiki-okon.html | ⬜ |
| 2 | Категории/Готовые/kompyuternaya-periferiya.html | ⬜ |
| 3 | Категории/Готовые/xiaomi-17t.html | ⬜ |
| 4 | Категории/Готовые/xiaomi-17t-pro.html | ⬜ |
| 5 | Категории/Готовые/xiaomi-smartfony-category.html | ⬜ |
| 6 | Категории/Готовые/poco-category.html | ⬜ |
| 7 | Категории/Готовые/redmi-category.html | ⬜ |
| 8 | Категории/Готовые/smartfony-redmi-note-category.html | ⬜ |
| 9 | Категории/Готовые/pylesosy-i-uborka-category.html | ⬜ |
| 10 | Категории/Готовые/naushniki-category.html | ⬜ |
| 11 | Категории/Готовые/smartfonyi-category.html | ⬜ |
| 12 | Категории/Готовые/planshety-category.html | ⬜ |
| 13 | Категории/Готовые/noutbuki-category.html | ⬜ |
| 14 | Категории/Готовые/monitory-xiaomi-category.html | ⬜ |
| 15 | Категории/Готовые/gadjety-category.html | ⬜ |
| 16 | Категории/Готовые/fitnes-braslety-xiaomi-category.html | ⬜ |
| 17 | Категории/Готовые/besprovodnye-naushniki-category.html | ⬜ |
| 18 | Категории/Готовые/akustika-i-kolonki-category.html | ⬜ |
| 19 | Категории/Готовые/televizory-xiaomi-category.html | ⬜ |
| 20 | Категории/Готовые/smart-chasy-category.html | ⬜ |

## Важное правило проверки (добавлено после пропуска)

При обработке каждого файла — **не полагаться только на изначальный список**. Перед правкой файла заново прогнать по нему `grep -i` с паттерном `фирменн\w*[^.]{0,40}(Xiaomi|Сяоми)` — падежные формы могли встретиться и в парном файле (html/jsonld), даже если он не помечен в этой таблице. Также проверять «N магазин\*» (не только «6») и одиночные упоминания «крупнейш\* ... Xiaomi/Сяоми» отдельно от слова «фирменный».
