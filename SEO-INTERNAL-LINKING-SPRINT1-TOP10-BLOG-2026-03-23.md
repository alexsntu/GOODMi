# Sprint 1: внутренняя перелинковка из топ-10 статей блога

Цель: передать коммерческий вес с самых трафиковых статей на приоритетные URL из `SEO-TOP30-COMMERCIAL-CTR-OPPORTUNITIES-2026-03-23.md`.

## Правила внедрения
- В каждой статье добавить блок в конце: `Что выбрать в каталоге GOODMi`.
- На статью ставить 2 ссылки: обе на стабильные URL (категории/подборки/серии).
- Не использовать ссылки на конкретные карточки товара в evergreen-статьях блога.
- Анкоры естественные, без переспама и без повторения одного и того же шаблона.
- Ссылки открывать в текущем окне, без `nofollow`.

## Карта ссылок (готово к внедрению)

| Статья-источник | Анкор 1 -> URL | Анкор 2 -> URL |
|---|---|---|
| https://goodmi.ru/blog-xiaomi-na-russkom/poleznoe/kak-proverit-podlinnost-xiaomi-za-5-minut-cheklist-pered-pokupkoy-onlayn/ | Смартфоны Redmi Note в каталоге GOODMi -> https://goodmi.ru/smartfony-redmi-note/ | Смартфоны Xiaomi с eSIM -> https://goodmi.ru/smartfonyi/s-esim/ |
| https://goodmi.ru/blog-xiaomi-na-russkom/stati/samye-rasprostranennye-problemy-xiaomi-mi-band-i-sposoby-ih-ustraneniya/ | Смартфоны Xiaomi 2025 года -> https://goodmi.ru/smartfonyi/smartfony-2025-goda/ | Смартфоны с беспроводной зарядкой -> https://goodmi.ru/smartfonyi/s-besprovodnoj-zaradkoj/ |
| https://goodmi.ru/blog-xiaomi-na-russkom/stati/telefon-postoyanno-otklyuchaetsya-ot-wi-fi-5-sposobov-eto-ispravit/ | Смартфоны Xiaomi 2025 года -> https://goodmi.ru/smartfonyi/smartfony-2025-goda/ | Смартфоны Redmi Note в GOODMi -> https://goodmi.ru/smartfony-redmi-note/ |
| https://goodmi.ru/blog-xiaomi-na-russkom/poleznoe/kak-oboyti-zaschitu-frp-factory-reset-protection-na-smartfone-xiaomi/ | Смартфоны с беспроводной зарядкой -> https://goodmi.ru/smartfonyi/s-besprovodnoj-zaradkoj/ | Смартфоны Xiaomi с eSIM в наличии -> https://goodmi.ru/smartfonyi/s-esim/ |
| https://goodmi.ru/blog-xiaomi-na-russkom/stati/shagomer-na-telefone-xiaomi-chto-vybrat-i-kak-nastroit/ | Смартфоны Redmi Note в GOODMi -> https://goodmi.ru/smartfony-redmi-note/ | Смартфоны Xiaomi 2025 года -> https://goodmi.ru/smartfonyi/smartfony-2025-goda/ |
| https://goodmi.ru/blog-xiaomi-na-russkom/stati/gayd-sbros-smartfona-xiaomi-k-zavodskim-nastroykam/ | Смартфоны Xiaomi с eSIM в наличии -> https://goodmi.ru/smartfonyi/s-esim/ | Смартфоны с беспроводной зарядкой -> https://goodmi.ru/smartfonyi/s-besprovodnoj-zaradkoj/ |
| https://goodmi.ru/blog-xiaomi-na-russkom/stati/podrobnoe-sravnenie-redmi-watch-5-redmi-watch-5-lite-i-redmi-watch-5-active/ | Смартфоны Xiaomi 2025 года -> https://goodmi.ru/smartfonyi/smartfony-2025-goda/ | Смартфоны Redmi Note в каталоге -> https://goodmi.ru/smartfony-redmi-note/ |
| https://goodmi.ru/blog-xiaomi-na-russkom/poleznoe/prilozhenie-getapps-xiaomi-chto-eto-za-programma/ | Смартфоны с беспроводной зарядкой -> https://goodmi.ru/smartfonyi/s-besprovodnoj-zaradkoj/ | Смартфоны Xiaomi с eSIM -> https://goodmi.ru/smartfonyi/s-esim/ |
| https://goodmi.ru/blog-xiaomi-na-russkom/poleznoe/podklyuchenie-mi-tv-k-wifi-i-mi-home-poshagovaya-nastroyka-bez-oshibok/ | Смартфоны Xiaomi 2025 года -> https://goodmi.ru/smartfonyi/smartfony-2025-goda/ | Смартфоны Redmi Note в GOODMi -> https://goodmi.ru/smartfony-redmi-note/ |
| https://goodmi.ru/blog-xiaomi/wiki/mozhno-li-gotovit-v-aerogrile-bez-ustanovki-semnoy-reshetki/ | Аэрогрили Xiaomi в каталоге -> https://goodmi.ru/gril/ | Смартфоны Xiaomi 2025 года -> https://goodmi.ru/smartfonyi/smartfony-2025-goda/ |

## Шаблон блока для вставки в статью

```html
<div class="gm-cta">
  <p><strong>Что выбрать в каталоге GOODMi:</strong></p>
  <p><a href="URL_1">Анкор 1</a> и <a href="URL_2">Анкор 2</a>.</p>
</div>
```

## Контроль после внедрения (через 14 дней)
- Проверить в GSC рост кликов по URL-целям из таблицы.
- Отследить в Метрике переходы из блога в категории/подборки.
- Если CTR/переходы низкие: заменить анкоры на более коммерческие.
