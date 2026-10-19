<?php
/**
 * GOODMi: отчёт Авито о последней загрузке → модуль (cron, только CLI, 2026-10-19).
 *   php app/addons/gm_avito/bin/sync.php <company_id> [<company_id> …]
 * Номер объявления на Авито, статус, ссылка, ошибки и предупреждения по каждому объявлению (fn_gm_avito_sync_report).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
chdir(dirname(__DIR__, 4));
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$companies = array_filter(array_map('intval', array_slice($argv, 1)));
if (!$companies) {
    exit("Нужно: company_id (1 – Maxmobiles, 2 – GOODMi)\n");
}
foreach ($companies as $company_id) {
    try {
        $report = fn_gm_avito_sync_report($company_id);
        $sections = [];
        foreach ((array) ($report['stats']['sections'] ?? []) as $section) {
            $sections[] = $section['title'] . ' ' . $section['count'];
        }
        echo date('Y-m-d H:i'), ' витрина ', $company_id, ': загрузка ', $report['upload_id'], ' (', $report['started_at'], ', ', $report['upload_status'], '), наших объявлений ',
            $report['ads'], ': с замечаниями ', $report['with_problems'], ', опубликовано без замечаний ', $report['published'], ', нет на Авито ', $report['not_on_avito'],
            '; не наших в отчёте ', $report['unknown_count'], '; ', implode('; ', $sections), PHP_EOL;
    } catch (Exception $e) {
        echo date('Y-m-d H:i'), ' витрина ', $company_id, ': ошибка – ', $e->getMessage(), PHP_EOL;
    }
}
