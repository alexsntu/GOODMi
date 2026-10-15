# Правки GOODMi на сервере магазинов: что восстановить после обновления CS-Cart / UniTheme2

Здесь лежат точные копии всех файлов, которые мы добавили или изменили на сервере
(`ssh goodmi`, корень магазина `/var/www/maxmobiles/data/www/maxmobiles.ru/`).
Структура папок повторяет серверную: файл из `server-overrides/design/...` кладётся в
`<корень магазина>/design/...`. Копии сняты 2026-10-07, контрольные суммы совпадали с сервером.

Файлы ядра CS-Cart, модулей и темы мы **не меняли** – только добавляли свои файлы в папку
темы `abt__unitheme2` и дописывали «Свой CSS» витрины GOODMi. Все шаблоны ограничены условием
`$runtime.company_id == 2`, поэтому на Maxmobiles и Xione не действуют. Исключения (2026-10-14): штатный
`common/scripts.tpl` темы правлен (пиксель Diginetica, GOODMi и Maxmobiles), а капча по требованию и `ia_defer.js`
действуют на GOODMi и Maxmobiles – см. ниже.

### Временно: пиксель статистики Diginetica отключён (2026-10-14)

`design/themes/abt__unitheme2/templates/common/scripts.tpl` – **штатный файл темы**, уже ранее правленный вручную
(там подключение Diginetica и прочие вставки по `$config.current_host`). Мы добавили в блок
`{if $config.current_host =='goodmi.ru'}` перед подключением `cdn.diginetica.net/10125/client.js` один `<script>`
с комментарием «GOODMi 2026-10-14»: он не даёт скрипту Diginetica отправить пиксель `tracking.diginetica.net/csc-event`
(сервер статистики не отвечает из России, карточка товара «крутилась» до тайм-аута). Поиск Diginetica работает.

То же добавлено и для Maxmobiles – в блок `{if $config.current_host =='maxmobiles.ru'}` перед `cdn.diginetica.net/10127/client.js`
(бэкап перед этим шагом – `scripts.tpl.before-maxmobiles`).

**Вернуть как было:** удалить оба комментария с `<script>` (по 4 строки в каждом блоке) или положить бэкап
`/root/gm-backups/2026-10-14-diginetica-pixel/scripts.tpl.orig` на место, затем очистить кэш CS-Cart и CDN.
Копия текущей версии – `server-overrides/design/themes/abt__unitheme2/templates/common/scripts.tpl`.
После обновления темы файл перезапишется – вставку повторять только если tracking.diginetica.net всё ещё недоступен.

## Файлы

| Файл (от `design/themes/abt__unitheme2/`) | Что делает | Как подключается |
|---|---|---|
| `styles/data/New Orange GOODMI - work.css` | «Свой CSS» GOODMi: все наши стили (меню, выбор города, главная, крошки, оформление заказа – секции 24, 29 и 43, корзина, избранное, сравнение, сроки получения) | Дизайн → Темы → Свой CSS |
| `templates/addons/geo_maps/views/geo_maps/customer_geolocation.tpl` | Выбор города списком вместо карты в диалоге «Местоположение» | Переопределяет шаблон модуля `geo_maps` |
| `templates/addons/call_requests/views/call_requests/components/call_requests_content.tpl` | «Быстрый заказ» в карточке товара: без E-mail, имя и телефон в одну строку | Переопределяет шаблон модуля `call_requests` |
| `templates/addons/reward_points/views/products/components/product_representation.tpl` | Строка «Бонусы за покупку»: «начислим N бонусов» – ссылка на бонусную программу (только GOODMi, остальным – штатная разметка) | Переопределяет шаблон модуля `reward_points` (исходник в теме `responsive`) |
| `templates/addons/abt__unitheme2/hooks/checkout/product_options.post.tpl` | Кнопки «В избранное» и «Сравнить» у товара в корзине | Хук `checkout:product_options` |
| `templates/addons/abt__unitheme2/hooks/ab__extended_comparison_wishlist/top_lists.pre.tpl` | Сравнение всегда открывается на одной категории | Хук `ab__extended_comparison_wishlist:top_lists` |
| `templates/addons/rf_yandex_smartcaptcha/hooks/index/scripts.post.tpl` | Капча Яндекса на GOODMi и Maxmobiles грузится только когда на странице появляется поле капчи (регистрация, восстановление пароля), а не синхронно на каждой странице (2026-10-14); на Xione – как было. **Действует и на Maxmobiles** | Копия шаблона модуля из темы `responsive`; откат – удалить файл |

