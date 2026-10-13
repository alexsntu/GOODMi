# Подтверждение номера телефона по СМС (личный кабинет GOODMi)

Состояние на 2026-10-12: код написан и проверен изолированно, **на сервер не выложен** – вход в API Таргет СМС не проходит.

Что готово (в `server-overrides/`, на сервере пока старые версии этих файлов):
- `app/gm_cabinet/lib.php` – `gm_cab_sms_send`, `gm_cab_phone_mask`, `gm_cab_code_hash`, `gm_cab_verify_enabled`
- `app/gm_cabinet/store.php` – `fn_gm_cab_phone_verified`, `fn_gm_cab_verify_state`, `fn_gm_cab_verify_send`, `fn_gm_cab_verify_check`
- `app/controllers/frontend/gm_cabinet.php` – режим `verify`, скрытие данных по номеру до подтверждения
- шаблоны `views/gm_cabinet/components/verify.tpl`, правки `orders.tpl`, `bonuses.tpl`
- `section56.css` рядом – секция 56 «Своего CSS», в файлы стилей ещё не внесена

Перед выкладкой:
1. Таблица:
   ```sql
   CREATE TABLE cscart_gm_phone_verify (
     user_id INT UNSIGNED NOT NULL PRIMARY KEY,
     phone VARCHAR(15) NOT NULL DEFAULT '',
     verified_at INT UNSIGNED NOT NULL DEFAULT 0,
     code_phone VARCHAR(15) NOT NULL DEFAULT '',
     code_hash CHAR(64) NOT NULL DEFAULT '',
     code_expires INT UNSIGNED NOT NULL DEFAULT 0,
     attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
     sent_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
     sent_window INT UNSIGNED NOT NULL DEFAULT 0,
     last_sent INT UNSIGNED NOT NULL DEFAULT 0,
     KEY code_phone (code_phone)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
   ```
2. Ключи в `app/gm_cabinet/secrets.php`: `targetsms_login`, `targetsms_password`, `targetsms_sender`, `verify_salt` (случайная строка),
   `phone_verify` – сначала `[1000355]` (только тестовая запись), после проверки – `true`.
3. Проверка на тестовом номере 79787193485, затем включение для всех.

API Таргет СМС: `POST https://sms.targetsms.ru/sendsmsjson.php`, вход по `security.login` + `security.password` (логин и пароль кабинета
sms.targetsms.ru). Ключ из 32 символов сервис не принимает ни как пароль, ни как `api_key` – ответ «Неправильный логин или пароль».

## 2026-10-13: вход по номеру телефона, регистрация с подтверждением, восстановление пароля по СМС (выложено)

Состояние выше устарело: подтверждение по СМС работает и включено для всех (`phone_verify => true`, `phone_verify_sms => true`).
Вручную номер подтверждает только оператор интернет-магазина (покупатель звонит на 8 (800) 250-17-00 с подтверждаемого номера),
не сотрудники магазинов.

Что добавлено:
- **Вход по номеру или e-mail.** Обработчик `auth_routines` (`fn_gm_delivery_date_auth_routines` в `func.php` модуля
  `gm_delivery_date`): если в поле входа нет `@` и значение приводится к номеру, штатная проверка ищет покупателя, у которого
  этот номер подтверждён и стоит в профиле (`fn_gm_cab_phone_owners`). Пароль прежний. 8 неверных паролей подряд закрывают
  вход по номеру на 15 минут (`fn_gm_cab_login_by_phone`).
- **Регистрация.** `profiles.pre.php`: номер обязателен и не должен быть подтверждён в другой записи; e-mail необязателен –
  вместо пустого записывается заглушка из номера, `79780000000@goodmi.ru` (`fn_gm_cab_stub_email`, уникальная у каждого): с пустым e-mail не создавался заказ, с общей заглушкой не регистрировался второй покупатель. Заглушка
  не настоящий адрес, поэтому по ней нельзя войти и восстановить пароль (`auth_routines`, `auth.pre.php`),
  в профиле она не показывается (`auth.js`). Обработчик `update_profile`
  отправляет код, отмечает запись `must_verify = 1` и ведёт покупателя в «Мои заказы».
- **Полная блокировка до подтверждения.** Обработчик `before_dispatch` (`fn_gm_delivery_date_before_dispatch`): запись с
  `must_verify = 1` и неподтверждённым номером с любой страницы попадает в «Мои заказы» с блоком «Завершите регистрацию»;
  открыты только `gm_cabinet.orders`, `gm_cabinet.verify`, `profiles.update`, `auth.logout`. Быстро снять блокировку –
  убрать `before_dispatch` из `fn_register_hooks` в `init.php` модуля.
- **Запрет заказа до подтверждения (вторая линия).** `checkout.pre.php`: запись с `must_verify = 1` и неподтверждённым номером не попадает
  на оформление и не может отправить заказ (`fn_gm_cab_verify_required`). Колонка добавлена так:
  `ALTER TABLE cscart_gm_phone_verify ADD must_verify TINYINT UNSIGNED NOT NULL DEFAULT 0;`
- **Восстановление пароля.** Страница `gm_cabinet.recover` (шаблон `views/gm_cabinet/recover.tpl`): номер → код и новый
  пароль → вход. Только для подтверждённого номера; иначе текст про оператора и ссылка на восстановление по e-mail.
