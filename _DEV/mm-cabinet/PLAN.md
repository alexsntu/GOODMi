# Личный кабинет Maxmobiles – отдельная копия кабинета GOODMi

Решение пользователя 2026-10-14: «делаем отдельную копию для максмобаилс». Код GOODMi (`gm_cabinet`) не трогаем;
у Maxmobiles (company_id = 1) свои имена, файлы, таблицы, ключи, пункты админки. Xione (3) не трогаем.
Минус, принятый пользователем: исправления кабинета (особенно безопасности) нужно вносить в обе копии.

## Проверено 2026-10-14
- Ключи Maxmobiles в `cscart-mcp-server/.env` рабочие: БонусПлюс (заголовок `ApiKey ` + base64 ключа, как у GOODMi;
  тестовый номер найден, карта «Pro»), МойСклад (организация «1. maxmobiles», формат Bearer).
- Таргет СМС: логин/пароль общие с GOODMi, отправитель `Maxmobiles`.
- Телефон оператора 8 (800) 250-81-58 (`tel:+78002508158`) – совпадает с сайтом.
- Уровни программы Maxmobiles (страница /bonusnaya-programma-ru/): **Pro** – с первой покупки, кэшбэк до 5%
  (техника 1%, аудио и приставки 1%, аксессуары 4%, услуги 5%); **Ultra** – от 1 500 000 ₽ покупок, до 10%
  (1,5% / 2% / 6% / 10%). Бонусы через 14 дней, 1 бонус = 1 ₽, до 40% покупки. Есть карта в Wallet (QR).

## Карта соответствия GOODMi → Maxmobiles

| GOODMi | Maxmobiles |
|---|---|
| `app/gm_cabinet/lib.php`, `store.php`, `secrets.php` | `app/mm_cabinet/lib.php`, `store.php`, `secrets.php` |
| функции `gm_cab_*`, `fn_gm_cab_*`, константы `GM_CAB_*` | `mm_cab_*`, `fn_mm_cab_*`, `MM_CAB_*` |
| таблицы `cscart_gm_phone_verify`, `gm_phone_auth`, `gm_auth_log`, `gm_ms_orders` (+ проверить остальные) | `cscart_mm_*` |
| `app/controllers/frontend/gm_cabinet.php`, `backend/gm_cabinet.php` | `.../mm_cabinet.php` |
| шаблоны `views/gm_cabinet/*` (витрина и админка) | `views/mm_cabinet/*` |
| хуки в `gm_delivery_date` (init.php/func.php: auth_routines, update_user_pre, update_profile, before_dispatch, is_order_allowed_post, product_reviews_…) | `my_changes` (init.php/func.php) с условием company_id = 1 |
| `gm_delivery_date/controllers/frontend/auth.pre.php`, `profiles.pre.php`, `checkout.pre.php` | `my_changes/controllers/frontend/*.pre.php` |
| `gm_delivery_date/schemas/menu/menu.post.php`, админ-хуки `design/backend/templates/addons/gm_delivery_date/hooks/*` | `my_changes/...` |
| `js/addons/gm_delivery_date/auth.js` (заглушка почты `@goodmi.ru`) | `js/addons/my_changes/auth.js` (`@maxmobiles.ru`) |
| общие файлы темы с условием company_id = 2: `views/profiles/components/profiles_info.tpl`, `blocks/static_templates/profile_info.tpl`, `addons/abt__unitheme2/hooks/profiles/my_account_menu.post.tpl`, `.../hooks/wrapper/mainbox_general_title_wrapper.pre.tpl`, `addons/rf_yandex_smartcaptcha/hooks/index/scripts.post.tpl` | развилка `elseif company_id == 1` → свой файл в `my_changes` (как с окном выбора города) |
| стили секции 44–57, 64 «Своего CSS» GOODMi | секции MM-* «Своего CSS» Maxmobiles, синие цвета |
| сводка `gm_cabinet.auth_status` + облачная задача → Telegram | `mm_cabinet.auth_status` (облачная задача – в другом аккаунте claude.ai, см. reference-claude-accounts-tools) |

