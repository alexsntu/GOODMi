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

### Модуль «GOODMi: выгрузка на Авито» (`gm_avito`, с 2026-10-19)

Собственный модуль вместо платного UnitXML в МойСклад: файл автозагрузки Авито (XML, formatVersion 3) собирается
на сайте. Этап 1: объявления GOODMi перенесены из фида UnitXML (556; заголовки, описания и поля – как были), цена и фото
берутся с сайта. Этап 2: в карточке товара вкладка «Авито» (переключатель «Продавать на Авито», заголовок, «описание для
Авито: о товаре», предпросмотр), страницы «Товары → Авито: настройки» (какие товары выгружать – с остатком / без остатка /
все включённые, режим текста, общий блок магазина, поля по умолчанию, ссылка на файл, сборка) и «Товары → Авито: шаблоны
категорий». Новое описание = «о товаре» + характеристики + общий блок. Таблицы `cscart_gm_avito_settings`,
`cscart_gm_avito_items`, `cscart_gm_avito_templates` (`_DEV/gm_avito/tables.sql`) ставятся вручную; перенос из UnitXML,
вывод шаблонов из него, начальные настройки и сверка – `_DEV/gm_avito/tools/`. Ключи API Авито хранятся на сервере вне
папки сайта, в репозиторий не попадают.

| Файл (от корня магазина) | Что делает |
|---|---|
| `app/addons/gm_avito/addon.xml` | описание модуля |
| `app/addons/gm_avito/init.php` | хук `update_product_post` (сохранение вкладки «Авито») |
| `app/addons/gm_avito/func.php` | настройки, шаблоны, цена, фото, характеристики, автоматические заголовок и «о товаре», сборка объявления и файла, хук |
| `app/addons/gm_avito/controllers/frontend/gm_avito.php` | `index.php?dispatch=gm_avito.feed&key=…` – отдаёт файл витрины по секретному ключу, иначе 404 |
| `app/addons/gm_avito/controllers/backend/gm_avito.php` | страницы настроек и шаблонов категорий |
| `app/addons/gm_avito/controllers/backend/products.post.php` | вкладка «Авито» в карточке товара (витрины GOODMi и Maxmobiles) |
| `app/addons/gm_avito/schemas/menu/menu.post.php` | пункт меню «Товары → Авито» с подпунктами «Настройки», «Проблемы и отчёт Авито», «Шаблоны категорий» (те же вкладки – `views/gm_avito/components/nav.tpl`) |
| `app/addons/gm_avito/bin/build.php` | сборка файла: `php app/addons/gm_avito/bin/build.php 2` (номер витрины) |
| `app/addons/gm_avito/bin/sync.php` | отчёт Авито о последней загрузке (API): номер объявления, статус, ссылка, ошибки и предупреждения → вкладка «Авито» и страница проблем; cron в :30 (сборка файла – в :55, Авито забирает в :08) |
| `app/addons/gm_avito/bin/fill.php` | заполнение «о товаре» всем включённым товарам (правленое вручную не трогает) |
| `design/backend/templates/addons/gm_avito/hooks/products/tabs_content.post.tpl` | разметка вкладки «Авито» |
| `design/backend/templates/addons/gm_avito/views/gm_avito/manage.tpl`, `templates.tpl`, `problems.tpl` | страницы модуля; «Авито: проблемы» – итог проверки `fn_gm_avito_check` (включённые не в файле и почему, незаполненные обязательные поля по категориям, длинные заголовки, категории без шаблона) |

Шаблоны категорий (160) собраны по справочнику автозагрузки Авито (`/autoload/v1/user-docs/node/<раздел>/fields`):
постоянные поля, допустимые «Состояние» и «Вид продажи», обязательные поля (`required_fields` – без них товар не
выгружается и попадает на страницу проблем). Марка/модель/память/цвет телефонов, планшетов и телевизоров и бренды
одежды и освещения – из каталогов Авито, записаны в поля товаров. Генераторы и сопоставление – `_DEV/gm_avito/avito-docs/`
и `_DEV/gm_avito/tools/`. Пробный файл для проверки шаблонов на autoload.avito.ru/format/xmlcheck –
`bin/build.php 2 --test` → `feed-test.xml`, ссылка с `&test=1`.

Файл витрины – `var/files/<номер>/gm_avito/feed.xml`. После выкладки шаблонов админки или меню – убрать
`var/cache/templates/backend` и сбросить реестр (`fn_clear_cache('registry')`). Откат: выключить модуль в админке
(Авито продолжит брать файл UnitXML, пока ссылка в кабинете Авито не заменена).

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

> С 2026-10-16 файлы, которые относятся только к Maxmobiles, и стили Maxmobiles (`maxmobiles-styles.css`) продублированы в репозитории
> владельца `https://github.com/alexsntu/Maxmobiles` (папки `server-overrides/`, `_DEV/`). При правке файла Maxmobiles на сервере обновлять
> обе копии. Общие файлы обеих витрин хранятся только здесь.

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

### Подтверждённый номер меняет только администратор (2026-10-15, GOODMi и Maxmobiles)

Номер – ключ покупателя в МойСклад и БонусПлюс, а смена номера на сайте туда не доходит, поэтому покупатель сам
подтверждённый номер не меняет (неподтверждённый и гость – могут). Номер меняет администратор вручную: профиль в админке →
«Подтверждение телефона покупателей» → «Подтвердить номер» → контрагент в МойСклад и БонусПлюс.
- Кабинет: `views/{gm,mm}_cabinet/phone.tpl` – вместо смены по СМС карточка «Как сменить номер» (заявка на store@goodmi.ru /
  store@maxmobiles.ru, звонок на текущий номер или письмо с e-mail профиля); `controllers/frontend/{gm,mm}_cabinet.php` –
  `verify` отказывает в смене подтверждённого номера; плашка профиля в хуках `mainbox_general_title_wrapper.pre.tpl`.
- Оформление заказа: в конце `checkout.pre.php` (`gm_delivery_date` / `my_changes`) номер из формы и корзины подменяется номером
  профиля (иначе `fn_checkout_update_user_profile` записал бы его в профиль после заказа); флаг `Tygh.gm_phone_locked` – хуки
  `index/scripts.post.tpl`; поле только для чтения – `auth.js`; стили – секция 68 GOODMi и MM-12 Maxmobiles.
- Бэкапы и откаты: `/root/gm-backups/2026-10-15-phone-lock{,-mm}/`, `/root/gm-backups/2026-10-15-phone-checkout{,-mm}/`.

### «Все заказы» на странице «Заказ оформлен» ведёт в кабинет (2026-10-15, GOODMi и Maxmobiles)