### Модуль «GOODMi: сроки получения товара» (`gm_delivery_date`)

Собственный модуль, в ядро не входит. Показывает сроки самовывоза, курьера и СДЭК в карточке товара, каталоге и
корзине. Работает только на витрине 2 (условие в коде), но установлен на всю систему: выключение снимает его везде.

| Файл (от корня магазина) | Что делает |
|---|---|
| `app/addons/gm_delivery_date/addon.xml` | описание модуля |
| `app/addons/gm_delivery_date/func.php` | остатки по складам, локация покупателя, разметка блока; с 2026-10-08 – условия покупки в карточке товара (`fn_gm_delivery_date_terms`) и сроки гарантии по категориям (`fn_gm_delivery_date_warranty_map`) |
| `app/addons/gm_delivery_date/lib/engine.php` | правила расчёта дат (без CS-Cart, проверяются `_DEV/gm_delivery_date/tests.php`) |
| `app/addons/gm_delivery_date/controllers/frontend/gm_delivery_date.php` | ответ на запрос страницы `gm_delivery_date.estimate` |
| `js/addons/gm_delivery_date/func.js` | находит товары на странице и вставляет блоки |
| `js/addons/gm_delivery_date/flymenu.js` | с 2026-10-14: мобильное каталог-меню (выезжающее меню темы) – подраздел с моделями раскрывается списком на месте, как у М.Видео, вместо перехода на экран моделей; первая строка «Смотреть все». Перехват нажатия раньше `abt__ut2_fly_menu.js`; стили – секция 67 «Своего CSS». Только GOODMi; откат – убрать подключение из `scripts.post.tpl` |
| `js/addons/gm_delivery_date/ia_defer.js` | с 2026-10-14: отложенный запрос сопутствующих товаров модуля `ab__intelligent_accessories` в карточке (когда блок подходит к экрану или через 4 с после загрузки). Единственный наш скрипт, который подключается и на Maxmobiles (витрины 1 и 2); откат – убрать его подключение из `scripts.post.tpl` |
| `js/addons/gm_delivery_date/jivo.js` | чат Jivo на телефоне: пункт «Помощь» в нижнем закреплённом меню темы на месте «Главной» (с 2026-10-09; стили – секция 41), запасной вариант – прежняя круглая кнопка (секция 31); к срокам не относится, лежит здесь, чтобы подключаться тем же шаблоном. В настройках темы пункт «Главная» из нижнего меню убран пользователем |
| `js/addons/gm_delivery_date/share.js` | открывает меню кнопки «Поделиться» в карточке на шаблоне `goodmi_card` и копирует ссылку; на телефоне – штатное окно «Поделиться» |
| `js/addons/gm_delivery_date/about.js` | прокрутка по кнопке в заголовке «О товаре» (к описанию на ПК, к характеристикам на телефоне); сам заголовок выдаёт шаблон `goodmi_card`, стили – секции 32 и 41 |
| `design/themes/abt__unitheme2/templates/addons/gm_delivery_date/hooks/index/scripts.post.tpl` | подключает скрипты: все – только GOODMi, `ia_defer.js` – GOODMi и Maxmobiles |
| `design/themes/abt__unitheme2/templates/addons/gm_delivery_date/hooks/products/gm_card_delivery.post.tpl` | выводит сроки и условия покупки сразу в странице карточки на шаблоне `goodmi_card` (функция `fn_gm_delivery_date_card_html` в `func.php`) |
| `var/langs/ru/addons/gm_delivery_date.po` | название модуля в админке |
| `js/addons/gm_delivery_date/auth.js` | вход по номеру телефона: подпись поля входа, необязательный e-mail в регистрации и профиле, ссылка «Забыли пароль?» на восстановление по СМС (с 2026-10-13) |
| `js/addons/gm_delivery_date/seomenu.js` | кнопка «Ещё» в строке быстрых ссылок категории (блок 1284, класс `.seomenu`): на ПК видны две строки, остальное раскрывается; на телефоне не работает (там прокрутка). Стили – секция 65 (с 2026-10-13) |
| `app/addons/gm_delivery_date/init.php`, `controllers/frontend/profiles.pre.php` | обработчики входа по номеру и регистрации (`auth_routines`, `update_user_pre`, `update_profile`), проверка формы регистрации, защита подтверждённого номера в профиле |

