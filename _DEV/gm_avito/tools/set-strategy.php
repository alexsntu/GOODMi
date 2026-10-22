<?php
/**
 * Запись стратегии продвижения витрины в модуль Авито (настройка strategy, блок «Стратегия продвижения» на «Обзоре»,
 * 2026-10-22). Только CLI, из корня сайта:
 *   php set-strategy.php <company_id> <strategy.json> [<cpx-advice.tsv> [<сколько кандидатов, по умолчанию 40> [<дневной лимит, ₽>]]]
 * strategy.json – {status, summary, inputs[], rules[], plan[], updated, review}; из cpx-advice.tsv (вывод cpx-advice.py)
 * берутся объявления со ставкой выше минимума – в advice с дневным лимитом: заданным или max(50 ₽, ставка × 15).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$company_id = (int) ($argv[1] ?? 0);
$strategy = json_decode((string) file_get_contents($argv[2] ?? ''), true);
if (!$company_id || !is_array($strategy)) {
    exit("Нужно: company_id, strategy.json [cpx-advice.tsv [N]]\n");
}
if (!empty($argv[3])) {
    $top = (int) ($argv[4] ?? 40);
    $fixed_limit = (int) ($argv[5] ?? 0);
    $fh = fopen($argv[3], 'r');
    $head = fgetcsv($fh, 0, "\t");
    $advice = [];
    while (($line = fgetcsv($fh, 0, "\t")) !== false && count($advice) < $top) {
        $r = array_combine($head, $line);
        if ($r['advice_bid'] === '' || (float) $r['advice_bid'] <= (float) $r['min_bid']) {
            continue;
        }
        $bid = (float) $r['advice_bid'];
        $advice[] = [
            'avito_id' => $r['avito_id'],
            'product_id' => (int) $r['product_id'],
            'title' => $r['title'],
            'price' => number_format((float) $r['price'], 0, ',', ' '),
            'view_value' => number_format((float) $r['view_value'], 1, ',', ' '),
            'bid' => number_format($bid, 0, ',', ' '),
            'compare' => $r['advice_compare'],
            'rec_bid' => number_format((float) $r['rec_bid'], 0, ',', ' '),
            'limit' => number_format($fixed_limit ?: max(50, ceil($bid * 15)), 0, ',', ' '),
        ];
    }
    fclose($fh);
    $strategy['advice'] = $advice;
    $strategy['advice_date'] = date('d.m.Y', filemtime($argv[3]));
}
fn_gm_avito_set_setting($company_id, 'strategy', fn_gm_avito_db_text(json_encode($strategy, JSON_UNESCAPED_UNICODE)));
echo 'витрина ', $company_id, ': стратегия записана, кандидатов ', count($strategy['advice'] ?? []), PHP_EOL;
