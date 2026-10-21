<?php
/**
 * GOODMi: API маркетинг-портала для облачных routines (2026-10-21) – вместо https://price.maxmobiles.ru/api/...
 *   https://goodmi.ru/mkt-api/<путь> → index.php?dispatch=gm_marketing.api&gm_path=<путь> (правило nginx gm-mkt-api.conf)
 *   Пути, тела и ответы – как у портала: state/:key, drafts, topics, topics/:id/generated, tasks/due, tasks/:id/reminded, plan,
 *   promo-ideas, repost-suggestions (см. fn_gm_mkt_api). Доступ – заголовок «Authorization: Bearer <токен>», токен –
 *   api_token в private/gm_marketing.json. Только витрина GOODMi (company_id 2); без верного токена – 401.
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode === 'api') {
    $send = static function ($code, array $payload) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    };

    if ((int) fn_get_runtime_company_id() !== 2) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    $token = stripos($header, 'Bearer ') === 0 ? trim(substr($header, 7)) : '';
    $expected = fn_gm_mkt_config('api_token');
    if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
        $send(401, ['error' => 'unauthorized']);
    }

    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $body = [];
    if ($method === 'POST') {
        $raw = (string) file_get_contents('php://input');
        $body = $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($body)) {
            $send(400, ['error' => 'invalid JSON']);
        }
    }
    $query = $_GET;
    unset($query['dispatch'], $query['gm_path']);

    [$code, $payload] = fn_gm_mkt_api($method, (string) ($_REQUEST['gm_path'] ?? ''), $query, $body);
    $send($code, $payload);
}

return [CONTROLLER_STATUS_NO_PAGE];
