<?php
require_once __DIR__ . '/../bootstrap.php';
require_once PROJECT_ROOT . '/app/functions/siga_client.php';
session_start();

$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
$allowedOrigin = $origin ?: ((isset($_SERVER['HTTP_HOST']) ? ( ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST']) : '*'));
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
if ($allowedOrigin !== '*') {
    header('Access-Control-Allow-Credentials: true');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type,Authorization');
    exit;
}

try {
    $cookieHeader = $_SERVER['HTTP_COOKIE'] ?? '';
    if (trim($cookieHeader) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Nenhum cookie do SIGA foi enviado. Faça login no SIGA e tente novamente.']);
        exit;
    }

    $target = $_GET['target'] ?? $_POST['target'] ?? '';
    if (!$target) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetro target é obrigatório.']);
        exit;
    }

    // Converte caminho relativo em URL completa do SIGA
    if (strpos($target, 'http://') !== 0 && strpos($target, 'https://') !== 0) {
        $target = rtrim(SIGA_BASE_URL, '/') . '/' . ltrim($target, '/');
    }

    $parsed = parse_url($target);
    if (empty($parsed['host']) || stripos($parsed['host'], 'siga.congregacao.org.br') === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Host de destino não permitido. Use apenas siga.congregacao.org.br']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $payload = null;
    if ($method !== 'GET' && $method !== 'HEAD') {
        $payload = file_get_contents('php://input');
    }

    $result = siga_proxy_request($target, $method, $payload, [], $cookieHeader);

    if (isset($result['headers']['Content-Type'])) {
        header('Content-Type: ' . $result['headers']['Content-Type']);
    } else {
        header('Content-Type: text/html; charset=utf-8');
    }
    http_response_code($result['status_code']);
    echo $result['body'];
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao usar proxy do SIGA', 'message' => $e->getMessage()]);
}
