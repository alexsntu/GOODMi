<?php
/**
 * GOODMi: расчёт сроков получения товара («дата доставки как на Ozon»).
 * Чистая логика без обращений к базе и к CS-Cart – чтобы её можно было проверять отдельно.
 * Правила продиктованы владельцем магазина 2026-10-07, полный текст – RULES.md рядом.
 *
 * Вход:
 *   $stock  – где товар есть в наличии:
 *             ['sev' => bool, 'sev_shop' => bool, 'simf' => bool, 'yalta' => bool]
 *             sev_shop = есть хотя бы в одном севастопольском МАГАЗИНЕ (не только на складе
 *             «Интернет-магазин»); если товар в Севастополе лежит только на складе
 *             «Интернет-магазин», порог самовывоза «сегодня» – 17:00 вместо 20:00.
 *   $client – где покупатель: 'sev' | 'simf' | 'yalta' | 'crimea' | 'ru1' | 'ru2' | 'ru3' | 'other'
 *   $now    – момент заказа (DateTimeInterface), время переводится в московское.
 *   $cfg    – настройки (см. gm_dd_default_config()).
 *
 * Выход: массив
 *   'preorder' => bool                 товара нет нигде
 *   'pickup'   => null | ['date' => 'Y-m-d', 'after' => 'HH:MM'|null]
 *   'courier'  => null | ['date' => 'Y-m-d', 'until' => 'HH:MM'|null]
 *   'cdek'     => null | ['from' => 'Y-m-d', 'to' => 'Y-m-d']
 */

function gm_dd_default_config()
{
    return [
        'tz' => 'Europe/Moscow',
        // Самовывоз «сегодня», если заказ строго раньше этого времени
        'pickup_cutoff' => ['sev' => '20:00', 'simf' => '20:00', 'yalta' => '18:30'],
        'pickup_cutoff_sev_internet_only' => '17:00',
        // Курьер: рабочие дни недели (1 = пн … 7 = вс) и пороги приёма заказа «на сегодня»
        'courier_days' => ['sev' => [1, 2, 3, 4, 5, 6], 'simf' => [1, 3, 4, 6]],
        'courier_cutoff_weekday' => '16:00',
        'courier_cutoff_saturday' => '15:00',
        'courier_until' => '18:00',
        'courier_holidays' => ['01-01', '01-02'],
        // Рейсы между городами: дни недели и пороги
        'run_days' => [2, 5],                 // вторник, пятница
        'run_holidays' => ['01-01', '01-02'], // рейс в праздник переносится на следующий день рейса
        'sev_to_simf_cutoff' => '14:00',      // заказ строго раньше 14:00 в день рейса
        'yalta_out_cutoff' => '15:00',        // товар из Ялты: заказ строго раньше 15:00 в день рейса
        'eve_cutoff' => '20:00',              // «до вечера накануне»: понедельник / четверг до 20:00
        'arrive_after' => '18:00',            // в Симферополе и Ялте товар с рейса можно забрать после 18:00
        // СДЭК: диапазоны в днях от базового дня (день заказа не считается)
        'cdek_days' => ['crimea' => [4, 5], 'ru1' => [7, 10], 'ru2' => [10, 14], 'ru3' => [14, 23]],
        'cdek_saturday_cutoff' => '14:00',    // суббота с 14:00 и воскресенье → отправка в понедельник
    ];
}

function gm_dd_at(DateTimeImmutable $day, $hhmm)
{
    list($h, $m) = array_map('intval', explode(':', $hhmm));
    return $day->setTime($h, $m, 0);
}

function gm_dd_is_holiday(DateTimeImmutable $day, array $list)
{
    return in_array($day->format('m-d'), $list, true);
}

/** Ближайший день рейса R (начиная с $from включительно), для которого $accept(R) истинно. */
function gm_dd_next_run(DateTimeImmutable $from, array $cfg, callable $accept)
{
    $d = $from->setTime(0, 0, 0);
    for ($i = 0; $i < 60; $i++) {
        if (in_array((int) $d->format('N'), $cfg['run_days'], true)
            && !gm_dd_is_holiday($d, $cfg['run_holidays'])
            && $accept($d)
        ) {
            return $d;
        }
        $d = $d->modify('+1 day');
    }
    return null;
}

/** Ближайший рабочий день курьера, начиная с $from включительно. */
function gm_dd_courier_day(DateTimeImmutable $from, array $days, array $cfg)
{
    $d = $from->setTime(0, 0, 0);
    for ($i = 0; $i < 30; $i++) {
        if (in_array((int) $d->format('N'), $days, true) && !gm_dd_is_holiday($d, $cfg['courier_holidays'])) {
            return $d;
        }
        $d = $d->modify('+1 day');
    }
    return null;
}

