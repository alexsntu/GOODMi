-- Модуль gm_avito (2026-10-19): таблицы. Ставятся вручную (как у gm_delivery_date / gm_cabinet), без install-запросов addon.xml.

-- Настройки витрины (company_id: 1 – Maxmobiles, 2 – GOODMi): stock_mode (in_stock / out_of_stock / all), text_mode
-- (old / categories / new), new_text_categories, common_block, defaults (JSON полей объявления), images_limit, specs_limit,
-- site_url, feed_key, last_build и т.п. Значение – строка или JSON.
CREATE TABLE IF NOT EXISTS cscart_gm_avito_settings (
    company_id INT UNSIGNED NOT NULL,
    name VARCHAR(64) NOT NULL,
    value MEDIUMTEXT,
    PRIMARY KEY (company_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Товары Авито: строка у каждого товара витрины, enabled – «Продавать на Авито». ad_id – Id объявления в файле (у
-- перенесённых из UnitXML = UUID товара МойСклад, у новых gm2-<product_id> / mm1-<product_id>; менять нельзя – Авито
-- опубликует заново); avito_id – номер объявления на Авито (из API); title / intro – заголовок и «о товаре» (вступление
-- нового описания), *_auto = Y – заполнено автоматически (перезаписывается), N – правлено вручную; description – прежнее
-- описание из UnitXML; fields – JSON полей объявления, важнее шаблона (Category, GoodsType, Brand …); status / status_text –
-- из отчётов Авито.
CREATE TABLE IF NOT EXISTS cscart_gm_avito_items (
    company_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    ad_id VARCHAR(64) NOT NULL,
    avito_id BIGINT UNSIGNED NULL,
    enabled CHAR(1) NOT NULL DEFAULT 'Y',
    title VARCHAR(255) NOT NULL DEFAULT '',
    title_auto CHAR(1) NOT NULL DEFAULT 'N',
    intro MEDIUMTEXT,
    intro_auto CHAR(1) NOT NULL DEFAULT 'Y',
    description MEDIUMTEXT,
    fields MEDIUMTEXT,
    status VARCHAR(32) NOT NULL DEFAULT '',
    status_text TEXT,
    updated_at INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (company_id, product_id),
    UNIQUE KEY ad_id (company_id, ad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Шаблоны категорий: для категории сайта (и вложенных) – поля объявления Авито (JSON) и поля из характеристик товара
-- (auto_fields: Brand, Vendor, Model, Color).
CREATE TABLE IF NOT EXISTS cscart_gm_avito_templates (
    company_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    fields MEDIUMTEXT,
    auto_fields VARCHAR(255) NOT NULL DEFAULT '',
    note VARCHAR(255) NOT NULL DEFAULT '',
    updated_at INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (company_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Переход с этапа 1 (2026-10-19), уже выполнен на mm_work и mm_dev:
-- ALTER TABLE cscart_gm_avito_items ADD COLUMN title_auto CHAR(1) NOT NULL DEFAULT 'N' AFTER title,
--     ADD COLUMN intro MEDIUMTEXT AFTER title_auto, ADD COLUMN intro_auto CHAR(1) NOT NULL DEFAULT 'Y' AFTER intro;