- **Формы.** `js/addons/gm_delivery_date/auth.js`: подпись «Телефон или e-mail» без проверки на e-mail, e-mail
  необязателен в регистрации и профиле, «Забыли пароль?» ведёт на `gm_cabinet.recover`. Шаблоны темы не менялись.

Таблица (по строке на номер – счётчик неверных паролей и код восстановления):
```sql
CREATE TABLE cscart_gm_phone_auth (
  phone VARCHAR(15) NOT NULL PRIMARY KEY,
  login_fails TINYINT UNSIGNED NOT NULL DEFAULT 0,
  login_window INT UNSIGNED NOT NULL DEFAULT 0,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  code_hash CHAR(64) NOT NULL DEFAULT '',
  code_expires INT UNSIGNED NOT NULL DEFAULT 0,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  sent_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  sent_window INT UNSIGNED NOT NULL DEFAULT 0,
  last_sent INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Откат: вернуть семь файлов из `/root/gm-backups/2026-10-13-phone-login/`, удалить `recover.tpl` и `auth.js`, убрать
скомпилированную копию `scripts.post.tpl` из `var/cache/templates/abt__unitheme2/`. Быстрый выключатель входа и регистрации
по номеру без отката файлов – убрать три новых названия из `fn_register_hooks` в `init.php` модуля.

Шаблоны на сайте сами не перекомпилируются (режим разработки выключен): после правки `.tpl` убирать его скомпилированную
копию. Новый файл скрипта попадает в общий файл скриптов сам, как только перекомпилирован `scripts.post.tpl`.

## 2026-10-13: блокировка номера, журнал, сводка для оповещений, отметка в админке (выложено)

- **Блокировка.** `GM_CAB_STRIKES` (6) неудач подряд по номеру – неверные коды и запросы кода сверх часового лимита –
  блокируют отправку СМС на него (`fn_gm_cab_strike`). Неверные пароли в счёт не идут. Удачный ввод кода счётчик обнуляет.
  Снятие – админка, «Покупатели → Заблокированные номера» (`gm_cabinet.blocked`, `fn_gm_cab_unblock`).
- **Журнал** `cscart_gm_auth_log` (`fn_gm_cab_log`): отправки СМС, отказы сервиса, верные и неверные коды, отказы по лимиту,
  неверные пароли, блокировки. Не чистится – при росте добавить удаление старше 90 дней.
- **Сводка** `gm_cabinet.auth_status&key=…` (ключ `status_key` в `secrets.php`): JSON со счётчиками и списком `alerts`.
  Её должна читать облачная задача и писать в Telegram (с сервера магазина Telegram недоступен); задача ещё не создана.
- **Админка.** Отметка «подтверждён / не подтверждён» у телефона: списки заказов и покупателей, заказ, профиль
  (`design/backend/templates/addons/gm_delivery_date/hooks/`, `fn_gm_delivery_date_phone_mark`).

```sql
ALTER TABLE cscart_gm_phone_auth ADD strikes TINYINT UNSIGNED NOT NULL DEFAULT 0, ADD blocked_at INT UNSIGNED NOT NULL DEFAULT 0,
  ADD blocked_reason VARCHAR(80) NOT NULL DEFAULT '', ADD KEY blocked_at (blocked_at);
ALTER TABLE cscart_gm_phone_verify ADD KEY phone (phone);
CREATE TABLE cscart_gm_auth_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ts INT UNSIGNED NOT NULL DEFAULT 0, phone VARCHAR(15) NOT NULL DEFAULT '',
  user_id INT UNSIGNED NOT NULL DEFAULT 0, event VARCHAR(20) NOT NULL DEFAULT '', info VARCHAR(120) NOT NULL DEFAULT '',
  KEY ts (ts), KEY phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Бэкапы шагов дня – подпапки в `/root/gm-backups/2026-10-13-phone-login/` (`no-email-stub`, `must-verify`, `full-lock`,
`stub-per-phone`, `block`, `admin-mark`). Проверка состояния: `php /root/gm-check-auth.php` на сервере (только чтение).

## 2026-10-13: исправления по ревью и капча (выложено)

По замечаниям `code-review-2026-10-13.md`:
- регистрация при сбое проверки номера отказывает, отметка `must_verify` ставится до отправки кода;
- номер блокируют только неверные коды (6 подряд); запросы кода сверх лимита в счёт не идут;
- восстановление пароля – только для включённых записей; после смены пароля остальные сессии покупателя CS-Cart завершает
  сам (сверяет `password_change_timestamp`);
- один номер – один владелец: подтверждение номера одной записью снимает его у остальных (`fn_gm_cab_verify_claim`);
- скрипт объединения дублей проверяет запись файла отката и показывает, что не перенеслось.

Капча Яндекс SmartCaptcha включена на GOODMi для регистрации и восстановления пароля. Решает основная настройка CS-Cart
«Проверка изображений → использовать для» (object_id 138), строка витрины в `settings_vendor_values`; настройку модуля
(6605) держим такой же. Скрипт – `server-scripts/gm-captcha-setting.php`. Бэкап файлов: `/root/gm-backups/2026-10-13-review-fixes/`.