/**
 * Когда товар из города $src окажется доступен в городе $dst.
 * Возвращает ['date' => DateTimeImmutable, 'after' => 'HH:MM'|null, 'next_day_only' => bool]
 *   next_day_only – товар приехал рейсом; курьер в день приезда его не везёт (везёт на следующий день).
 * Для самовывоза в Севастополе товар с рейса тоже выдаётся только на следующий день – это уже учтено в date.
 */
function gm_dd_arrival($src, $dst, DateTimeImmutable $now, array $cfg, $sev_shop = true)
{
    $today = $now->setTime(0, 0, 0);

    if ($src === $dst) {
        $cut = $cfg['pickup_cutoff'][$dst];
        if ($dst === 'sev' && !$sev_shop) {
            $cut = $cfg['pickup_cutoff_sev_internet_only'];
        }
        $date = $now < gm_dd_at($today, $cut) ? $today : $today->modify('+1 day');
        return ['date' => $date, 'after' => null, 'transfer' => false];
    }

    // «Накануне до 20:00»: рейс R подходит, если заказ сделан раньше 20:00 предыдущего дня
    $eve = function (DateTimeImmutable $r) use ($now, $cfg) {
        return $now < gm_dd_at($r->modify('-1 day'), $cfg['eve_cutoff']);
    };
    // «В день рейса до HH:MM»
    $same = function ($hhmm) use ($now) {
        return function (DateTimeImmutable $r) use ($now, $hhmm) {
            return $now < gm_dd_at($r, $hhmm);
        };
    };

    $route = $src . '>' . $dst;
    switch ($route) {
        case 'sev>simf': // рейс вт/пт после 14:00, заказ до 14:00 в день рейса; забрать после 18:00
            $r = gm_dd_next_run($today, $cfg, $same($cfg['sev_to_simf_cutoff']));
            return ['date' => $r, 'after' => $cfg['arrive_after'], 'transfer' => true];

        case 'simf>sev': // заказ до 20:00 накануне; товар на складе утром, выдача на следующий день
            $r = gm_dd_next_run($today, $cfg, $eve);
            return ['date' => $r->modify('+1 day'), 'after' => null, 'transfer' => true, 'ready_for_courier' => true];

        case 'sev>yalta': // заказ до 20:00 накануне; рейс в 15:00; забрать после 18:00
        case 'simf>yalta': // Симферополь → Севастополь утром → Ялта в 15:00 тем же днём
            $r = gm_dd_next_run($today, $cfg, $eve);
            return ['date' => $r, 'after' => $cfg['arrive_after'], 'transfer' => true];

        case 'yalta>sev': // заказ до 15:00 в день рейса; в Севастополе к 18:00, выдача на следующий день
            $r = gm_dd_next_run($today, $cfg, $same($cfg['yalta_out_cutoff']));
            return ['date' => $r->modify('+1 day'), 'after' => null, 'transfer' => true, 'ready_for_courier' => true];

        case 'yalta>simf': // Ялта → Севастополь (к 18:00), затем СЛЕДУЮЩИМ рейсом в Симферополь
            $r1 = gm_dd_next_run($today, $cfg, $same($cfg['yalta_out_cutoff']));
            $r2 = gm_dd_next_run($r1->modify('+1 day'), $cfg, function () {
                return true;
            });
            return ['date' => $r2, 'after' => $cfg['arrive_after'], 'transfer' => true];
    }
    return null;
}

