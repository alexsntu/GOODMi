<?php
/**
 * Проверка шаблонов модуля Авито без браузера (2026-10-22): поднимает CS-Cart как админку, выполняет контроллер
 * gm_avito.dashboard для витрины и рисует «Обзор» (рамка админки common/mainbox.tpl в CLI не собирается – подменяется
 * выводом capture), а также вкладку «Авито» товара. Только чтение.
 *   cd <сайт> && php render-check.php <company_id> [<product_id>] > /tmp/out.html
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
$company_id = (int) ($argv[1] ?? 0);
$product_id = (int) ($argv[2] ?? 0);
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

use Tygh\Registry;

Registry::set('runtime.company_id', $company_id);
$view = Tygh::$app['view'];
$mode = 'dashboard';
$_REQUEST = ['dispatch' => 'gm_avito.dashboard'];
include getcwd() . '/app/addons/gm_avito/controllers/backend/gm_avito.php';

$dir = getcwd() . '/design/backend/templates/addons/gm_avito';
$src = file_get_contents($dir . '/views/gm_avito/dashboard.tpl');
$src = preg_replace('/\{include file="common\/mainbox\.tpl"[^}]*\}/', '{$smarty.capture.mainbox nofilter}', $src);
$tmp = sys_get_temp_dir() . '/gm-avito-render-dashboard.tpl';
file_put_contents($tmp, $src);
echo "<!-- dashboard -->\n", $view->fetch('file:' . $tmp);
unlink($tmp);

if ($product_id) {
    $view->assign('gm_avito', fn_gm_avito_admin_item($company_id, $product_id));
    echo "\n<!-- product tab -->\n", $view->fetch('file:' . $dir . '/hooks/products/tabs_content.post.tpl');
}
