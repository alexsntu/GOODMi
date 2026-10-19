<?php
/**
 * GOODMi: сборка файла автозагрузки Авито (cron, только CLI, 2026-10-19).
 *   php app/addons/gm_avito/bin/build.php <company_id> [<company_id> …] [--test]
 * --test – пробный файл feed-test.xml (по 2 товара в наличии из каждой категории с шаблоном) для проверки на xmlcheck.
 * Витрина задаётся явно: в CLI index.php берёт витрину по умолчанию, а не по адресу. После сборки – проверка для страницы
 * «Авито: проблемы» (fn_gm_avito_check).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
chdir(dirname(__DIR__, 4));
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$test = in_array('--test', $argv, true) ? 2 : 0;
$companies = array_filter(array_map('intval', array_slice($argv, 1)));
if (!$companies) {
    exit("Нужно: company_id (1 – Maxmobiles, 2 – GOODMi)\n");
}
foreach ($companies as $company_id) {
    $result = fn_gm_avito_build_feed($company_id, $test);
    if ($test) {
        echo 'витрина ', $company_id, ': пробный файл ', $result['path'], ', объявлений ', $result['ads'], PHP_EOL;
        continue;
    }
    echo 'витрина ', $company_id, ': объявлений ', $result['ads'], ', пропущено ', count($result['skipped']), PHP_EOL;
    foreach (array_slice($result['skipped'], 0, 20, true) as $product_id => $reason) {
        echo '  товар ', $product_id, ': ', $reason, PHP_EOL;
    }
    $check = fn_gm_avito_check($company_id);
    echo '  проверка: включено ', $check['enabled'], ', в файле ', $check['in_file'], ', категорий с незаполненными полями ', count($check['missing']),
        ', без шаблона ', count($check['no_template']), PHP_EOL;
}
