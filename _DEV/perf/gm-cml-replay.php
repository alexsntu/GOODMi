<?php
/**
 * Проигрывание сохранённого обмена CommerceML (МойСклад) на ТЕСТОВОЙ копии (2026-10-19): тот же путь, что у
 * app/addons/commerceml/controllers/frontend/commerceml.php – CreateImportCommand по файлу, затем шаги
 * ExecuteCatalogImportCommand по 25 с до конца. Для замера: время, шаги, сколько товаров реально обновлено (по журналу
 * products/update копии), запросов на запись в этом соединении, сколько товаров пропущено нашим обработчиком.
 * Запуск (только CLI, из корня копии, от www-root): php var/gm-cml-replay.php <company_id> <файл> [файл ...]
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
define('COMMERCEML_IMPORT', true);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'dev.goodmi.ru';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
chdir(dirname(__DIR__));
require 'init.php';

use Tygh\Addons\CommerceML\ServiceProvider;
use Tygh\Addons\CommerceML\Commands\CreateImportCommand;
use Tygh\Addons\CommerceML\Commands\ExecuteCatalogImportCommand;
use Tygh\Addons\CommerceML\Dto\ImportDto;
use Tygh\Addons\CommerceML\Dto\ProductDto;
use Tygh\Registry;

if (strpos((string) db_get_field('SELECT DATABASE()'), 'mm_dev') !== 0) {
    exit("Не тестовая база – стоп\n");
}
$company_id = (int) $argv[1];
Registry::set('runtime.company_id', $company_id);
$bus = ServiceProvider::getCommandBus();
$auth = ['user_id' => 16];
$writes = static function () {
    $s = db_get_hash_single_array("SHOW SESSION STATUS WHERE Variable_name IN ('Com_insert','Com_update','Com_delete','Com_replace')", ['Variable_name', 'Value']);
    return array_sum(array_map('intval', $s));
};

foreach (array_slice($argv, 2) as $path) {
    $t0 = microtime(true);
    $ts0 = time();
    $w0 = $writes();
    $GLOBALS['gm_cml_skipped'] = 0;
    $import_key = md5(microtime());
    $r = $bus->dispatch(CreateImportCommand::create([$path], $auth, $import_key, ImportDto::IMPORT_TYPE_CATALOG, $company_id));
    if (!$r->isSuccess()) {
        echo basename($path), ': ошибка разбора: ', implode('; ', $r->getErrors()), PHP_EOL;
        continue;
    }
    $import = $r->getData('import');
    $t_parse = microtime(true) - $t0;
    $steps = 0;
    do {
        $r = $bus->dispatch(ExecuteCatalogImportCommand::create($import->import_id, 25, ProductDto::REPRESENT_ENTITY_TYPE));
        $steps++;
        if ($r->isFailure()) {
            echo basename($path), ': ошибка шага ', $steps, ': ', implode('; ', $r->getErrors()), PHP_EOL;
            break;
        }
        $import = $r->getData('import');
    } while (!$import->isStatusFinished() && $steps < 500);
    $updated = (int) db_get_field("SELECT COUNT(*) FROM ?:logs WHERE type = 'products' AND action = 'update' AND timestamp >= ?i", $ts0);
    printf(
        "%s: всего %.0f с (разбор %.0f с), шагов %d, обновлено товаров %d, пропущено обработчиком %d, запросов на запись %d\n",
        basename($path), microtime(true) - $t0, $t_parse, $steps, $updated, $GLOBALS['gm_cml_skipped'], $writes() - $w0
    );
}
