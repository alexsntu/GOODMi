-- GOODMi: модуль «Маркетинг» (gm_marketing) – перенос маркетинг-портала с VPS price.maxmobiles.ru в админку CS-Cart (2026-10-21).
-- Таблицы повторяют SQLite портала (marketing-portal/src/db.js). Время created_at / due_at / remind_at / seen_at – в UTC, как
-- в портале: routines сравнивают его с текущим UTC (tasks/due), админка показывает в МСК. Символы вне BMP (эмодзи) хранятся как
-- &#N; – соединение CS-Cart с базой в utf8 (3 байта), см. fn_gm_mkt_db_text / fn_gm_mkt_out_text.

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_settings` (
  `name` varchar(64) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_drafts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(32) NOT NULL,
  `title` varchar(1000) NOT NULL,
  `text` mediumtext NOT NULL,
  `source_url` varchar(2000) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_created` (`status`, `created_at`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- «уже видели»: ключ routine + адрес; адрес до 2000 знаков, уникальность – по sha1 адреса
CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_seen` (
  `state_key` varchar(32) NOT NULL,
  `url_hash` char(40) NOT NULL,
  `item_url` varchar(2000) NOT NULL,
  `seen_at` datetime NOT NULL,
  PRIMARY KEY (`state_key`, `url_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_topics` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(1000) NOT NULL,
  `pitch` mediumtext NOT NULL,
  `source_url` varchar(2000) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_tasks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(1000) NOT NULL,
  `description` mediumtext,
  `due_at` datetime DEFAULT NULL,
  `remind_at` datetime DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `reminded` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `due` (`status`, `reminded`, `remind_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_plan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(1000) NOT NULL,
  `type` varchar(32) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `channels` varchar(500) DEFAULT NULL,
  `description` mediumtext,
  `status` varchar(16) NOT NULL DEFAULT 'planned',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_start` (`status`, `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_promo_ideas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(1000) NOT NULL,
  `rationale` mediumtext NOT NULL,
  `mechanic` mediumtext,
  `category` varchar(255) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_reposts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(1000) NOT NULL,
  `summary` mediumtext NOT NULL,
  `source_channel` varchar(255) DEFAULT NULL,
  `source_url` varchar(2000) DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_bonus_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(16) NOT NULL DEFAULT 'preview',
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_bonus_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int unsigned NOT NULL,
  `phone` varchar(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `dup_count` int unsigned NOT NULL DEFAULT 1,
  `requested_deduct` decimal(12,2) NOT NULL,
  `balance_at_preview` decimal(12,2) DEFAULT NULL,
  `pending_at_preview` decimal(12,2) DEFAULT NULL,
  `deducted_step1` decimal(12,2) DEFAULT NULL,
  `deducted_step2` decimal(12,2) NOT NULL DEFAULT 0,
  `step2_needed` tinyint(1) NOT NULL DEFAULT 0,
  `step2_done` tinyint(1) NOT NULL DEFAULT 0,
  `error` text,
  PRIMARY KEY (`id`),
  KEY `batch` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cscart_gm_mkt_bonus_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int unsigned NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `details` text,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `batch` (`batch_id`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
