<?php
/**
 * Точечная правка полей объявлений по требованиям Авито (2026-10-19). Только CLI, из корня сайта:
 *   php fix-ad-fields.php <company_id> <spec.json>
 * spec – [{"product_ids": [...], "remove": ["GoodsType", …], "set": {"Category": "…", …}}]: у каждого товара из списка
 * убираются поля remove и записываются set (остальные поля объявления не трогаются). Печатает было → стало.
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
$_SERVER['REQUEST_METHOD'] = 'GET';
require getcwd() . '/init.php';

$company_id = (int) ($argv[1] ?? 0);
$spec = json_decode((string) file_get_contents($argv[2] ?? ''), true);
if (!$company_id || !is_array($spec)) {
    exit("Нужно: company_id, spec.json\n");
}

foreach ($spec as $rule) {
    foreach ((array) $rule['product_ids'] as $product_id) {
        $item = fn_gm_avito_get_item($company_id, (int) $product_id);
        if (!$item) {
            echo 'нет строки товара ', $product_id, PHP_EOL;
            continue;
        }
        $fields = (array) json_decode((string) $item['fields'], true);
        $before = implode(' / ', array_filter([$fields['Category'] ?? '', $fields['GoodsType'] ?? '', $fields['ProductType'] ?? '', $fields['GoodsSubType'] ?? '']));
        foreach ((array) ($rule['remove'] ?? []) as $tag) {
            unset($fields[$tag]);
        }
        foreach ((array) ($rule['set'] ?? []) as $tag => $value) {
            $fields[$tag] = fn_gm_avito_db_text($value);
        }
        db_query(
            'UPDATE ?:gm_avito_items SET fields = ?s, updated_at = ?i WHERE company_id = ?i AND product_id = ?i',
            json_encode($fields, JSON_UNESCAPED_UNICODE),
            TIME,
            $company_id,
            $product_id
        );
        echo $product_id, ': ', $before, ' → ', implode(' / ', array_filter([$fields['Category'] ?? '', $fields['GoodsType'] ?? '', $fields['ProductType'] ?? '', $fields['GoodsSubType'] ?? '', $fields['ToolType'] ?? '', $fields['CarpenterSubType'] ?? ''])), PHP_EOL;
    }
}
