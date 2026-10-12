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