### Свой шаблон карточки товара `goodmi_card` (с 2026-10-09 – шаблон по умолчанию всей витрины GOODMi)

| Файл (от `design/themes/abt__unitheme2/templates/`) | Что делает |
|---|---|
| `blocks/product_templates/goodmi_card.tpl` | копия `abt__ut2_three_columns_template.tpl`: краткие характеристики последними в левой колонке, строка «О товаре» в разметке, сроки и условия покупки через хук `products:gm_card_delivery` |
| `blocks/product_templates/components/goodmi_card_mobile.tpl` | копия `components/abt__ut2_mobile_template.tpl` с тем же хуком под кнопкой «В корзину» |
| `blocks/product_templates/components/goodmi_credit.tpl` | плашка «от N ₽/мес. в кредит на 12 мес.» справа от цены; платёж считается в шаблоне (75% годовых, 12 мес.), стили – секция 37 |
| `blocks/product_templates/components/goodmi_share.tpl` | кнопка «Поделиться» с меню (ссылка, ВКонтакте, Одноклассники, Telegram, WhatsApp) – подключается из обоих шаблонов в блоке кнопок; скрипт `js/addons/gm_delivery_date/share.js`, стили – секция 36 |

Выбирается в админке как шаблон страницы товара `GOODMi_card` (у товара, категории или витрины). Откат – вернуть
прежний шаблон в настройке; файлы можно не удалять. Правки в копиях помечены «GOODMi:»; после обновления UniTheme2
сверить обе копии с исходными шаблонами темы. Блок с отметкой `data-gm-dd="1"` скрипт `func.js` не запрашивает повторно,
а при смене города заменяет как обычно. Бэкап и выложенные версии – `/root/gm-backups/2026-10-09-card-template/`.

Как модуль устроен, что где менять и как проверять – `_DEV/gm_delivery_date/README.md` (там же правила, проверки и исходники).

Восстановление: вернуть файлы → Модули → установить «GOODMi: сроки получения товара» → очистить кэш. Стили блока –
секция 28 «Своего CSS». Правила словами – `_DEV/gm_delivery_date/RULES.md`.

Исходники стилей с комментариями – `CSS/goodmi-styles.css` (секции 19–42; 35 – вид блока «Доставка и возврат», 36 – кнопка «Поделиться», 37 – плашка кредита, 38 – блок покупки, 39 – выбор вариантов и «О товаре», 40 – вкладки и разделы под карточкой, 41 – «Помощь» в нижнем меню и карточка на телефоне, 42 – блок «Этот товар хорошо дополняют»). Серверный файл стилей
**не равен** `CSS/goodmi-styles.min.css` один в один (например, на сервере нет секции виджета
бонусов), поэтому при восстановлении брать именно копию из этой папки.

## Как восстановить

1. Сравнить контрольные суммы серверных файлов с копиями отсюда – понять, что пропало или затёрто.
2. Вернуть недостающие файлы на те же пути. Владелец `maxmobiles:maxmobiles`, права файлов `666`,
   новых папок `777` (как у соседних файлов темы).
