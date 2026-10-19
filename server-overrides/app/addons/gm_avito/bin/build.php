<?php
/**
 * GOODMi: сборка файла автозагрузки Авито (cron, только CLI, 2026-10-19).
 *   php app/addons/gm_avito/bin/build.php <company_id> [<company_id> …]
 * Витрина задаётся явно: в CLI index.php берёт витрину по умолчанию, а не по адресу.
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
    $result = fn_gm_avito_build_feed($company_id);
    echo 'витрина ', $company_id, ': объявлений ', $result['ads'], ', пропущено ', count($result['skipped']), PHP_EOL;
    foreach (array_slice($result['skipped'], 0, 20, true) as $product_id => $reason) {
        echo '  товар ', $product_id, ': ', $reason, PHP_EOL;
    }
}
