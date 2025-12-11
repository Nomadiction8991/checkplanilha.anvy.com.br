<?php
/**
 * Bootstrap central para inicializar charset, sessao e utilidades comuns.
 * Garantimos UTF-8 de ponta a ponta para evitar problemas de acentuacao.
 */

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__));
    }

    // Forcar UTF-8 em todas as saídas
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    ini_set('default_charset', 'UTF-8');
    if (function_exists('mb_internal_encoding')) {
        mb_internal_encoding('UTF-8');
    }
    if (function_exists('mb_http_output')) {
        mb_http_output('UTF-8');
    }

    // Timezone padrao da aplicacao (Cuiaba - UTC-4)
    date_default_timezone_set('America/Cuiaba');

    // Sessoes mais seguras
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'cookie_samesite' => 'Lax',
        ]);
    }

    // Log em arquivo local (cria pasta se necessario)
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . '/app.log');

    /**
     * Detecta se a requisicao foi feita via AJAX/Fetch.
     */
    function is_ajax_request(): bool
    {
        $byHeader = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $byAccept = isset($_SERVER['HTTP_ACCEPT']) && stripos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        return $byHeader || $byAccept;
    }

    /**
     * Envia resposta JSON padronizada e encerra a execucao.
     *
     * @param array $payload Dados a serem serializados
     * @param int $statusCode Codigo HTTP
     */
    function json_response(array $payload, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            header_remove('Location');
            header('Content-Type: application/json; charset=UTF-8');
        }
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Sanitiza textos simples vindos do cliente.
     */
    function sanitize_text($value): string
    {
        return trim((string) $value);
    }
}