Кнопки страницы `checkout.complete` (общий шаблон `responsive/templates/views/checkout/complete.tpl`) подменены через хук
`checkout:complete_button`: `addons/gm_delivery_date/hooks/checkout/complete_button.override.tpl` (только витрина 2 →
`gm_cabinet.orders`, `/cabinet/`) и `addons/my_changes/hooks/checkout/complete_button.override.tpl` (только витрина 1 →
`mm_cabinet.orders`). На другой витрине подмена пустая, и CS-Cart выводит штатные кнопки. После выкладки нужно удалить кэш
списка хуков `var/cache/registry/thooks_C/{1,2}`. Откат: `/root/gm-backups/2026-10-15-complete-button/ROLLBACK.txt`.

### Служебные команды `bonusplus.*` закрыты для посетителей (2026-10-15, все витрины)

`app/addons/my_changes/controllers/frontend/bonusplus.pre.php`: команды контроллера разработчика
`app/controllers/frontend/bonusplus.php` – `aj1`, `ajdel`, `aj` (подписки на вебхуки БонусПлюс), `orderinfo` (данные заказа),
`bonus`, `bonusfile` (перезапись баллов CS-Cart), `clearphone` – работают только из cron (CLI) и для вошедшего администратора,
остальным 404. Открыта `ajax` (проверка баланса, `js/tygh/bonus.js`); вебхуки `bonusplus.php`/`bonuspluscard.php` в корне не
затронуты. Сам контроллер не менялся (в нём ключ API БонусПлюс открытым текстом). Откат: `rm` этого файла
(`/root/gm-backups/2026-10-15-bonusplus-close/ROLLBACK.txt`).

## Настройки nginx (не файлы CS-Cart)

Лежат вне папки сайта, обновление CS-Cart их не трогает; копии – в `server-overrides/etc/nginx/`.

| Файл на сервере | Что делает |
|---|---|
| `/etc/nginx/conf.d/gm-bot-filter.conf` | условия отсева ботов-обходчиков, три правила (описаны в начале файла): бот «Chrome 142 на Mac»; служебные действия без Referer для любого браузера; настольный Chrome старше 140-й версии без Referer на адресах с фильтрами; с 2026-10-13 |
| `/etc/nginx/vhosts-resources/goodmi.ru/gm-bot-filter.conf` | включает отказ 403 по этим условиям для goodmi.ru |
| `/etc/nginx/vhosts-resources/maxmobiles.ru/gm-bot-filter.conf` | то же для maxmobiles.ru (с 2026-10-13) |
| `/etc/nginx/conf.d/gm-bot-names.conf` | условия отсева ботов по названию (Meta, Ahrefs, Semrush, MJ12bot и др.) и поиска уязвимостей (`.env`, `/.git`, `wp-login.php`) – для сайтов не за NGENIX |
| `/etc/nginx/vhosts-resources/xione.ru/gm-bot-filter.conf` | xione.ru: три правила против обходчиков + боты по названию + поиск уязвимостей (с 2026-10-13) |
| `/etc/nginx/vhosts-resources/{goodmi.ru,maxmobiles.ru}/ngenix-realip.conf` (копия – `ngenix-realip.conf`) | настоящий адрес посетителя за NGENIX: `X-Real-IP` (NGENIX перезаписывает подделку клиента) только от 63 диапазонов NGENIX (API `api/v3/ip-prefix`); журнал, `limit_req` (у maxmobiles.ru 20 запросов/с) и REMOTE_ADDR – по посетителю, а не по узлу CDN. Адресами NGENIX в журнале остаются только их проверки доступности (NCDN Health Checking). Если NGENIX добавит диапазоны – обновить список. С 2026-10-18, откат – `/root/gm-backups/2026-10-18-ngenix-realip/ROLLBACK.txt` |
| `/etc/nginx/vhosts-resources/{goodmi.ru,maxmobiles.ru}/dynamic/gm-client-ip.conf` (копия – `gm-client-ip.conf`) | PHP получает пустые `X-Forwarded-For`, `Client-IP`, `Forwarded` и т.п.: CS-Cart `fn_get_ip` брал ПЕРВЫЙ адрес из них, а его подделывает сам посетитель – лимиты СМС кабинетов по адресу обходились. Теперь `fn_get_ip` = REMOTE_ADDR (проверенный, см. строку выше). Подключается в `location @php`; папку `dynamic` ведёт ISPmanager (там его `reqlimit.conf`) – после смены настроек сайта в панели проверить, что файл на месте. С 2026-10-18 |

После правки: `nginx -t && systemctl reload nginx`. Бэкап прежних настроек – `/root/gm-backups/2026-10-13-bot-filter/`.

### Свой поиск Maxmobiles вместо Diginetica (с 2026-10-16 у всех покупателей Maxmobiles; Diginetica на Maxmobiles удалена)

С включения для всех кука `mm_search_test` ничего не включает (`?test=` только стирает её), скрипт `mm_search.js` – в общем
бандле через хук my_changes, стили – секция MM-13 «Своего CSS» Maxmobiles (= `app/mm_search/suggest.css`), статистика – таблицы
`cscart_mm_search_*` и админка `mm_search.*` (Товары → «Поиск Maxmobiles»). Откат включения – `/root/gm-backups/2026-10-16-mm-search-golive/`.
Ниже – как было на этапах до включения.

Движок – Manticore Search 29.9 на этом же сервере (служба `manticore`, слушает только 127.0.0.1:9306/9308/9312; репозиторий
`/etc/apt/sources.list.d/manticoresearch.sources`). Индексы: `mm_products` (товары витрины 1), `mm_suggest` (словарь подсказок),
`mm_categories`, `mm_queries`. Сборка – скрипты `/root/mm-search/` (исходники и сравнение с Diginetica – `_DEV/search/`):
`mm-search-index.php` (каждые 2 часа, :15 нечётных часов), `mm-search-demand.php` (спрос за 90 дней из МойСклад, с 2026-10-17 раз в неделю – понедельник 05:10) – `/etc/cron.d/mm-search`
(копия – `_DEV/search/cron.d-mm-search`).
- Файлы сайта: `mm-search.php` (корень: подсказки `?q=`, категории `?cats=1&q=`, включение `?test=on|off`, скрипт и стили на время
  настройки `?asset=js|css`; не maxmobiles.ru → 404), `app/mm_search/lib.php` (поиск, синонимы, подсказки), `app/mm_search/suggest.css`,
  `js/addons/my_changes/mm_search.js` (подсказки, история «Вы искали», плитки «Найдено в категориях»).