Константы GOODMi, которые меняются: адреса goodmi.ru, телефон 8 (800) 250-17-00, тексты СМС «Код подтверждения
GOODMi», почта-заглушка `@goodmi.ru` / `mail@goodmi.ru`, уровни `gm_cab_levels()`, скрытые каналы продаж МойСклад
`gm_cab_hidden_channels()` (у Maxmobiles свои – посмотреть в МойСклад), статусы заказов МойСклад (сверить с
состояниями МойСклад Maxmobiles), `company_id = 2` в запросах.

## Шаги
1. [ ] Копия кода с новыми именами, константы Maxmobiles, уровни Pro/Ultra; изолированная проверка на сервере.
2. [ ] Таблицы `cscart_mm_*` в базе (запись – только по «да» пользователя).
3. [ ] Страницы «Мои бонусы», «Мои заказы», «Купленные товары», меню аккаунта, стили – проверка под тестовой записью.
4. [ ] Вход и регистрация по номеру, СМС, восстановление пароля, блокировки – сначала тестовый номер, потом все.
5. [ ] Админка (подтверждение номера, заблокированные номера) и оповещения.
6. [ ] Покупатели: подтверждение номеров и дубли (3575 покупателей) – список до изменений.

## Открытые вопросы
- Карта лояльности со штрих-кодом (у GOODMi) – у Maxmobiles карта в Wallet с QR; что показывать в «Моих бонусах»?
- МойСклад Maxmobiles (прочитано 2026-10-14): каналы продаж – «Магазин Вконтакте» (4 шт.), «https://old.maxmobiles.ru/…/1c_exchange.php»,
  «MaxMobiles-CS», «Гудми», «Xione», «Xione - CS», «Консультация сайт»; точки продаж – «ДМ», «БМ». Статусы заказа:
  Новый, Нет связи с клиентом, Клиент думает, Уточняем возможность заказа, Требуется согласование, Предоплата внесена,
  Кредит, «Тинькофф Муленко», «Тинькофф Андрей» (имена сотрудников – покупателю не показывать!), Заказан, доставляется
  на склад, Готов к выдаче, Прозвонили, ожидает клиента, Доставка, Выдан, Отмена: … (6 видов), Данного товара нет на
  складе, В процессе подготовки, YA Отменен, Открыт, Обработан, Выполнен, Аннулирован, Неудача, Отклонен, Отложен.
  Отгрузки без статусов. Нужны решения: какие каналы скрыть (кандидаты: Гудми, Xione, Xione - CS; «MaxMobiles-CS» –
  заказы сайта, их берём из базы сайта?), как переводить статусы для покупателя.

## Решения пользователя
- 2026-10-14, каналы продаж: «все что из интернет магазина это MaxMobiles-CS – остальное это покупки в магазине».
  Скрывать в «Моих заказах» только «MaxMobiles-CS» (заказы сайта берутся из базы сайта). Ответ на уточнение
  про «Гудми», «Xione», «Xione - CS»: «эти 3 канала исключаем, это не продажи» – скрыты (mm_cab_hidden_channels).
- 2026-10-14, статусы МойСклад для покупателя – предложенный перевод подходит, с поправкой: «Тинькофф Муленко»,
  «Тинькофф Андрей» = заказ в обработке и внесена предоплата → «Предоплата получена». Итог:
  Кредит → «Оформление кредита»; Тинькофф … и «Предоплата внесена» → «Предоплата получена»; Новый, Клиент думает,
  Требуется согласование, Уточняем возможность заказа, В процессе подготовки → «В обработке»; Заказан, доставляется на
  склад → «Ожидает поступления»; Готов к выдаче, Прозвонили… → «Готов к выдаче»; Доставка → «Доставляется»; Выдан,
  Выполнен → «Выдан»; все «Отмена: …», YA Отменен, Аннулирован, Неудача, Отклонен, Данного товара нет на складе →
  «Отменён»; Нет связи с клиентом → «Не смогли связаться»; Отложен → «Отложен»; Открыт, Обработан → «В обработке».
