-- Модуль gm_avito (2026-10-19): таблицы. Ставятся вручную (как у gm_delivery_date / gm_cabinet), без install-запросов addon.xml.

-- Настройки витрины (company_id: 1 – Maxmobiles, 2 – GOODMi): адрес, телефон, способ связи, шапка/подвал описания,
-- правило цены, число фото, адрес сайта для фото, ключ ссылки на файл и т.п. Значение – строка или JSON.
CREATE TABLE IF NOT EXISTS cscart_gm_avito_settings (
    company_id INT UNSIGNED NOT NULL,
    name VARCHAR(64) NOT NULL,
    value MEDIUMTEXT,
    PRIMARY KEY (company_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Товары Авито: один товар сайта = одно объявление. ad_id – Id объявления в файле (перенесён из UnitXML = UUID товара
-- МойСклад, менять нельзя – Авито опубликует заново); avito_id – номер объявления на Авито (из API);
-- title / description – заголовок и «описание для Авито» (правим вручную и вместе с ассистентом);
-- fields – JSON остальных полей объявления (Category, GoodsType, Brand …); status / status_text – из отчётов Авито.
CREATE TABLE IF NOT EXISTS cscart_gm_avito_items (
    company_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    ad_id VARCHAR(64) NOT NULL,
    avito_id BIGINT UNSIGNED NULL,
    enabled CHAR(1) NOT NULL DEFAULT 'Y',
    title VARCHAR(255) NOT NULL DEFAULT '',
    description MEDIUMTEXT,
    fields MEDIUMTEXT,
    status VARCHAR(32) NOT NULL DEFAULT '',
    status_text TEXT,
    updated_at INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (company_id, product_id),
    UNIQUE KEY ad_id (company_id, ad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