- Модуль «Мои изменения»: `controllers/frontend/products.pre.php` (страница результатов products.search – наши товары, `mm_cat` – одна
  категория), хуки `get_products_pre`, `get_products`, `get_products_post`, `products_sorting` в `func.php` (сортировка «По релевантности»).
- Шаблоны: `common/scripts.tpl` – Diginetica Maxmobiles не грузится при куке; хук my_changes `index/scripts.post.tpl` – при куке
  подключает `/mm-search.php?asset=js`. Секция MM-13 «Своего CSS» Maxmobiles – старая версия стилей, при включении для всех заменить
  содержимым `app/mm_search/suggest.css`.
- Откаты: `/root/gm-backups/2026-10-16-manticore/`, `…-mm-search/`, `…-mm-search-results/` (ROLLBACK.txt в каждой).

### Свой поиск GOODMi (с 2026-10-16 у всех покупателей GOODMi; Diginetica на GOODMi удалена)

Включение для всех: скрипт `gm_search.js` – в общем бандле через хук gm_delivery_date `index/scripts.post.tpl`, стили – секция 69 «Своего CSS»
GOODMi (= `app/gm_search/suggest.css`), Diginetica 10125 (и защита от её пикселя) убрана из `common/scripts.tpl`, условие отметки снято в
`products.pre.php`, `?digiSearch=…&term=` → 301 на нашу выдачу (before_dispatch gm_delivery_date), `?test=` только стирает куку.
Откат включения – `/root/gm-backups/2026-10-16-gm-search-golive/ROLLBACK.txt`. Ниже – как было на этапах до включения.

Отдельная копия поиска Maxmobiles: свои индексы Manticore `gm_products`, `gm_suggest`, `gm_categories`, `gm_queries`, `gm_status`,
кука `gm_sid`, история `gm_search_history`, классы `gm-*` – с Maxmobiles не смешивается. Адрес категории – только своё имя (без родителей).
Сборка – `/root/gm-search/` (исходники – `_DEV/search-gm/`): `gm-search-index.php` (:15 чётных часов), `gm-search-queries.php` (частые запросы:
автодополнение Diginetica по буквам + журналы nginx, каждый день 05:40), `gm-search-demand.php` (спрос за 90 дней из МойСклад GOODMi,
с 2026-10-17 раз в неделю – понедельник 05:40, ~12 мин) – `/etc/cron.d/gm-search`
(копия – `_DEV/search-gm/cron.d-gm-search`).

### Защита поиска обеих витрин от зависания Manticore (2026-10-17)

2026-10-16 в 22:20 Manticore завис на `CREATE TABLE` (журнал binlog ссылался на несозданный файл), и 14 часов все сборки копились
в очереди. Перезапуск не шёл, пока журнал не убрали в `/root/gm-backups/2026-10-17-search-hang/manticore-binlog/`. Что сделано:
- Расписание: GOODMi – :15 чётных часов, Maxmobiles – :15 нечётных; все сборки под общим замком `/run/lock/search-index.lock`
  (`flock`) и с пределом времени (`timeout`); любой сбой – строка «не завершён, код …» в `cron.log`.
- Сборщики (`*-search-index.php`, `*-search-demand.php`): ждут ответа Manticore не дольше 2 мин (`MYSQLI_OPT_READ_TIMEOUT`).
- Сайт (`gm-search.php`, `mm-search.php`, оба `products.pre.php`): 1 с на соединение, 3 с на ответ; страница результатов при сбое
  показывает штатную выдачу CS-Cart, а не висит (обработчиков PHP всего 5, `fastcgi_read_timeout` – 10 суток).
- Сводка `gm_cabinet.auth_status` (`app/gm_cabinet/store.php`, облачная задача → Telegram): поле `search` со временем сборок обеих
  витрин и тревоги «Manticore не отвечает» / «индекс не обновлялся больше 5 часов».
- Порядок выдачи: модуль `ecl_instock_sorting` («сначала в наличии») переписывал ORDER BY страницы результатов – хуки `get_products`
  (my_changes и gm_delivery_date) убирают его метку `instock_marker` при сортировке «По релевантности».
- Откат – `/root/gm-backups/2026-10-17-search-hang/ROLLBACK.txt`.
- Проверка (2026-10-17, всё прошло): `_DEV/search-tests/test-safe.sh` – без влияния на сайт (зависшая сборка снимается по времени, занятый
  замок не копит очередь, сборщик на «молчащем» порту падает с ошибкой); `_DEV/search-tests/run-hole-test.sh` – с Mac, через веб-сервер
  GOODMi на минуту (тестовая страница + посредник `hole-proxy.py`): зависший запрос отпускается за 3 с, главная открывается как обычно.
  Не проверено на живом сбое: переход страницы результатов на штатную выдачу именно при зависании и доставка тревоги в Telegram –
  для этого «заморозить» Manticore (`kill -STOP` / `kill -CONT` процесса searchd) на 30 с ночью.
- Файлы сайта: `gm-search.php` (корень: подсказки `?q=`, `?cats=1`, `?chips=1`, отметка `?test=on|off`, `?asset=js|css`; не goodmi.ru → 404;
  `?ev=1` до этапа 4 ничего не пишет), `app/gm_search/lib.php`, `app/gm_search/suggest.css` (цвета GOODMi), `js/addons/gm_delivery_date/gm_search.js`
  (флаги STATS и RESULTS – выключены до этапов 3–4).
- Шаблоны: `common/scripts.tpl` – Diginetica GOODMi (10125) не грузится при отметке `gm_search_test`; хук gm_delivery_date `index/scripts.post.tpl` –
  при отметке подключает `/gm-search.php?asset=js` (скрипт сам подключает стили).
- Этап 3 (страница результатов, при отметке): `app/addons/gm_delivery_date/controllers/frontend/products.pre.php` (поиск по Manticore →
  Registry `runtime.gm_search`, плитка `gm_cat`, сортировка «По релевантности»), хуки `get_products_pre`, `get_products`, `get_products_post`,
  `products_sorting` в `func.php`/`init.php` модуля gm_delivery_date (ключи `gm_search` – с хуками Maxmobiles `mm_search` не пересекаются).
- Откаты: `/root/gm-backups/2026-10-16-gm-search-s2/ROLLBACK.txt`, `…-gm-search-s3/ROLLBACK.txt`.
- Этап 4 и правки: статистика `cscart_gm_search_*` и админка «Товары → Поиск GOODMi» (`app/controllers/backend/gm_search.php`,
  `app/gm_search/stats.php`, `design/backend/templates/views/gm_search/*`, пункт в `gm_delivery_date/schemas/menu/menu.post.php`), синонимы
  в админке (196), правила порядка выдачи «раздел запроса» и «тип товара» (`gm_search_intent`; на Maxmobiles – `mm_search_intent`).
  Откаты: `…-gm-search-s4/`, `…-gm-search-admin/`, `…-gm-search-intent/`, `…-mm-intent/`, `…-mobile-gap/`.

