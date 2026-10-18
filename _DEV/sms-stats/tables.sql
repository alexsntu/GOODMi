-- СМС всех витрин (2026-10-18): данные кабинета Таргет СМС, собирает /root/sms-stats/sms-stats-collect.php раз в час.
-- Текст СМС (в нём коды) и номер целиком не храним: отпечаток номера (для подсчёта разных номеров) и последние 2 цифры.

-- Каждая часть СМС – отдельная строка оператора (id_state); сообщение – id_sms, цена – за часть
CREATE TABLE IF NOT EXISTS cscart_sms_stat (
  id_state bigint unsigned NOT NULL,
  id_sms bigint unsigned NOT NULL DEFAULT 0,
  ts int unsigned NOT NULL DEFAULT 0,
  changed int unsigned NOT NULL DEFAULT 0,
  kind varchar(60) NOT NULL DEFAULT '',
  originator varchar(20) NOT NULL DEFAULT '',
  operator varchar(40) NOT NULL DEFAULT '',
  status varchar(16) NOT NULL DEFAULT '',
  price decimal(8,2) NOT NULL DEFAULT 0,
  parts tinyint unsigned NOT NULL DEFAULT 1,
  part_no tinyint unsigned NOT NULL DEFAULT 1,
  phone_hash char(12) NOT NULL DEFAULT '',
  phone_tail char(2) NOT NULL DEFAULT '',
  PRIMARY KEY (id_state),
  KEY ts (ts),
  KEY kind_ts (kind, ts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Баланс раз в час; error – сбой сбора (пусто – всё в порядке)
CREATE TABLE IF NOT EXISTS cscart_sms_balance (
  ts int unsigned NOT NULL,
  money decimal(10,2) NOT NULL DEFAULT 0,
  stat_rows int unsigned NOT NULL DEFAULT 0,
  error varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (ts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