3. Если «Свой CSS» затёрт – вставить содержимое копии через админку или вернуть файл на сервер.
4. Очистить кэш CS-Cart.
5. Проверить три витрины (goodmi.ru, maxmobiles.ru, xione.ru) и страницы: главная, категория,
   карточка товара («Быстрый заказ»), `/cart/`, `/checkout/`, `/wishlist/`, `/yith-compare/`,
   диалог выбора города.

После обновления стили могут перестать попадать в цель, даже если файлы на месте: они
привязаны к классам темы и модулей (`ut2-*`, `litecheckout__*`, `b--pay-ship*`, `ab-ec-*`,
`ty-cart-content*`). Тогда правится секция в `CSS/goodmi-styles.css`, а не шаблон.

## Что настроено в админке (в файлах этого нет)

- Макет главной: товарные блоки 2681–2687, классы секций `gm-showcase`, `gm-showcase--sale`,
  `gm-home-title`; отложенная загрузка секций «Выгодные цены», «Новинки», «Смартфоны» с высотой
  скелетона на ПК 488 / 468 / 360.
- Текстовый блок главной – содержимое `Страницы/HOMEPAGE-SEO-TEXT-BLOCK-GOODMI-2026-10-06.html`.
- Оформление заказа: у блоков доставки и оплаты включено «показывать списком выбора».
- Модуль «Яндекс.Метрика»: выключены вебвизор, карта кликов, внешние ссылки.
- Модуль сравнения: группировка списков по главной категории.

## Бэкапы на сервере

`/root/gm-backups/<дата>-<тема>/` – состояние файлов до каждой правки и проверочные скрипты
(`lesstest.php` – компиляция стилей компилятором магазина, `*-hook-test.php` и `smartytest.php` –
проверка шаблонов на Smarty 5 под `/opt/php83/bin/php`).

### Личный кабинет (`gm_cabinet`, выложен 2026-10-11)

Отдельный контроллер без установки модуля. Только витрина 2 и только вошедший покупатель (гостя отправляет на вход,
на других витринах – 404). Телефон берётся из профиля покупателя в базе, из запроса не принимается.

- `index.php?dispatch=gm_cabinet.bonuses` – «Мои бонусы»: баланс, уровень, прогресс и история из API БонусПлюс (кэш 5 минут).
- `index.php?dispatch=gm_cabinet.orders` – «Мои заказы»: заказы сайта и покупки в магазинах одним списком, с составом,
  картинками и ссылками на товары; отборы «Актуальные / Завершённые», источник, год; кнопка «Оценить товар».
- `php index.php --dispatch=gm_cabinet.sync_orders [--minutes=N]` – загрузка заказов МойСклад в свою таблицу; только из
  командной строки. В расписании пользователя `maxmobiles`: каждую ночь в 03:20.

| Файл (от корня магазина) | Что делает |
|---|---|
| `app/controllers/frontend/gm_cabinet.php` | контроллер страниц и загрузки |
| `app/gm_cabinet/lib.php` | запросы к БонусПлюс и МойСклад, кэш, приведение данных к виду для шаблонов (без зависимости от CS-Cart) |
| `app/gm_cabinet/store.php` | своя таблица заказов МойСклад: запись, чтение, загрузка, состав заказов, поиск товаров сайта |
| `app/gm_cabinet/secrets.php` | ключи доступа; **в репозитории нет** – на сервере собран из ключей, уже записанных в `app/controllers/frontend/bonusplus.php` и `moysklad.php` |
| `design/themes/abt__unitheme2/templates/views/gm_cabinet/bonuses.tpl`, `orders.tpl`, `components/nav.tpl` | шаблоны страниц и левого меню кабинета |
| `design/themes/abt__unitheme2/templates/addons/abt__unitheme2/hooks/profiles/my_account_menu.post.tpl` | пункты «Мои заказы» и «Мои бонусы» в выпадающем меню аккаунта (хук `profiles:my_account_menu`) |
| `design/themes/abt__unitheme2/templates/addons/abt__unitheme2/hooks/wrapper/mainbox_general_title_wrapper.pre.tpl` | левое меню кабинета на штатных страницах «Данные профиля», «Подробности заказа» и «Избранное» (в избранном только на ПК) (хук `wrapper:mainbox_general_title_wrapper`; срабатывает на всех витринах, условие внутри – только GOODMi) |
| секции 44–53 «Своего CSS» | страница бонусов, меню аккаунта, каркас кабинета и страница заказов, вход, регистрация, профиль и подробности заказа, значки левого меню, меню в избранном, нижний лист «Профиль» на телефоне |