### Статистика поиска без ботов, «Часто ищут» по своему журналу (2026-10-18, обе витрины)

17.10 в 00:11 бот без cookie за 35 с сделал на GOODMi 49 поисков («xjp», «jinping»…): каждый записался новым посетителем и через
журналы nginx попал в «Часто ищут». Что сделано:
- Оба `products.pre.php`: поиск без cookie сессии сайта (`sid_customer_…`, её ставит первая же страница) выполняется, но в журнал
  `cscart_{gm,mm}_search_log` не пишется.
- Сборщики индекса (`_DEV/search*/{gm,mm}-search-index.php`): «Часто ищут» и запросы в подсказках = основа (`queries.txt`) + свой журнал
  за 90 дней, по 3 за посетителя, только где что-то нашлось; запрос только из журнала – если его искали минимум двое.
- `gm-search-queries.php` больше не читает журналы nginx (за NGENIX там адреса его узлов) – в `queries.txt` только Diginetica.
- Из журналов удалены строки бота и наших проверок 17.10 (GOODMi 56, Maxmobiles 7). Копии таблиц и прежних файлов –
  `/root/gm-backups/2026-10-18-search-stats/` (`old/`, `search-stats-tables.sql.gz`).
- NGENIX: в набор № 6177742 добавлено правило № 2322118 – Referer ровно `https://www.google.com/` на поиск, «в сравнение», `/wishlist/`,
  `/yith-compare/` → 403 (так ходили этот бот и бот корзин 10–13.10). До/после – `_DEV/ngenix/ruleset-6177742-*-2026-10-18.json`.

### СМС всех витрин: страница «Покупатели → СМС: работа и баланс» (2026-10-18)

Общая для всех витрин страница админки (`heaven.php?dispatch=sms_stats.manage&days=0|7|30|90`): баланс Таргет СМС (один кабинет на
GOODMi и Maxmobiles), расход в день и прогноз, путь покупателя по кодам каждой витрины (запросил → отправлено → ошибка/лимит →
доставлено → ввёл), все СМС кабинета оператора по видам (коды, статусы заказов из МойСклад, «Шлюз GET») с расходом, отправители у
покупателя, операторы связи, ошибки, по дням.
- Файлы сайта: `app/controllers/backend/sms_stats.php`, `app/sms_stats/stats.php`, `design/backend/templates/views/sms_stats/manage.tpl`,
  пункт меню – в `app/addons/gm_delivery_date/schemas/menu/menu.post.php` (меню дополняется только из модулей; пункт виден на всех витринах).
- Сбор раз в час: `/etc/cron.d/sms-stats` (:05) → `/root/sms-stats/sms-stats-collect.php` (исходники и таблицы – `_DEV/sms-stats/`):
  JSON API Таргет СМС `type=balance` и `type=list_stats` за 3 дня (статусы приходят с опозданием) → `cscart_sms_stat` (часть СМС –
  строка, без текста и номера: отпечаток + 2 последние цифры), `cscart_sms_balance` (почасовые отметки, ошибка сбора). Журнал –
  `/root/sms-stats/cron.log`. Оператор хранит статистику 3 месяца; первая загрузка – `--days=90`.
- Путь покупателя – из `cscart_gm_auth_log` / `cscart_mm_auth_log` на момент открытия страницы.
- Состояние (отдельного запроса «сервис исправен» у Таргет СМС нет): `sms_stats_health()` – плашка вверху страницы (оператор отвечает
  и принял вход, сбор идёт, баланс ≥ 300 ₽ и хватит ≥ 7 дн., доставка за сутки ≥ 90 %, нет СМС без статуса > 30 мин, нет отказов
  оператора, коды вводят). Те же проверки и `sms_stats_alerts($hours)` – в сводке Telegram `gm_cabinet.auth_status` (поле `sms` и
  тревоги): постоянная беда – в каждой сводке; недоставленные – один раз (по `collected_at` – когда сборщик увидел статус), баланс –
  при переходе ниже 300 ₽, «хватит меньше чем на неделю» – раз в день в сводке с 10:00; отказы и «коды не вводят» Maxmobiles
  (GOODMi сводка считала и раньше).
- Откат – `/root/gm-backups/2026-10-18-sms-stats/ROLLBACK.txt`.

### Инцидент 2026-10-18: закладки взломщика (веб-шеллы) в папке сайтов

Найдены при разборе перехода на PHP 8.3 и убраны в карантин `/root/incident-2026-10-18/quarantine/` (с путями; там же
`files-stat.txt`, `files-sha256.txt`, `webshells-evidence.tgz`): `images/ab__webp/thumbnails/3603/3602/api.php`,
`images/thumbnails/856/728/modules.php`, `images/thumbnails/1273/1083/scripts.php`, `images/thumbnails/1943/1457/detailed/theme-settings.php`,
`app/lib/vendor/tijsverkoyen/functions.php` (все созданы 2025-09-30 18:55:32–33), `upgrades/source_restora.php`, `upgrades/source_restors.php`
(дата подделана под 2025-02-11; приехали с прежнего сервера при переносе 2025-09-04), и те же два загрузчика на копии dev.maxmobiles.ru.
10.10.2026 загрузчики в `upgrades/` открывали на goodmi.ru (ответ 200). maldet их не видел: проверяет только файлы за сутки,
подписи от 25.05.2026, ClamAV не установлен.
- `gm-no-php-in-data.conf` (копия – `server-overrides/etc/nginx/`) в `/etc/nginx/vhosts-resources/{goodmi.ru,maxmobiles.ru,xione.ru,
  dev.maxmobiles.ru,dev.goodmi.ru,dev.mi92.ru}/`: запрет запуска PHP в `images|img|upgrades|var|wp-content|app/lib|design|js|css`
  и закрытие снаружи папок с данными покупателей и журналами обмена `bonusplus/`, `logsklad/`, `mc_log/`, `sber/` (их пишут
  скрипты в корне сайта, через сайт их никто не читал). Уровень server (`if … return 403`) – срабатывает до выбора location.
  Копия nginx до правки – `/root/incident-2026-10-18/nginx-before-block.tgz`.
- Проверено: закладок с теми же признаками и других PHP-файлов в папках данных на сервере нет, подозрительных процессов,
  соединений, cron и ключей SSH нет, внедрённых скриптов в базе нет; администраторы – на проверке у пользователя.
