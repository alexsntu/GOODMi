<?php
/**
 * Все витрины: страница админки «Покупатели → СМС: работа и баланс» (2026-10-18) – баланс Таргет СМС и прогноз, путь покупателя
 * по кодам подтверждения GOODMi и Maxmobiles (запросил → отправлено → доставлено → ввёл), все СМС кабинета оператора по видам
 * с расходом, отправители и операторы связи, ошибки, по дням.
 * heaven.php?dispatch=sms_stats.manage[&days=0|7|30|90]. Расчёты – app/sms_stats/stats.php, шаблон – views/sms_stats/manage.tpl,
 * данные оператора раз в час собирает /root/sms-stats/sms-stats-collect.php (cron :05).
 */

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

require_once Registry::get('config.dir.root') . '/app/sms_stats/stats.php';

if ($mode === 'manage') {
    $days = isset($_REQUEST['days']) ? (int) $_REQUEST['days'] : 7;
    if (!in_array($days, [0, 7, 30, 90], true)) {
        $days = 7;
    }
    Tygh::$app['view']->assign('sms', sms_stats_overview($days));
    Tygh::$app['view']->assign('sms_health', sms_stats_health());
    Tygh::$app['view']->assign('sms_days', $days);
}