**База данных** (создаётся вручную, в обновлениях CS-Cart не участвует): таблицы `cscart_gm_ms_orders` (заказы покупателей из
МойСклад, поиск по номеру телефона; хранятся все каналы продаж, заказы сайта и чужие площадки отсекаются при чтении) и
`cscart_gm_ms_order_items` (состав, дочитывается при первом показе заказа); отметка загрузки – запись
`gm_ms_orders_cursor` в `cscart_storage_data`. Товар сайта по позиции МойСклад находится через карту обмена
`cscart_commerceml_import_entity_map` (внешний код) или по коду товара.

Кэш ответов – файлы в `var/cache/misc/gm_cabinet/` (очищается вместе с кэшем магазина). Бэкапы, проверочные скрипты,
журнал первой загрузки и команды отката – `/root/gm-backups/2026-10-11-cabinet/`.

## 2026-10-12: подробности заказа и сессия

- `app/addons/gm_delivery_date/controllers/frontend/gm_delivery_date.php` – запросы модуля не сохраняют сессию (иначе фоновый запрос сроков стирал отметку о заказе гостя и после оформления открывался вход). Бэкап: `/root/gm-backups/2026-10-12-session-race/`.
- `design/themes/abt__unitheme2/templates/views/profiles/components/profiles_info.tpl` – новый файл, перекрывает штатный из темы responsive: на GOODMi в подробностях заказа две плитки «как получить» и «получатель»; в остальных случаях – штатное содержимое (ветка else, сверять с оригиналом после обновления CS-Cart).
- `hooks/wrapper/mainbox_general_title_wrapper.pre.tpl` – гостю на странице заказа карточка «Заказ оформлен без входа».
- «Свой CSS» – секция 54. Бэкап и откат: `/root/gm-backups/2026-10-12-order-details/`.

### Личный кабинет: вход по номеру телефона (с 2026-10-13)

Вход по подтверждённому номеру или e-mail, регистрация с подтверждением номера кодом из СМС и необязательным e-mail,
восстановление пароля по СМС (`gm_cabinet.recover`, шаблон `templates/views/gm_cabinet/recover.tpl`). Логика – в
`app/gm_cabinet/store.php`, таблицы `cscart_gm_phone_verify` и `cscart_gm_phone_auth`. Описание, SQL и откат –
`_DEV/gm-phone-verify/README.md`.

### Оповещения антивируса maldet в Telegram (с 2026-10-14)

Сервер не достаёт до Telegram, поэтому итог проверки идёт через ту же сводку, что и оповещения о СМС:
`/usr/local/sbin/gm-maldet-status` (root, раз в час по `/etc/cron.d/gm-maldet-status`) кладёт выжимку последней
проверки в `/var/lib/gm-status/maldet.json`; `fn_gm_cab_auth_status()` в `app/gm_cabinet/store.php` добавляет блок
`malware` и тревоги (находки – один раз после проверки, «проверка не шла 30 часов», «нет свежих данных»), облачная
задача пересылает их в Telegram. Оба файла лежат вне папки сайта, обновление CS-Cart их не трогает; копии – в
`server-overrides/usr/local/sbin/` и `server-overrides/etc/cron.d/`. Бэкап и проверка на подставных данных –
`/root/gm-backups/2026-10-14-maldet-alerts/` (`store.php.orig`, `test/run.php`).

## Maxmobiles (company_id = 1): перенос оформления с GOODMi