- 2026-10-18, позже: maldet и ClamAV (поставлен 1.4.3 из архива Debian, базы скачаны с Mac – с сервера database.clamav.net
  отвечает 403 для России; служба обновления clamav-freshclam выключена) не узнали НИ ОДНУ из 9 закладок. Поэтому –
  свои правила `/var/lib/clamav/gm-webshells.yar` (YARA: Godzilla, Behinder, eval(hex2bin(POST)), загрузчик, base32+eval,
  код/функция из запроса) – 9 из 9, ложных срабатываний 0 на 33 тыс. файлах – и сторож `/usr/local/sbin/gm-webshell-watch`
  (`/etc/cron.d/gm-webshell-watch`, :25, ~45 с): правила + сравнение PHP-файлов с прошлым запуском → `/var/lib/gm-status/
  webshell.json` и журнал `code-changes.jsonl` → сводка `gm_cabinet.auth_status` (поле `code_watch`; закладка – тревога в каждой
  сводке, новые/изменённые PHP-файлы – один раз за окно). Исходники – `_DEV/security/`. Полная проверка официальными базами
  ClamAV (33 182 файла) – чисто.
- 2026-10-18, закрытие и права (пользователь: «1 – да, 2 – закрыть пока, 3 – если не используется, 4 – с мониторингом»):
  - `/usr/local/sbin/gm-firewall` (копия `_DEV/security/`): цепочка `GM_CLOSED` в iptables и ip6tables – снаружи закрыты 3306
    (MariaDB; внешних пользователей нет, а `bind-address = *` в `50-server.cnf` вписала панель ISPmanager), 21 (FTP; за 6 недель
    ни одного входа, только перебор паролей) и 6081 (Varnish – ни один сайт через него не идёт, nginx ходит прямо в Apache :8080).
    Сам сервер пускается. `/etc/cron.d/gm-firewall`: при загрузке и раз в 10 минут возвращает правила, если их снесли панель или
    fail2ban. Открыть порт – убрать его из `PORTS` и `iptables -D INPUT -p tcp --dport N -j GM_CLOSED` (и ip6tables).
  - Снята «запись для всех» (o+w) с 3,36 млн файлов и папок сайта (все принадлежали `maxmobiles` – от него и работает сайт);
    список для отката – `/root/incident-2026-10-18/o+w-before.list0.gz`, команда – `ROLLBACK-permissions.txt`. В `config.local.php`
    `DEFAULT_FILE_PERMISSIONS` 0666 → 0644 и `DEFAULT_DIR_PERMISSIONS` 0777 → 0755 (иначе CS-Cart создавал новые файлы кэша и
    картинок с записью для всех), сам файл (пароль БД) – 600; прежний – `/root/incident-2026-10-18/config.local.php.before`.
  - Мониторинг в сторож `gm-webshell-watch`: дочитывает журналы ошибок и запросов goodmi, maxmobiles, xione и dev-копий
    (`log-offsets.json`) → `events.jsonl` → сводка Telegram: ошибки доступа к файлам («Permission denied», «not writable»…; до правки
    их не было ни одной), запросы к закрытым папкам данных и запуск существующего PHP в папке данных (несуществующие – сканеры, молчим).
- 2026-10-18, вечер: облачная задача оповещений (`goodmi-sms-watch-tick`) с 17.10 12:24 не забирает сводку – запасной путь на Mac,
  системная служба `ru.goodmi.alerts` (исходники и установка – `_DEV/security/mac-alerts/`).

### Тест PHP 8.3 на свежей копии боевых витрин (с 2026-10-18, идёт несколько дней)

Боевые – НЕ переключались (7.4). Резервная копия боевых перед тестом – `/root/backup-2026-10-18-pre-php83/` (база, база панели,
код без картинок, настройки nginx/PHP/cron/панели; `RESTORE.txt`). Скрипты подготовки – `_DEV/php83-test/`.
- Копия: `/var/www/www-root/data/www/dev.maxmobiles.ru` (пользователь www-root), база `mm_dev`, адреса dev.maxmobiles.ru /
  dev.goodmi.ru / dev.mi92.ru (Xione) – в ДВУХ таблицах: `cscart_companies.storefront` и `cscart_storefronts.url`. Картинки –
  `mount --bind` боевой `images` только для чтения (не в fstab: после перезагрузки сервера смонтировать заново).
- Ограждения: `gm-firewall` – пользователю www-root запрещены любые соединения наружу (цепочка `GM_DEV_OUT`); в
  `/etc/nginx/vhosts-resources/dev.*/` (копии – `server-overrides/etc/nginx/dev-copies/`): `gm-dev-auth.conf` (адрес пользователя
  без пароля, остальным – логин/пароль `/etc/nginx/gm-dev.htpasswd`), `gm-dev-noindex.conf` (X-Robots-Tag noindex + robots.txt
  «Disallow: /»), `gm-cscart-fallback.conf`.
- **Находка:** ISPmanager при смене версии PHP удаляет из vhost ручной `location @fallback` (ЧПУ CS-Cart), а `try_files … @fallback`
  остаётся – весь каталог, корзина и кабинет отдают 500. Поэтому `@fallback` – в `gm-cscart-fallback.conf` (папку vhosts-resources
  панель не трогает). Для боевых: до смены версии одним заходом убрать `@fallback` из vhost и положить этот файл (иначе дубль location).
  Пока этого не сделано – не править боевые сайты в панели. Сайты уже работали на 8.3 с 20.11.2025 по 24.04.2026, причина отката неизвестна.
- Расширения 8.3 bcmath, dba, timezonedb, xsl включены через панель (на 8.3 работают только dev).
- **Правки ядра CS-Cart под PHP 8 (2026-10-20, dev и боевые; на 7.4 поведение то же; ОБНОВЛЕНИЕ ЯДРА ИХ ПЕРЕЗАПИШЕТ – вернуть из этой папки):**
  - `app/addons/reward_points/func.php` (стр. ~792): коэффициент баллов `price / (price + discount)` при сумме 0 → 0. Без этого на 8.3
    карточки товаров с ценой 0 (услуги ремонта Maxmobiles – 241 шт., GOODMi 19, Xione 1) отдают 500 (DivisionByZeroError);
    на 7.4 было предупреждение «Division by zero» в журнале.
  - `app/controllers/frontend/product_features.php` (`delete_product`, стр. ~101): `array_search` только при непустом списке сравнения
    (боты без сессии) – на 8.3 TypeError → 500.
  - Оригиналы – `/root/backup-2026-10-18-pre-php83/php8-fixes-2026-10-20/{live,dev}/`. Проверка: 253 карточки с ценой 0 Maxmobiles
    на dev 8.3 – все 200.

