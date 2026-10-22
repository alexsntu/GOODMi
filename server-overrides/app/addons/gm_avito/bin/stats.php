<?php
/**
 * GOODMi: статистика объявлений и баланс кабинета Авито → модуль (cron 09:30 и 15:30 – к 10:00 и 16:00, только CLI, 2026-10-20).
 *   php app/addons/gm_avito/bin/stats.php <company_id> [<company_id> …] [--days=30] [--force]
 *   --force – перезапросить все дни периода (догрузка новых метрик, например расходов 2026-10-22)
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
chdir(dirname(__DIR__, 4));
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$days = 30;
$force = in_array('--force', $argv, true);
foreach ($argv as $arg) {
    if (preg_match('/^--days=(\d+)$/', $arg, $m)) {
        $days = max(1, min(90, (int) $m[1]));
    }
}
$companies = array_filter(array_map('intval', array_filter(array_slice($argv, 1), 'is_numeric')));
if (!$companies) {
    exit("Нужно: company_id (1 – Maxmobiles, 2 – GOODMi) [--days=30]\n");
}
foreach ($companies as $company_id) {
    try {
        $balance = fn_gm_avito_sync_balance($company_id);
        $stats = fn_gm_avito_sync_stats($company_id, $days, $force);
        $promos = fn_gm_avito_sync_promotions($company_id);
        echo date('Y-m-d H:i'), ' витрина ', $company_id, ': баланс кошелёк ', $balance['wallet'], ' ₽, CPA ', $balance['cpa'], ' ₽; статистика – дней ', $stats['days'], ', строк ', $stats['rows'], '; ставок ', count($promos['items']), PHP_EOL;
    } catch (Exception $e) {
        echo date('Y-m-d H:i'), ' витрина ', $company_id, ': ошибка – ', $e->getMessage(), PHP_EOL;
    }
}