- 2026-10-14, карта лояльности в «Моих бонусах»: и штрих-код (как GOODMi), и кнопка «Добавить карту в Wallet»
  (ссылка программы со страницы бонусов: https://k.bonusplus.pro/!16YTQBZ5 , электронная карта …/Ecard).

## Прогресс шага 1 (2026-10-14)
- Сделана копия с переименованием: `app/mm_cabinet/lib.php`, `store.php`, `app/controllers/frontend|backend/mm_cabinet.php`,
  шаблоны `views/mm_cabinet/*` (витрина и админка) – в `server-overrides/`. Таблицы `?:mm_phone_verify`, `mm_phone_auth`,
  `mm_auth_log`, `mm_ms_orders`, `mm_ms_order_items`; ключи сессии/кэша `mm_*`.
- Константы: company_id 1, телефон 8 (800) 250-81-58, заглушка `@maxmobiles.ru`, программа /bonusnaya-programma-ru/,
  тексты «Maxmobiles». Уровни Pro/Ultra (`mm_cab_levels`, img пока пустые – пользователь пришлёт картинки карт).
  Скрытый канал – только `maxmobiles-cs`. Перевод статусов проверен на всех 30 статусах (`status-test.php`).
- Ночная загрузка МойСклад GOODMi: crontab пользователя maxmobiles, 03:20, `index.php --dispatch=gm_cabinet.sync_orders
  --minutes=40` – для Maxmobiles нужна своя строка (`mm_cabinet.sync_orders`) после создания таблиц.
- Сделано дальше: обработчики событий магазина перенесены в `my_changes/func.php` (fn_my_changes_*, company 1) и
  подключены в `my_changes/init.php`; pre-контроллеры `my_changes/controllers/frontend/{auth,profiles,checkout}.pre.php`;
  `js/addons/my_changes/auth.js` (заглушка `@maxmobiles.ru`); в «Моих бонусах» блок «Карта лояльности» – штрих-код
  + кнопка «Добавить карту в Wallet» (https://k.bonusplus.pro/!16YTQBZ5), карта уровня рисуется стилями, пока нет
  картинки; тексты программы Maxmobiles (Pro с первой покупки, до 300 бонусов, 14 дней, до 40%).
- Порядок выкладки: сначала таблицы (шаг 2), потом код – без таблиц обработчики молча отступают, но не спешить.
- Картинки уровней (прислал пользователь, Pro.png/Ultra.png 685×395 с прозрачностью) сжаты в webp ~11 КБ и
  ЗАГРУЖЕНЫ на сервер: https://maxmobiles.ru/images/mm_cabinet/card-pro.webp, card-ultra.webp (копии – server-overrides/
  images/mm_cabinet/); прописаны в mm_cab_levels, в шаблоне размеры 300×173.
- Админка скопирована: `my_changes/schemas/menu/menu.post.php` (пункты «… Maxmobiles», позиции 152/153),
  `design/backend/templates/addons/my_changes/hooks/{orders,profiles}/*` (классы mm-mark-*, fn_my_changes_mm_phone_mark).
- Ещё не сделано в шаге 1: развилки в общих шаблонах темы (profiles_info.tpl, profile_info.tpl, my_account_menu.post.tpl,
  mainbox_general_title_wrapper.pre.tpl, капча), подключение auth.js в scripts.post.tpl my_changes, стили MM-*.
  Картинки уровней Pro/Ultra – ждём от пользователя.

## Пауза 2026-10-14 вечер
Пользователь: «Пока давай остановимся и продолжим позже». Продолжать с «Ещё не сделано в шаге 1» (развилки в общих
шаблонах темы, подключение auth.js, стили MM-*), затем шаг 2 (таблицы – по «да»). Этап 3 (карточка) – отдельно, 2026-10-15.
ВНИМАНИЕ: `server-overrides/app/addons/my_changes/func.php` и `init.php` в репозитории уже содержат обработчики кабинета
Maxmobiles, которых НА СЕРВЕРЕ ЕЩЁ НЕТ (там версия с одной группировкой сравнения). Зеркало опережает сервер – не
«восстанавливать» эти два файла на сервер из зеркала до выкладки кабинета (шаг 2 – таблицы, потом код).