### Журнал времени ответа PHP боевых сайтов (с 2026-10-18)

Пулы PHP 7.4 боевых упираются в предел 5 процессов (с 13–14.10 в десятки раз чаще), а время ответа nginx не записывал.
`/etc/nginx/conf.d/gm-timing-format.conf` (копия – `server-overrides/etc/nginx/conf.d/`) + `vhosts-resources/{goodmi.ru,maxmobiles.ru,
xione.ru}/gm-timing-log.conf` (`access_log /var/log/nginx/gm-timing-<сайт>.log gm_timing if=$gm_to_php;`) – отдельный журнал
только запросов, дошедших до PHP, рядом с прежними (они не изменены). Ротация – стандартная `/etc/logrotate.d/nginx` (14 дней).
Убрать – удалить три `gm-timing-log.conf` и `gm-timing-format.conf`, `nginx -t && systemctl reload nginx`.
Нагрузочные испытания на dev – `_DEV/php83-test/gm-bench.sh`, итоги – `bench-results-2026-10-18.txt`.

### Гео-страницы (`ab__geo_pages`) выключены, переадресация городов GOODMi (2026-10-18)

Пользователь: модуль не нужен. На PHP 8.3 он даёт «Deprecated» на каждой странице (`ab__geo_pages/func.php:279`). Модуль
ВЫКЛЮЧЕН (не удалён – данные целы): 42 города (40 у GOODMi), 20 шаблонов, 63 232 привязки; копия таблиц –
`/root/backup-2026-10-18-pre-php83/geo-pages/`. За 7 дней до выключения: 5 054 запроса к гео-адресам, 139 переходов из поиска.
`vhosts-resources/goodmi.ru/gm-geo-redirect.conf` (копия здесь): 301 с `/<город>/<адрес>` на `/<адрес>`, `/<город>/` – на главную,
параметры сохраняются; совпадений названий городов с ЧПУ сайта нет. Вернуть: включить модуль в админке (или
`fn_update_addon_status('ab__geo_pages','A')`) и удалить файл переадресации, `nginx -t && systemctl reload nginx`.
Там же: `cp_json_ld` – для Xione задано `review_qty = 2` (как у GOODMi и Maxmobiles): без него на PHP 8.3 карточки Xione с отзывами – 500.

### Подготовка боевых к PHP 8.3, сделанное 2026-10-18 вечером (на 7.4 ничего не меняет)

- `@fallback` (ЧПУ CS-Cart) вынесен из `/etc/nginx/vhosts/maxmobiles/{goodmi.ru,maxmobiles.ru,xione.ru}.conf` в
  `vhosts-resources/<сайт>/gm-cscart-fallback.conf` (копия – `server-overrides/etc/nginx/gm-cscart-fallback.conf`): ISPmanager при
  смене версии PHP стирает этот блок из vhost, и весь каталог отдаёт 500. Если панель когда-нибудь вернёт блок в vhost – nginx
  не стартует из-за дубля: убрать блок из vhost. Копия до правки – `/root/backup-2026-10-18-pre-php83/before-fallback/nginx.tar`.
- `gm-search.php` и `mm-search.php`: первой строкой `mysqli_report(MYSQLI_REPORT_OFF)` – на PHP 8.1+ mysqli бросает исключения,
  и недоступный Manticore давал бы фатальную ошибку вместо мягкого 503.
- `/opt/php83/etc/php-fpm.d/user.d/maxmobiles.conf` = как у 7.4 (загрузка 1024M, error_reporting без DEPRECATED); панель может
  перезаписать при смене версии – сверить при переходе.
- Найдено, не исправлено (ждёт решения): «Deprecated: html_entity_decode(null)» – шаблоны метатегов темы (abt__unitheme2 `meta.tpl`:8,
  `addons/abt__unitheme2/hooks/index/meta.post.tpl`:4, responsive `addons/seo/hooks/index/meta_description.override.tpl`:2) на страницах
  без своего описания; ядро ставит Smarty `error_reporting = E_ALL & ~E_NOTICE` (`fn.init.php`:78), поэтому DEPRECATED из шаблонов
  попадает в журнал, хотя `config.local.php` его гасит.

### Шаблоны метатегов: пустое описание без предупреждений PHP 8 (2026-10-18, все витрины)

На страницах без своего описания (главная, корзина, кабинет, избранное, поиск, отзывы) в `html_entity_decode` попадал null –
на PHP 8.1+ «Deprecated» в журнал на каждой такой странице (ядро ставит Smarty `error_reporting = E_ALL & ~E_NOTICE`, `fn.init.php`:78).
Перед `html_entity_decode` добавлено `|default:""` в трёх файлах (копии здесь, в `server-overrides/design/themes/`):
`abt__unitheme2/templates/meta.tpl`:8, `abt__unitheme2/templates/addons/abt__unitheme2/hooks/index/meta.post.tpl`:4,
`responsive/templates/addons/seo/hooks/index/meta_description.override.tpl`:2. Метатеги не изменились (сверено побайтно на 18 страницах
боевых и 20 dev). Обновление UniTheme2 / CS-Cart перезапишет файлы – вернуть отсюда и удалить скомпилированные
`var/cache/templates/abt__unitheme2/*meta*.tpl.php`. Оригиналы – `/root/backup-2026-10-18-pre-php83/meta-templates/orig/`.

### `testpage` закрыт, PHPExcel и трейд-ин из Excel убраны (2026-10-18, решение пользователя, все витрины)

`app/addons/my_changes/controllers/frontend/testpage.pre.php` (копия здесь): самописный `app/controllers/frontend/testpage.php`
(24 команды без проверки доступа, часть пишет в базу) не выполняет ни одной команды – ни из интернета, ни с сервера (404).
Задание cron `updateprice` (раз в час цены других витрин → Xione) остановлено тем же вечером – пользователь напишет своё заново
(прежний crontab – `removed/crontab-maxmobiles.before-updateprice`); цены Xione пока не обновляются. Лента Мегамаркета `generatesearch` (`mi92_s.xml`, забирал goods.ru)
остановлена тем же днём – пользователь давно её не использует: строка убрана из crontab maxmobiles (прежний –
`/root/backup-2026-10-18-pre-php83/removed/crontab-maxmobiles.before`), файлы ленты – там же, `megamarket-feed/`. `PHPExcel-1.8/` (нужна была только `testpage.gettraidin` и `bonusplus.bonusfile`; на PHP 8 не работает)
и `traidin.xlsx` (последний раз 27.01.2025) перенесены в `/root/backup-2026-10-18-pre-php83/removed/` (там README, как вернуть).

