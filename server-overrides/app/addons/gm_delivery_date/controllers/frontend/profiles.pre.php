<?php
/**
 * GOODMi: личный кабинет – подтверждённый номер телефона нельзя изменить прямо в профиле.
 * Лежит в модуле gm_delivery_date только потому, что CS-Cart подключает pre-контроллеры лишь из модулей;
 * логика – в app/gm_cabinet/store.php. При сохранении профиля (profiles.update) номер подменяется текущим,
 * при показе страницы в шаблон передаётся $gm_phone_locked (поле гасится стилями, рядом – ссылка на смену номера
 * через код из СМС: gm_cabinet.phone). Только витрина GOODMi и только вошедший покупатель.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode === 'update' && !empty($auth['user_id']) && (int) Registry::get('runtime.company_id') === 2) {
    $gm_store = Registry::get('config.dir.root') . '/app/gm_cabinet/store.php';
    if (is_file($gm_store)) {
        try {
            require_once $gm_store;
            if (fn_gm_cab_phone_locked((int) $auth['user_id'])) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['user_data']) && is_array($_REQUEST['user_data'])) {
                    $current = db_get_field('SELECT phone FROM ?:users WHERE user_id = ?i', (int) $auth['user_id']);
                    foreach (['phone', 's_phone', 'b_phone'] as $field) {
                        if (isset($_REQUEST['user_data'][$field])) {
                            $_REQUEST['user_data'][$field] = $current;
                            $_POST['user_data'][$field] = $current;
                        }
                    }
                } else {
                    Tygh::$app['view']->assign('gm_phone_locked', true);
                }
            }
        } catch (\Throwable $e) {
            // Защита номера необязательна для работы профиля: при сбое страница остаётся штатной
        }
    }
}
