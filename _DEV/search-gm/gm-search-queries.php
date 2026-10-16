<?php
/**
 * GOODMi: частые запросы покупателей для «Часто ищут» и словаря подсказок (2026-10-16). Пока поиск на GOODMi у Diginetica,
 * запросы покупателей почти не попадают в журнал сервера (окно Diginetica ищет само) – поэтому два источника:
 * 1) популярные запросы Diginetica: её автодополнение с открытым ключом сайта (тот же, что в cdn.diginetica.net/10125/client.js,
 *    лежит в dg.key) по каждой букве и цифре; место в списке → вес (первое – 10, десятое – 1), веса по буквам складываются;
 * 2) запросы со страницы поиска в журналах nginx за 10 дней (q / hint_q / term) – по числу разных адресов (боты не раздувают).
 * Результат – queries.txt рядом со скриптом: «частота<TAB>запрос», по убыванию. Только чтение; запуск раз в сутки.
 * После переключения GOODMi на свой поиск источник 1 заменит журнал своего поиска (cscart_gm_search_log).
 */
error_reporting(E_ERROR);
$key = trim((string) @file_get_contents(__DIR__ . '/dg.key'));
$freq = [];
$add = function ($q, $w) use (&$freq) {
    $q = trim(preg_replace('/\s+/u', ' ', mb_strtolower(str_replace('ё', 'е', (string) $q), 'UTF-8')));
    if (mb_strlen($q) < 2 || mb_strlen($q) > 60 || !preg_match('/\p{L}/u', $q)) { return; }
    $freq[$q] = (isset($freq[$q]) ? $freq[$q] : 0) + $w;
};

// 1) Diginetica
$dg = 0;
if ($key !== '') {
    $prefixes = array_merge(preg_split('//u', 'абвгдежзиклмнопрстуфхцчшэюя', -1, PREG_SPLIT_NO_EMPTY), str_split('abcdefghijklmnopqrstuvwxyz0123456789'), ['']);
    foreach ($prefixes as $st) {
        $url = 'https://autocomplete.diginetica.net/autocomplete?st=' . rawurlencode($st) . '&apiKey=' . rawurlencode($key)
            . '&strategy=advanced_xname,zero_queries&fullData=true&withCategories=false&withProducts=false&withSku=false&regionId=global&forIs=true&showUnavailable=true';
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $d = json_decode((string) curl_exec($ch), true);
        curl_close($ch);
        // Баллы по букве – только запросу, который с неё начинается (Diginetica ищет по началу любого слова – иначе длинные
        // запросы вроде «умный ночник mijia night light 3» набирали баллы почти по каждой букве); общий список (без буквы) – втрое
        foreach (array_values((array) (isset($d['sts']) ? $d['sts'] : [])) as $i => $x) {
            if (!isset($x['st'])) { continue; }
            $low = mb_strtolower((string) $x['st'], 'UTF-8');
            if ($st === '') { $add($low, 3 * max(1, 10 - $i)); $dg++; }
            elseif (mb_strpos($low, $st) === 0) { $add($low, max(1, 10 - $i)); $dg++; }
        }
        usleep(200000);
    }
}

// 2) Журналы nginx за 10 дней
$ips = [];
foreach (glob('/var/www/maxmobiles/data/logs/goodmi.ru.access.log*') as $f) {
    $h = substr($f, -3) === '.gz' ? gzopen($f, 'r') : fopen($f, 'r');
    if (!$h) { continue; }
    while (($l = (substr($f, -3) === '.gz' ? gzgets($h) : fgets($h))) !== false) {
        if (strpos($l, 'search_performed=Y') === false && strpos($l, 'digiSearch=true') === false) { continue; }
        if (preg_match('/bot|spider|crawl|NCDN/i', $l) || !preg_match('/"GET (\S+)/', $l, $m)) { continue; }
        parse_str((string) parse_url($m[1], PHP_URL_QUERY), $qs);
        $q = '';
        foreach (['q', 'hint_q', 'term'] as $k) { if (!empty($qs[$k]) && is_string($qs[$k])) { $q = $qs[$k]; break; } }
        if ($q === '') { continue; }
        $ips[mb_strtolower(trim($q), 'UTF-8')][strtok($l, ' ')] = 1;
    }
    substr($f, -3) === '.gz' ? gzclose($h) : fclose($h);
}
foreach ($ips as $q => $set) { $add($q, 3 * count($set)); }

arsort($freq);
$out = '';
foreach ($freq as $q => $n) { $out .= $n . "\t" . $q . "\n"; }
file_put_contents(__DIR__ . '/queries.txt.tmp', $out);
rename(__DIR__ . '/queries.txt.tmp', __DIR__ . '/queries.txt');
printf("Готово: запросов %d (из Diginetica вариантов %d, из журналов %d)\n", count($freq), $dg, count($ips));