У Maxmobiles свои файлы, файлы GOODMi не трогаем. Стили – в «Своём CSS» витрины
`design/themes/abt__unitheme2/styles/data/Gray Maxmobiles - work new.css` (точная копия – в этой папке), секции MM-*.
Шаблоны – в штатном модуле «Мои изменения» (`templates/addons/my_changes/`), каждый с условием `company_id == 1`;
модуль `my_changes` должен быть включён (Дополнения → Управление), иначе его шаблоны не подключаются.

### Этап 1: корзина, избранное, сравнение (2026-10-14)

- Стили – секции MM-1 (корзина, вкл. строку «Рекомендуем добавить к заказу» модуля «Интересные аксессуары»),
  MM-2 (избранное), MM-3 (сравнение `/compare/`) в конце файла стилей Maxmobiles.
- `addons/my_changes/hooks/checkout/product_options.post.tpl` – кнопки «В избранное» и «Сравнить» у товара в корзине.
- `addons/my_changes/hooks/ab__extended_comparison_wishlist/top_lists.pre.tpl` – сравнение сразу открывается на первой вкладке.
- Бэкап, проверки и откат – `/root/gm-backups/2026-10-14-mm-stage1/` (`ROLLBACK.txt`).

### Выбор города в окне геолокации (2026-10-14)

- `addons/my_changes/views/geo_maps/customer_geolocation.tpl` – выбор города Maxmobiles (поиск + популярные города, тот же
  список, что у GOODMi). Окно выводится одним файлом темы на все витрины, поэтому в файле GOODMi
  `addons/geo_maps/views/geo_maps/customer_geolocation.tpl` добавлена развилка `elseif company_id == 1` с подключением этого файла.
- Стили – секция MM-4 в конце «Своего CSS» Maxmobiles (акцент `#0071e3`).
- Бэкап, проверки и откат – `/root/gm-backups/2026-10-14-mm-geo/` (`ROLLBACK.txt`).

### Этап 2: оформление заказа (2026-10-14)

- Стили – секция MM-5 (a–d) в конце «Своего CSS» Maxmobiles: перенос секций 24, 29, 43 GOODMi в синих цветах, без
  карточек сроков доставки; штатный блок «Ваш заказ» оформлен компактными строками и стоит первым.
- `js/addons/my_changes/checkout_city.js` (подключает `addons/my_changes/hooks/index/scripts.post.tpl`) – нажатие на город
  открывает окно выбора города.
- Бэкап, проверки и откат – `/root/gm-backups/2026-10-14-mm-checkout/` (`ROLLBACK.txt`).

### Сравнение: группировка «По разделам» (2026-10-14, обе витрины)

- `app/addons/my_changes/func.php`, `init.php`, `GmSectionComparisonGrouper.php` – свой вариант настройки «Группировать по» модуля
  ab__extended_comparison_wishlist: крупные группы (iPhone, MacBook, Смартфоны…) из `fn_my_changes_gm_compare_groups()`,
  остальное – по разделу второго уровня; понимает `?product_id=`. Включается в настройках модуля сравнения отдельно на
  каждой витрине. **Пока выбран этот вариант, модуль «Мои изменения» выключать нельзя** – страница сравнения упадёт
  (модуль сравнения не найдёт класс группировки). Откат – вернуть «Главная категория» в настройке.
- `js/addons/my_changes/compare_notice.js` (Maxmobiles, подключает `addons/my_changes/hooks/index/scripts.post.tpl`) и
  `js/addons/gm_delivery_date/compare_notice.js` (GOODMi, подключает scripts.post.tpl модуля gm_delivery_date) – кнопка
  «Список сравнения» в уведомлении «Товар добавлен» ведёт сразу на список товара, без окна «Списки сравнений» поверх уведомления.
- Бэкап и проверки – `/root/gm-backups/2026-10-14-compare-groups/` (`test/grouper-test.php`).

### Личный кабинет Maxmobiles (`mm_cabinet`, выложен 2026-10-15 по этапам)