- Тем же вечером (пользователь: «убирай это всё – это старьё») из папки сайта убраны и сами контроллеры: `app/controllers/frontend/
  testpage.php` и самописный `app/controllers/frontend/payments.php` (QR-оплата СБП el-plat.ru – последний QR 05.04.2024, кредит
  MoneyCare, PayLate, предоплата; ни один включённый способ оплаты его не использовал), с ним `phpqrcode/` и `qrpng/` – всё в
  `/root/backup-2026-10-18-pre-php83/removed/{payments,testpage}/` (README там же). `payments.*` и `testpage.*` теперь 404; способы
  оплаты в админке (ядро) не затронуты.

### Предел процессов PHP 5 → 20 и мягкий перезапуск (2026-10-18 20:44, боевые на 7.4, решение пользователя – делать при нём, не ночью)

Пулы goodmi.ru/maxmobiles.ru/xione.ru упирались в `pm.max_children = 5` (стандарт шаблона ISPmanager для мелкого хостинга, шаблон
от 13.06.2023 никто не менял) – 46 тыс. раз с июля, 3 660 за 17–18.10, при нагрузке сервера ~1 из 24 ядер.
- `/opt/php74/etc/php-fpm.d/pool.d/{goodmi.ru,maxmobiles.ru,xione.ru}.conf`: `pm.max_children = 20`.
- Шаблон панели `/usr/local/mgr5/etc/templates/fpm_site_pool.conf`: `pm.max_children = 20` – иначе ISPmanager вернёт 5 при любой
  правке сайта и при смене версии PHP; обновление ISPmanager может вернуть шаблон – сверить.
- `/opt/php74/etc/php-fpm.conf` и `/opt/php83/etc/php-fpm.conf`: `process_control_timeout = 10s` – перезапуск PHP-FPM ждёт, пока
  процессы допишут ответы (раньше 0 – перезапуск по правке в панели обрывал запросы, 18.10 18:29 – один 502).
- Перезапуск 20:44:05: 5xx 0, обрывов 0. Через 3 мин: goodmi 9–17 процессов, maxmobiles 7–9, вся память PHP боевых 1,1–1,9 ГБ
  (свободно 33 ГБ), load ≤1,05, упираний в предел 0; средний ответ PHP goodmi 0,14 → 0,09 с, дольше 1 с – 14 → 0.
- Копии до правки – `/root/backup-2026-10-18-pre-php83/before-max-children/`. Откат: вернуть 5 в трёх файлах и в шаблоне,
  `systemctl reload php-fpm74`. Следить: блокировки MyISAM (224 таблицы) при большем числе параллельных запросов.

### Сбор данных «что тормозит, зависает, ждёт» (с 2026-10-18 20:53, боевые)

Пользователь: «надо отследить всё, что тормозит, зависает, долго ждёт – разобрать проблемные места и откуда они идут». Только наблюдение:
- PHP-FPM slowlog (pool.d goodmi/maxmobiles/xione на 7.4 и шаблон панели `fpm_site_pool.conf`): `request_slowlog_timeout = 3s`,
  `slowlog = /var/log/php-fpm-slow/$pool.log`, глубина стека 30 – для каждого запроса дольше 3 с – где он стоит (функция, файл).
- MariaDB: `/etc/mysql/mariadb.conf.d/99-gm-slowlog.cnf` (+ `SET GLOBAL` без перезапуска) – запросы дольше 1 с с Lock_time и планом,
  `/var/log/mysql/mariadb-slow.log` (ротация – стандартная `/etc/logrotate.d/mariadb`).
- `/usr/local/sbin/gm-sampler` (`/etc/cron.d/gm-sampler`, раз в минуту) → `/var/log/gm-sampler.log`: занятые процессы PHP по сайтам,
  load, память, выполняющиеся запросы БД, прирост ожиданий блокировок таблиц и медленных запросов, отклик Manticore; запросы к базе
  дольше 5 с – строками DBLONG. Ротация – `/etc/logrotate.d/gm-perf` (14 дней). Исходники и копии – `_DEV/perf/`.
- Вместе с журналом времени ответа `/var/log/nginx/gm-timing-*.log`. Выключить: убрать 3 строки slowlog из пулов и шаблона
  (reload php-fpm74), удалить 99-gm-slowlog.cnf и `SET GLOBAL slow_query_log=0`, удалить /etc/cron.d/gm-sampler.

### Разбор «что тормозит» 19.10 и первые исправления (боевые goodmi.ru, maxmobiles.ru)

По журналам с 18.10 вечера (gm-timing, php-fpm-slow, mariadb-slow, gm-sampler; отчёты – `_DEV/perf/gm-timing-report.py`,
`gm-slowdb-report.py`):
- Проверки здоровья NGENIX – 50 % времени PHP, с 14.10 пачками по 40–65 в секунду (причина роста упираний в предел процессов).
  `vhosts-resources/{goodmi.ru,maxmobiles.ru}/gm-ngenix-health.conf`: проверки с условным именем сайта (Host `s[0-9]+`, UA `NCDN/`,
  ~90 %) получают от nginx тот же 301 на https://maxmobiles.ru/, без PHP; проверки с настоящим именем (~10 %) идут в PHP, как раньше.
- Боты без cookie на страницах с параметрами отображения («Скидки» Maxmobiles – 76 % медленного времени базы, «Новинки» GOODMi,
  категории): `conf.d/gm-display-params.conf` + `vhosts-resources/<сайт>/gm-display-params.conf` – GET без единого cookie с
  sort_by/sort_order/items_per_page/layout → 301 на адрес без них (features_hash сохраняется; .php и админку не трогаем).
- Ночное зависание ~1 мин в 04:01–04:03: ночная копия ISPmanager (CHECK TABLE + mysqldump) по `cscart_logs` 9,1 ГБ / 38 млн записей
  (почти все «товар обновлён» от МойСклад, хранились 90 дней). Удалено старше 30 дней порциями (`_DEV/perf/gm-logs-cleanup.sh`),
  срок хранения журнала – 30 дней (Настройки → Журналы).
- Копии nginx до правок – `/root/backup-2026-10-18-pre-php83/before-{ngenix-health,display-params}/`.

### Почта закрыта снаружи, Roundcube выключен (2026-10-19, решение пользователя: почтой не пользуется)