function gm_dd_estimate(array $stock, $client, DateTimeInterface $now, array $cfg = null)
{
    $cfg = $cfg ?: gm_dd_default_config();
    $now = (new DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone(new DateTimeZone($cfg['tz']));
    $today = $now->setTime(0, 0, 0);

    $has = [
        'sev' => !empty($stock['sev']),
        'simf' => !empty($stock['simf']),
        'yalta' => !empty($stock['yalta']),
    ];
    $sev_shop = !empty($stock['sev_shop']);
    $out = ['preorder' => false, 'pickup' => null, 'courier' => null, 'cdek' => null];

    if (!$has['sev'] && !$has['simf'] && !$has['yalta']) {
        $out['preorder'] = true;
        return $out;
    }

    // ---------- Клиент в одном из трёх наших городов: пункт выдачи (+ курьер в Севастополе и Симферополе)
    if (in_array($client, ['sev', 'simf', 'yalta'], true)) {
        // Правило И: если товар есть в городе клиента – другие города не рассматриваем; иначе берём самый быстрый
        $sources = $has[$client] ? [$client] : array_keys(array_filter($has));
        $best = null;
        foreach ($sources as $src) {
            $a = gm_dd_arrival($src, $client, $now, $cfg, $sev_shop);
            if ($a === null || $a['date'] === null) {
                continue;
            }
            $key = $a['date']->format('Ymd') . ($a['after'] ?: '00:00');
            if ($best === null || $key < $best['key']) {
                $best = $a + ['key' => $key, 'src' => $src];
            }
        }
        if ($best !== null) {
            $out['pickup'] = ['date' => $best['date']->format('Y-m-d'), 'after' => $best['after']];
        }

        if (isset($cfg['courier_days'][$client])) {
            $days = $cfg['courier_days'][$client];
            if ($has[$client]) {
                // Товар в городе клиента: сегодня, если сегодня рабочий день курьера и заказ раньше порога
                $is_sat = (int) $today->format('N') === 6;
                $cut = $is_sat ? $cfg['courier_cutoff_saturday'] : $cfg['courier_cutoff_weekday'];
                $works_today = gm_dd_courier_day($today, $days, $cfg) == $today;
                if ($works_today && $now < gm_dd_at($today, $cut)) {
                    $c = $today;
                } else {
                    $c = gm_dd_courier_day($today->modify('+1 day'), $days, $cfg);
                }
                $out['courier'] = ['date' => $c->format('Y-m-d'), 'until' => $c == $today ? $cfg['courier_until'] : null];
            } elseif ($best !== null) {
                // Товар приезжает рейсом: в день приезда курьер не везёт – на следующий день.
                // Для Севастополя дата самовывоза уже «следующий день после рейса», курьер – в тот же день.
                $from = !empty($best['ready_for_courier']) ? $best['date'] : $best['date']->modify('+1 day');
                $c = gm_dd_courier_day($from, $days, $cfg);
                $out['courier'] = ['date' => $c->format('Y-m-d'), 'until' => null];
            }
        }
        return $out;
    }

    // ---------- Остальные локации: только СДЭК (условия одинаковы для товара из любого города)
    if (isset($cfg['cdek_days'][$client])) {
        $base = $today;
        $dow = (int) $today->format('N');
        if ($dow === 7 || ($dow === 6 && $now >= gm_dd_at($today, $cfg['cdek_saturday_cutoff']))) {
            $base = $today->modify('next monday');
        }
        list($a, $b) = $cfg['cdek_days'][$client];
        $out['cdek'] = [
            'from' => $base->modify('+' . $a . ' day')->format('Y-m-d'),
            'to' => $base->modify('+' . $b . ' day')->format('Y-m-d'),
        ];
    }
    return $out;
}

/** «сегодня» / «завтра» / «13 октября» относительно $now (московское время). */
function gm_dd_months($short = false)
{
    return $short
        ? [1 => 'янв', 'фев', 'мар', 'апр', 'мая', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек']
        : [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
}

function gm_dd_human_date($ymd, DateTimeInterface $now, $tz = 'Europe/Moscow', $short = false)
{
    $months = gm_dd_months($short);
    $zone = new DateTimeZone($tz);
    $today = (new DateTimeImmutable('@' . $now->getTimestamp()))->setTimezone($zone)->setTime(0, 0, 0);
    $d = new DateTimeImmutable($ymd . ' 00:00:00', $zone);
    $diff = (int) $today->diff($d)->format('%r%a');
    if ($diff === 0) {
        return 'сегодня';
    }
    if ($diff === 1) {
        return 'завтра';
    }
    return (int) $d->format('j') . ' ' . $months[(int) $d->format('n')];
}

/** Диапазон дат для СДЭК: «11–12 октября» или «30 октября – 2 ноября». */
function gm_dd_human_range($from, $to, $tz = 'Europe/Moscow', $short = false)
{
    $months = gm_dd_months($short);
    $zone = new DateTimeZone($tz);
    $a = new DateTimeImmutable($from, $zone);
    $b = new DateTimeImmutable($to, $zone);
    if ($a->format('Y-m') === $b->format('Y-m')) {
        return (int) $a->format('j') . '–' . (int) $b->format('j') . ' ' . $months[(int) $b->format('n')];
    }
    return (int) $a->format('j') . ' ' . $months[(int) $a->format('n')] . ' – ' . (int) $b->format('j') . ' ' . $months[(int) $b->format('n')];
}
