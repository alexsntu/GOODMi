<?php
/**
 * GOODMi: админка своего поиска (2026-10-16) – статистика для улучшения и обучения поиска, проверка запроса, индекс.
 * heaven.php?dispatch=gm_search.<раздел>[&days=7]:
 *   manage   – обзор: поиски, нулевые показы, нулевые клики, CTR, место клика, корзина и заказы после поиска, по дням;
 *   queries  – все запросы с показателями (сортировка sort=, фильтр f=);
 *   zero     – ничего не найдено; noclick – нашли, но не кликнули;
 *   suggest  – что выбирают в подсказках и брошенный ввод;
 *   orders   – заказы после поиска (купили ли кликнутый товар);
 *   check    – проверка запроса q=: как понят и почему товары стоят в таком порядке;
 *   candidates – кандидаты в синонимы из переформулировок (POST action=accept – сделать синонимом, dismiss – скрыть пару);
 *   synonyms – свои синонимы (POST action=save|toggle|delete); pins – закрепления товаров по запросу (action=add|delete|up|down);
 *              изменения выгружаются в app/gm_search/custom.json и сразу действуют в поиске;
 *   index    – состояние индекса и запуски сборки; POST action=rebuild – пересобрать (флаг, cron раз в минуту).
 * Расчёты – app/gm_search/stats.php, поиск – app/gm_search/lib.php; шаблоны – design/backend/templates/views/gm_search/.
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$gm_root = Registry::get('config.dir.root');
require_once $gm_root . '/app/gm_search/lib.php';
require_once $gm_root . '/app/gm_search/stats.php';

$gm_mc = @new mysqli('127.0.0.1', '', '', '', 9306);
if ($gm_mc->connect_errno) {
    $gm_mc = null;
} else {
    $gm_mc->set_charset('utf8mb4');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'index' && isset($_REQUEST['action']) && $_REQUEST['action'] === 'rebuild') {
        // Сборку запускает cron (root) раз в минуту, если есть флаг – у веб-сервера нет доступа к скриптам /root/gm-search
        $dir = $gm_root . '/var/gm_search';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/rebuild.flag', (string) time());
        fn_set_notification('N', 'Готово', 'Индекс пересоберётся в течение минуты. Обновите страницу, чтобы увидеть новый запуск.');
    }

    $act = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
    $res = null;
    if ($mode === 'synonyms') {
        if ($act === 'save') { $res = gm_stats_synonym_save((string) ($_REQUEST['src'] ?? ''), (string) ($_REQUEST['dst'] ?? ''), (string) ($_REQUEST['note'] ?? '')); }
        elseif ($act === 'toggle') { gm_stats_synonym_toggle((int) ($_REQUEST['id'] ?? 0), !empty($_REQUEST['active'])); }
        elseif ($act === 'delete') { gm_stats_synonym_delete((int) ($_REQUEST['id'] ?? 0)); $res = ['ok' => true, 'message' => 'Синоним удалён.']; }
    } elseif ($mode === 'candidates') {
        if ($act === 'accept') {
            $res = gm_stats_synonym_save((string) ($_REQUEST['src'] ?? ''), (string) ($_REQUEST['dst'] ?? ''),
                mb_substr('из переформулировки: «' . (string) ($_REQUEST['a'] ?? '') . '» → «' . (string) ($_REQUEST['b'] ?? '') . '»', 0, 120));
        } elseif ($act === 'dismiss') {
            gm_stats_candidate_dismiss((string) ($_REQUEST['a'] ?? ''), (string) ($_REQUEST['b'] ?? ''));
            $res = ['ok' => true, 'message' => 'Пара скрыта и больше не появится в кандидатах.'];
        }
    } elseif ($mode === 'pins') {
        if ($act === 'add') { $res = gm_stats_pin_add((string) ($_REQUEST['q'] ?? ''), (int) ($_REQUEST['product_id'] ?? 0)); }
        elseif ($act === 'delete') { gm_stats_pin_delete((int) ($_REQUEST['id'] ?? 0)); $res = ['ok' => true, 'message' => 'Закрепление снято.']; }
        elseif ($act === 'up' || $act === 'down') { gm_stats_pin_move((int) ($_REQUEST['id'] ?? 0), $act === 'up' ? -1 : 1); }
    }
    if ($res) {
        fn_set_notification($res['ok'] ? 'N' : 'E', $res['ok'] ? 'Готово' : 'Ошибка', $res['message']);
    }
    $back = $mode === 'pins' && !empty($_REQUEST['q']) ? 'gm_search.pins?q=' . urlencode((string) $_REQUEST['q']) : 'gm_search.' . $mode;
    if ($mode === 'candidates' && !empty($_REQUEST['days'])) { $back .= '?days=' . (int) $_REQUEST['days']; }
    // Синонимы: вернуться на ту же страницу списка и с тем же поиском
    if ($mode === 'synonyms' && (!empty($_REQUEST['find']) || !empty($_REQUEST['page']))) {
        $back .= '?find=' . urlencode((string) ($_REQUEST['find'] ?? '')) . '&page=' . (int) ($_REQUEST['page'] ?? 1);
    }

    return [CONTROLLER_STATUS_OK, $back];
}

$gm_days = isset($_REQUEST['days']) && isset(gm_stats_periods()[(int) $_REQUEST['days']]) ? (int) $_REQUEST['days'] : 7;
$gm_from = gm_stats_from($gm_days);
$view = Tygh::$app['view'];
$view->assign('gm_days', $gm_days);
$view->assign('gm_periods', gm_stats_periods());
$view->assign('gm_mode', $mode);
$view->assign('gm_tabs', [
    'manage' => 'Обзор', 'queries' => 'Запросы', 'zero' => 'Ничего не найдено', 'noclick' => 'Нашли, но не кликнули',
    'suggest' => 'Подсказки', 'orders' => 'Заказы после поиска', 'check' => 'Проверка запроса', 'candidates' => 'Кандидаты в синонимы', 'synonyms' => 'Синонимы',
    'pins' => 'Закрепления', 'index' => 'Индекс',
]);

if ($mode === 'manage') {
    $view->assign('gm_o', gm_stats_overview($gm_from));
    $view->assign('gm_top', gm_stats_queries($gm_from, 'all', 'searches', '', 15));
    $view->assign('gm_top_zero', gm_stats_queries($gm_from, 'zero', 'searches', '', 10));
    $view->assign('gm_top_noclick', gm_stats_queries($gm_from, 'noclick', 'searches', '', 10));
} elseif ($mode === 'queries' || $mode === 'zero' || $mode === 'noclick') {
    $sort = isset($_REQUEST['sort']) ? (string) $_REQUEST['sort'] : 'searches';
    $filter = isset($_REQUEST['f']) ? mb_substr(trim((string) $_REQUEST['f']), 0, 60) : '';
    $view->assign('gm_rows', gm_stats_queries($gm_from, $mode === 'queries' ? 'all' : $mode, $sort, $filter, 300));
    $view->assign('gm_sort', $sort);
    $view->assign('gm_filter', $filter);
} elseif ($mode === 'suggest') {
    $view->assign('gm_s', gm_stats_suggest($gm_from));
} elseif ($mode === 'orders') {
    $view->assign('gm_rows', gm_stats_orders($gm_from));
} elseif ($mode === 'check') {
    $q = isset($_REQUEST['q']) ? mb_substr(trim((string) $_REQUEST['q']), 0, 100) : '';
    $view->assign('gm_q', $q);
    if ($q !== '' && $gm_mc) {
        $view->assign('gm_e', gm_search_explain($gm_mc, $q, 30));
        $view->assign('gm_sg', gm_search_suggest($gm_mc, $q));
    }
    $view->assign('gm_engine', (bool) $gm_mc);
} elseif ($mode === 'candidates') {
    $view->assign('gm_rows', gm_stats_candidates($gm_from, 150));
} elseif ($mode === 'synonyms') {
    $gm_list = gm_stats_synonyms(isset($_REQUEST['find']) ? mb_substr((string) $_REQUEST['find'], 0, 60) : '', isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1, 25);
    $view->assign('gm_list', $gm_list);
    $view->assign('gm_rows', $gm_list['rows']);
    $view->assign('gm_src', isset($_REQUEST['src']) ? mb_substr((string) $_REQUEST['src'], 0, 80) : '');
    $view->assign('gm_builtin', count(gm_search_synonyms()) - count(db_get_fields('SELECT src FROM ?:gm_search_synonyms WHERE active = 1')));
} elseif ($mode === 'pins') {
    $q = isset($_REQUEST['q']) ? mb_substr(trim((string) $_REQUEST['q']), 0, 100) : '';
    $view->assign('gm_q', $q);
    $view->assign('gm_rows', gm_stats_pins());
    if ($q !== '' && $gm_mc) {
        $view->assign('gm_e', gm_search_explain($gm_mc, $q, 20));
    }
} elseif ($mode === 'index') {
    $view->assign('gm_i', gm_stats_index($gm_mc));
    $view->assign('gm_flag', is_file($gm_root . '/var/gm_search/rebuild.flag'));
} else {
    return [CONTROLLER_STATUS_NO_PAGE];
}