Ящиков нет (ни в панели, ни в dovecot), за неделю 13 служебных писем и 0 доставленных, тысячи попыток подбора пароля в сутки.
- `gm-firewall` (`_DEV/security/`): в `PORTS` добавлены 25 465 587 (exim) и 110 143 993 995 (dovecot) – цепочка `GM_CLOSED`,
  свои адреса и 127.0.0.1 пускаются (служебная почта cron работает). Исходящие (CS-Cart → smtp.yandex.ru:465) не затрагиваются.
  Службы exim4/dovecot не останавливали – монитор служб ISPmanager поднял бы их.
- `vhosts-resources/<каждый сайт>/gm-no-webmail.conf` (копия – `all-sites/`): `/roundcube` → 404 до выбора location. Подключение
  пакета `/etc/nginx/vhosts-includes/roundcube-nginx.conf` не трогали (его перезапишет обновление). Для нового сайта в панели – положить
  этот файл и в его папку.
- Копии до правок – `/root/backup-2026-10-18-pre-php83/before-mail-close/`. Открыть почту: убрать порты из `PORTS`,
  `iptables -D INPUT -p tcp --dport N -j GM_CLOSED` (и ip6tables), удалить gm-no-webmail.conf, `nginx -t && reload`.

### DNS-сервер named закрыт снаружи (2026-10-19, решение пользователя)

BIND от ISPmanager – 15 зон (магазины, dev, old, переадресующие и .рф), записи по шаблону панели (NS – заглушки ns1/ns2.example.com),
но ни один домен на этот сервер не делегирован: goodmi.ru и maxmobiles.ru – NGENIX, xione.ru / goodmi92.ru / гудми.рф / гудми92.рф /
ми92.рф – Яндекс, mi92.ru – Cloudflare, максмобайлс.рф – Timeweb; сервер сам спрашивает внешние DNS хостинга (/etc/resolv.conf).
Настоящие записи доменов правятся у этих провайдеров, не в панели. `gm-firewall`: 53/tcp в `PORTS` и новый список `UDP_PORTS="53"`
(правило `-p udp --dport 53 -j GM_CLOSED`). Служба named не остановлена. Проверять закрытие с Mac нельзя – VPN перехватывает DNS
(ответ с чужого адреса), смотреть счётчики `iptables -L INPUT -v -n | grep dpt:53`. Копия до правки – `before-dns-close/`.

### Maxmobiles: скорость, пункт 1 – шрифты и Jivo (2026-10-20)

По замерам карточки товара (новая `maxmobiles_card` и обычная – почти одинаковы; тормозит общее: стили темы 1,6 МБ, скрипты темы).
- **Inter урезан** до латиницы, кириллицы и знаков (₽ — – → ← ★ ✓ № «», U+2000–27BF): `design/themes/abt__unitheme2/media/custom_fonts/inter/*.woff2`
  ~110 → ~61 КБ каждый, все OpenType-функции сохранены (`pyftsubset --layout-features='*'`). Файлы общие – Inter использует и **Xione**
  (тоже русский сайт), GOODMi – нет.
- **nginx maxmobiles.ru**: в правила `expires 365d` добавлены `woff2|ttf` (шрифты отдавались с кэшем на сутки – попадали под соседнее
  правило 24h). Файл `/etc/nginx/vhosts/maxmobiles/maxmobiles.ru.conf` ведёт ISPmanager – после правки сайта в панели проверить.
- **Jivo** (HTML-блок 2648, макет 24: главная, категории, товар; только ПК): вместо `<script src=…jivo… async>` – загрузчик, который
  подключает виджет после `load` по первому действию посетителя (прокрутка, мышь, касание, клавиша) или через 5 с. У GOODMi свой блок 2649 – не трогали.
- **Предзагрузка шрифтов**: ядро (`Styles.php`) кэширует адрес шрифта с меткой одного CSS-файла, а у разных страниц свои CSS со своими
  метками – `inter-regular` качался дважды. Хук `my_changes` `block_styles_before_build_preload_links` (только company_id = 1) убирает
  предзагрузку ядра; шрифты Maxmobiles предзагружает модуль `ab__preload` (метка – от CSS текущей страницы): `inter-regular.woff2`
  и `inter-700.woff2`. Две ссылки на Roboto (`KFOMCnq…`) у Maxmobiles удалены владельцем – в стилях не использовались.
- Бэкап и откат – `/root/gm-backups/2026-10-20-mm-speed/<время>/ROLLBACK.txt` (шрифты, nginx, блок 2648) и `…/preload-<время>/` (my_changes).

### Maxmobiles: «Быстрый заказ» в плитках каталога (2026-10-20)

`design/themes/abt__unitheme2/templates/addons/my_changes/hooks/products/product_multicolumns_list_control.post.tpl` – только
company_id = 1: штатное окно `call_requests.request` («Быстрый заказ», как в карточке) рядом с «В корзину» в плитке
(`blocks/list_templates/grid_list.tpl`, хук `products:product_multicolumns_list_control`). Не для товаров без цены. Тема своё
выводит только на странице товара (`addons/call_requests/hooks/products/add_to_cart.post.tpl`, условие `$details_page`).
Стили – секция MM-15 «Своего CSS» Maxmobiles (`Maxmobiles/_DEV/category/MM-15-category.css`). После добавления хука –
удалить `var/cache/registry/thooks_C/1/*`.
- 2026-10-20: `addons/call_requests/views/call_requests/components/call_requests_content.tpl` – форма «Быстрый заказ» без e-mail
  (только телефон, обязателен) теперь и у Maxmobiles (company_id 1), было только GOODMi (2). Xione – как было.
- 2026-10-20: цена товара в окне «Быстрый заказ» Maxmobiles – `app/addons/my_changes/controllers/frontend/call_requests.post.php`
  (досчёт цены со скидками) + блок `.mm-cr-price` в `call_requests_content.tpl` (только company_id 1).
- 2026-10-20: тот же хук и для GOODMi (company_id 2): класс `gm-gl-quick`, подпись «Быстрый заказ». Стили – секция 71 «Своего CSS»
  GOODMi (`CSS/goodmi-styles.css`): «В корзину» под ценой (цена в настройке темы – в одну строку), на ПК рядом «Быстрый заказ»,
  на телефоне – ниже. Бэкап и откат – `/root/gm-backups/2026-10-20-gm-tiles/live/` (шаблон и CSS до правки); после смены
  шаблона – удалить `var/cache/registry/thooks_C/*`.
- 2026-10-20: окно «Быстрый заказ» GOODMi – секция 72 «Своего CSS» (вид как MM-15f, цвета GOODMi); цена товара в окне и у GOODMi:
  `call_requests_content.tpl` (класс `gm-cr-price` для company 2, `mm-cr-price` для 1) и `call_requests.post.php` (company 1 и 2).
  Бэкап – `/root/gm-backups/2026-10-20-gm-tiles/cr-live/`.
