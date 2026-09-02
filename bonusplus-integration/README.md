# bonusplus-integration

Прокси-сервис между личным кабинетом goodmi.ru (CS-Cart) и API БонусПлюс (bonusplus.pro). Хранит API-ключ на сервере, отдаёт браузеру только баланс и историю бонусов конкретного клиента — сам ключ клиенту никогда не передаётся.

## Состав
- `src/` — Node.js/Express прокси (`GET /api/bonuses?phone=...`)
- `widget/goodmi-bonus-widget.html` — HTML+JS блок для вставки в личный кабинет CS-Cart (Design → Layouts, отдельным HTML-блоком, не в WYSIWYG-описание)
- `scripts/` — Python-скрипты для массовой корректировки (списания) бонусов по списку клиентов; workflow описан в скилле `bonusplus-deduct-bonuses` (`.claude/skills/bonusplus-deduct-bonuses/SKILL.md`)
- `lists/` — рабочие списки клиентов и планы списания (в `.gitignore`, содержат реальные телефоны/имена — никогда не коммитить)

## Локальный запуск
```
npm install
cp .env.example .env   # вписать BONUSPLUS_API_KEY
npm run dev
```
Проверка: `curl http://localhost:3100/healthz`, `curl "http://localhost:3100/api/bonuses?phone=79991234567"`

## Деплой
Тот же VPS, что `cscart-mcp-server` и `marketing-portal`, тот же паттерн (`docker compose up -d --build`), порт 3100 забинден только на `127.0.0.1` — наружу отдаётся через reverse-proxy (nginx) на выбранном поддомене (напр. `bonusplus-proxy.goodmi.ru`) с HTTPS.

После деплоя: в `widget/goodmi-bonus-widget.html` заменить `PROXY_URL` на реальный адрес.

## Как это работает
1. Клиент открывает свой личный кабинет на goodmi.ru — CS-Cart уже проверил, что это именно он.
2. Виджет читает номер телефона из уже отрисованного на странице поля профиля (`input[name="user_data[phone]"]`) — без похода к CS-Cart за токеном.
3. Виджет запрашивает `GET {PROXY_URL}/api/bonuses?phone=...`.
4. Прокси сам, с ключом (который не покидает сервер), запрашивает БонусПлюс: `GET /customer` (баланс, статус/уровень) и `POST /retail/bonusActivities` (история операций), отдаёт браузеру только нужные поля.

**Осознанное ограничение:** телефон не подписывается криптографически — подмена возможна только если знать чужой номер телефона (принято как приемлемый риск для функции «посмотреть свой баланс», решение пользователя 2026-09-02).

## API БонусПлюс — что использовано
- `GET /customer?phone=` — баланс (`availableBonuses`), неактивные бонусы (`notActiveBonuses`), уровень (`discountCardName`/`nextCardName`), сумма до следующего уровня (`purchasesSumToNextCard`), ближайшее сгорание (`nearestBonusesExpirationAmount`/`Date`)
- `POST /retail/bonusActivities` `{phone, rowCount, startRow, sort}` — история операций (`bonusActivities[]`: `receiptDate`, `transactionName`, `description`, `bonusCredit`, `bonusDebit`, `remainAmount`)
- Аутентификация: заголовок `Authorization: ApiKey <ключ, закодированный в Base64>`
- Лимит: 3600 запросов/час
- Полная документация: `https://bonusplus.pro/api/Help`