Отдельная копия кабинета GOODMi (решение пользователя 2026-10-14), код GOODMi не трогается. Подробный ход, решения и откаты
по этапам – `_DEV/mm-cabinet/PLAN.md`.
- `app/mm_cabinet/lib.php`, `store.php` (+ `secrets.php` – **в репозитории нет**, на сервере, права 600: ключи БонусПлюс, МойСклад,
  Таргет СМС, `phone_verify => true`, `status_key` = `MAXMOBILES_STATUS_KEY` из `cscart-mcp-server/.env`), таблицы `cscart_mm_*`
  (`_DEV/mm-cabinet/tables.sql`).
- `app/controllers/frontend/mm_cabinet.php`, `backend/mm_cabinet.php`; шаблоны `views/mm_cabinet/*` (витрина и админка).
- Модуль «Мои изменения»: `func.php`/`init.php` (обработчики входа по номеру, регистрации, доступа к заказу, отзывов от купивших
  в магазине, блокировки до подтверждения), `controllers/frontend/{auth,profiles,checkout}.pre.php`, `schemas/menu/menu.post.php`,
  админ-хуки `design/backend/templates/addons/my_changes/hooks/{orders,profiles}/*`, хуки темы
  `addons/my_changes/hooks/{profiles/my_account_menu.post,wrapper/mainbox_general_title_wrapper.pre,index/scripts.post}.tpl`,
  `addons/my_changes/views/{profiles/components/profiles_info,blocks/profile_info_benefits}.tpl`, `js/addons/my_changes/auth.js`.
- Развилки `elseif company_id == 1` в общих файлах темы: `views/profiles/components/profiles_info.tpl`,
  `blocks/static_templates/profile_info.tpl` (ветки GOODMi не менялись).
- Стили – секции MM-7…MM-11 в конце «Своего CSS» Maxmobiles (исходники – `_DEV/mm-cabinet/MM-*.css`).
- Cron пользователя maxmobiles: `20 4 * * * … --dispatch=mm_cabinet.sync_orders --minutes=40`.
- Настройки в базе: капча витрины 1 – register + recover, «не показывать после первой проверки» – N; «Отзыв только после
  покупки» Maxmobiles включил пользователь. Массовое подтверждение номеров и объединение дублей – скрипты
  `_DEV/mm-cabinet/server-scripts/`.
- После обновления CS-Cart: если пункты меню кабинета пропали – удалить кэш списка хуков `var/cache/registry/thooks_C/1/*`.

## Настройки nginx (не файлы CS-Cart)

Лежат вне папки сайта, обновление CS-Cart их не трогает; копии – в `server-overrides/etc/nginx/`.

| Файл на сервере | Что делает |
|---|---|
| `/etc/nginx/conf.d/gm-bot-filter.conf` | условия отсева ботов-обходчиков, три правила (описаны в начале файла): бот «Chrome 142 на Mac»; служебные действия без Referer для любого браузера; настольный Chrome старше 140-й версии без Referer на адресах с фильтрами; с 2026-10-13 |
| `/etc/nginx/vhosts-resources/goodmi.ru/gm-bot-filter.conf` | включает отказ 403 по этим условиям для goodmi.ru |
| `/etc/nginx/vhosts-resources/maxmobiles.ru/gm-bot-filter.conf` | то же для maxmobiles.ru (с 2026-10-13) |
| `/etc/nginx/conf.d/gm-bot-names.conf` | условия отсева ботов по названию (Meta, Ahrefs, Semrush, MJ12bot и др.) и поиска уязвимостей (`.env`, `/.git`, `wp-login.php`) – для сайтов не за NGENIX |
| `/etc/nginx/vhosts-resources/xione.ru/gm-bot-filter.conf` | xione.ru: три правила против обходчиков + боты по названию + поиск уязвимостей (с 2026-10-13) |

После правки: `nginx -t && systemctl reload nginx`. Бэкап прежних настроек – `/root/gm-backups/2026-10-13-bot-filter/`.
