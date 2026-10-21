<?php
/**
 * GOODMi: страницы «Маркетинг → Маркетинг-портал» в админке (2026-10-21), повторяют разделы портала на VPS:
 *   gm_marketing.drafts      – требует внимания, сбор данных (routines и статистика по источникам), черновики постов
 *   gm_marketing.topics      – темы статей блога на одобрение
 *   gm_marketing.tasks       – задачи с напоминанием в Telegram
 *   gm_marketing.plan        – маркетинговый план (список и календарь), plan_edit – правка мероприятия
 *   gm_marketing.promo_ideas – идеи акций на одобрение
 *   gm_marketing.reposts     – предложения постов для репоста
 *   gm_marketing.bonuses     – массовое списание бонусов БонусПлюс: партии, bonus_batch – партия, bonus_log – журнал
 *   gm_marketing.help        – справка
 *   POST: set_status (черновики, темы, идеи, репосты, задачи, план), add_task, delete_task, add_plan, update_plan, delete_plan,
 *   bonus_preview, bonus_execute, bonus_followup
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

$view = Tygh::$app['view'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $back = static function ($default) {
        $url = (string) ($_REQUEST['redirect_url'] ?? '');

        return strpos($url, 'gm_marketing.') !== false ? $url : $default;
    };

    if ($mode === 'set_status') {
        $types = [
            'draft' => ['gm_mkt_drafts', ['new', 'posted', 'dismissed'], 'gm_marketing.drafts'],
            'topic' => ['gm_mkt_topics', ['pending', 'approved', 'rejected'], 'gm_marketing.topics'],
            'promo_idea' => ['gm_mkt_promo_ideas', ['pending', 'approved', 'rejected'], 'gm_marketing.promo_ideas'],
            'repost' => ['gm_mkt_reposts', ['pending', 'approved', 'rejected'], 'gm_marketing.reposts'],
            'task' => ['gm_mkt_tasks', ['pending', 'done'], 'gm_marketing.tasks'],
            'plan' => ['gm_mkt_plan', array_keys(GM_MKT_PLAN_STATUSES), 'gm_marketing.plan'],
        ];
        $type = (string) ($_REQUEST['type'] ?? '');
        $status = (string) ($_REQUEST['status'] ?? '');
        if (isset($types[$type]) && in_array($status, $types[$type][1], true)) {
            fn_gm_mkt_set_status($types[$type][0], (int) ($_REQUEST['id'] ?? 0), $status);
        }

        return [CONTROLLER_STATUS_OK, $back($types[$type][2] ?? 'gm_marketing.drafts')];
    }

    if ($mode === 'add_task') {
        $task = (array) ($_REQUEST['task'] ?? []);
        if (trim((string) ($task['title'] ?? '')) !== '') {
            fn_gm_mkt_create_task([
                'title' => trim((string) $task['title']),
                'description' => trim((string) ($task['description'] ?? '')),
                'due_at' => fn_gm_mkt_msk_to_utc($task['due_at'] ?? ''),
                'remind_at' => fn_gm_mkt_msk_to_utc($task['remind_at'] ?? ''),
            ]);
            fn_set_notification('N', __('notice'), 'Задача добавлена.');
        }

        return [CONTROLLER_STATUS_OK, 'gm_marketing.tasks'];
    }

    if ($mode === 'delete_task') {
        db_query('DELETE FROM ?:gm_mkt_tasks WHERE id = ?i', (int) ($_REQUEST['id'] ?? 0));

        return [CONTROLLER_STATUS_OK, $back('gm_marketing.tasks')];
    }

    if ($mode === 'add_plan' || $mode === 'update_plan') {
        $data = fn_gm_mkt_plan_data((array) ($_REQUEST['plan'] ?? []));
        if ($data['title'] !== '') {
            if ($mode === 'add_plan') {
                $data['status'] = 'planned';
                $data['created_at'] = fn_gm_mkt_now();
                db_query('INSERT INTO ?:gm_mkt_plan ?e', $data);
                fn_set_notification('N', __('notice'), 'Мероприятие добавлено в план.');
            } else {
                db_query('UPDATE ?:gm_mkt_plan SET ?u WHERE id = ?i', $data, (int) ($_REQUEST['id'] ?? 0));
                fn_set_notification('N', __('notice'), 'Мероприятие сохранено.');
            }
        }

        return [CONTROLLER_STATUS_OK, 'gm_marketing.plan'];
    }

    if ($mode === 'delete_plan') {
        db_query('DELETE FROM ?:gm_mkt_plan WHERE id = ?i', (int) ($_REQUEST['id'] ?? 0));

        return [CONTROLLER_STATUS_OK, $back('gm_marketing.plan')];
    }

    if ($mode === 'bonus_preview') {
        if (!fn_gm_mkt_bp_configured()) {
            fn_set_notification('E', __('error'), 'Не задан ключ API БонусПлюс (private/gm_marketing.json).');

            return [CONTROLLER_STATUS_OK, 'gm_marketing.bonuses'];
        }
        $batch_id = fn_gm_mkt_bonus_preview((string) ($_REQUEST['list'] ?? ''));
        if (!$batch_id) {
            fn_set_notification('E', __('error'), 'Список пуст или не распознан – формат: телефон и сумма через пробел, по одному на строку.');

            return [CONTROLLER_STATUS_OK, 'gm_marketing.bonuses'];
        }

        return [CONTROLLER_STATUS_OK, 'gm_marketing.bonus_batch?batch_id=' . $batch_id];
    }

    if ($mode === 'bonus_execute' || $mode === 'bonus_followup') {
        $batch_id = (int) ($_REQUEST['batch_id'] ?? 0);
        $status = (string) db_get_field('SELECT status FROM ?:gm_mkt_bonus_batches WHERE id = ?i', $batch_id);
        if ($mode === 'bonus_execute' && $status === 'preview') {
            fn_gm_mkt_bonus_execute($batch_id);
        } elseif ($mode === 'bonus_followup' && $status === 'executed') {
            fn_gm_mkt_bonus_followup($batch_id);
        }

        return [CONTROLLER_STATUS_OK, 'gm_marketing.bonus_batch?batch_id=' . $batch_id];
    }

    return [CONTROLLER_STATUS_OK, 'gm_marketing.drafts'];
}

$counts = fn_gm_mkt_nav_counts();
$active = ['plan_edit' => 'plan', 'bonus_batch' => 'bonuses', 'bonus_log' => 'bonuses'][$mode] ?? $mode;
$tabs = [];
foreach ([
    'drafts' => ['Черновики', $counts['drafts']],
    'topics' => ['Темы статей', $counts['topics']],
    'tasks' => ['Задачи', 0],
    'plan' => ['План', 0],
    'promo_ideas' => ['Идеи акций', $counts['promo_ideas']],
    'reposts' => ['Предложение постов', $counts['reposts']],
    'bonuses' => ['Бонусы', $counts['bonuses']],
    'help' => ['Справка', 0],
] as $tab => [$title, $count]) {
    $tabs[$tab] = ['title' => $title, 'count' => $count, 'active' => $tab === $active];
}
$view->assign(['gm_mkt_counts' => $counts, 'gm_mkt_tabs' => $tabs, 'gm_mkt_url' => Registry::get('config.current_url')]);

if ($mode === 'drafts') {
    $status = (string) ($_REQUEST['status'] ?? 'new');
    $source = (string) ($_REQUEST['source'] ?? '');
    $drafts = fn_gm_mkt_rows(fn_gm_mkt_list('gm_mkt_drafts', [
        'status' => $status === 'all' ? null : $status,
        'source' => isset(GM_MKT_SOURCES[$source]) ? $source : null,
    ]));
    foreach ($drafts as &$d) {
        $d['created_msk'] = fn_gm_mkt_msk($d['created_at']);
    }
    unset($d);
    $view->assign([
        'gm_mkt_drafts' => $drafts,
        'gm_mkt_status' => $status,
        'gm_mkt_source' => $source,
        'gm_mkt_sources' => GM_MKT_SOURCES,
        'gm_mkt_source_stats' => fn_gm_mkt_draft_stats(),
        'gm_mkt_topic_stats' => fn_gm_mkt_topic_stats(),
        'gm_mkt_routines' => GM_MKT_ROUTINES,
    ]);
}

if ($mode === 'topics' || $mode === 'promo_ideas' || $mode === 'reposts') {
    $table = ['topics' => 'gm_mkt_topics', 'promo_ideas' => 'gm_mkt_promo_ideas', 'reposts' => 'gm_mkt_reposts'][$mode];
    $status = (string) ($_REQUEST['status'] ?? 'pending');
    $rows = fn_gm_mkt_rows(fn_gm_mkt_list($table, ['status' => $status === 'all' ? null : $status]));
    foreach ($rows as &$r) {
        $r['created_msk'] = fn_gm_mkt_msk($r['created_at']);
    }
    unset($r);
    $view->assign(['gm_mkt_rows' => $rows, 'gm_mkt_status' => $status]);
}

if ($mode === 'tasks') {
    $status = (string) ($_REQUEST['status'] ?? 'pending');
    $tasks = fn_gm_mkt_rows(fn_gm_mkt_list_tasks($status === 'all' ? null : $status), ['id', 'reminded']);
    foreach ($tasks as &$t) {
        $t['due_msk'] = fn_gm_mkt_msk($t['due_at']);
        $t['remind_msk'] = fn_gm_mkt_msk($t['remind_at']);
    }
    unset($t);
    $view->assign(['gm_mkt_tasks' => $tasks, 'gm_mkt_status' => $status]);
}

if ($mode === 'plan') {
    $status = (string) ($_REQUEST['status'] ?? 'planned');
    $plan_view = ($_REQUEST['view'] ?? '') === 'calendar' ? 'calendar' : 'list';
    $items = fn_gm_mkt_rows(fn_gm_mkt_list_plan($status === 'all' ? null : $status));
    foreach ($items as &$it) {
        $it['color'] = $it['id'] % 12;
    }
    unset($it);
    $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_REQUEST['month'] ?? '')) ? (string) $_REQUEST['month'] : date('Y-m');
    $ts = strtotime($month . '-01');
    $view->assign([
        'gm_mkt_plan' => $items,
        'gm_mkt_status' => $status,
        'gm_mkt_view' => $plan_view,
        'gm_mkt_month' => $month,
        'gm_mkt_month_title' => ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'][(int) date('n', $ts) - 1] . ' ' . date('Y', $ts),
        'gm_mkt_prev_month' => date('Y-m', strtotime('-1 month', $ts)),
        'gm_mkt_next_month' => date('Y-m', strtotime('+1 month', $ts)),
        'gm_mkt_weeks' => $plan_view === 'calendar' ? fn_gm_mkt_calendar($month, $items) : [],
        'gm_mkt_plan_types' => GM_MKT_PLAN_TYPES,
        'gm_mkt_plan_statuses' => GM_MKT_PLAN_STATUSES,
    ]);
}

if ($mode === 'plan_edit') {
    $item = db_get_row('SELECT * FROM ?:gm_mkt_plan WHERE id = ?i', (int) ($_REQUEST['id'] ?? 0));
    if (!$item) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    $view->assign([
        'gm_mkt_item' => fn_gm_mkt_rows([$item])[0],
        'gm_mkt_plan_types' => GM_MKT_PLAN_TYPES,
    ]);
}

if ($mode === 'bonuses') {
    $batches = db_get_array(
        'SELECT b.*, (SELECT COUNT(*) FROM ?:gm_mkt_bonus_items i WHERE i.batch_id = b.id) AS items_count,'
        . ' (SELECT COALESCE(SUM(requested_deduct), 0) FROM ?:gm_mkt_bonus_items i WHERE i.batch_id = b.id) AS total_requested,'
        . ' (SELECT COALESCE(SUM(deducted_step1), 0) + COALESCE(SUM(deducted_step2), 0) FROM ?:gm_mkt_bonus_items i WHERE i.batch_id = b.id) AS total_deducted'
        . ' FROM ?:gm_mkt_bonus_batches b ORDER BY b.created_at DESC, b.id DESC'
    );
    foreach ($batches as &$b) {
        $b['created_msk'] = fn_gm_mkt_msk($b['created_at']);
        $b['total_requested_r'] = (int) round((float) $b['total_requested']);
        $b['total_deducted_r'] = (int) round((float) $b['total_deducted']);
    }
    unset($b);
    $view->assign(['gm_mkt_batches' => $batches, 'gm_mkt_bp_configured' => fn_gm_mkt_bp_configured()]);
}

if ($mode === 'bonus_batch') {
    $data = fn_gm_mkt_bonus_batch_view((int) ($_REQUEST['batch_id'] ?? 0));
    if (!$data) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    $view->assign('gm_mkt_bb', $data);
}

if ($mode === 'bonus_log') {
    $phone = trim((string) ($_REQUEST['phone'] ?? ''));
    $batch_id = (int) ($_REQUEST['batch_id'] ?? 0);
    $where = '1';
    if ($phone !== '') {
        $where .= db_quote(' AND l.phone LIKE ?l', '%' . preg_replace('/\D/', '', $phone) . '%');
    }
    if ($batch_id) {
        $where .= db_quote(' AND l.batch_id = ?i', $batch_id);
    }
    $entries = fn_gm_mkt_rows(db_get_array('SELECT l.* FROM ?:gm_mkt_bonus_log l WHERE ' . $where . ' ORDER BY l.created_at DESC, l.id DESC LIMIT 500'), ['id', 'batch_id']);
    foreach ($entries as &$e) {
        $e['created_msk'] = fn_gm_mkt_msk($e['created_at']);
        $e['amount_r'] = $e['amount'] !== null ? (string) (int) round((float) $e['amount']) : '';
    }
    unset($e);
    $view->assign(['gm_mkt_entries' => $entries, 'gm_mkt_phone' => $phone, 'gm_mkt_batch_id' => $batch_id]);
}
