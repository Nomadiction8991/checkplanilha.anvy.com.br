<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once PROJECT_ROOT . '/CRUD/conexao.php';
require_once PROJECT_ROOT . '/app/functions/siga_client.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

$cookieHeader = $_SERVER['HTTP_COOKIE'] ?? '';
if (trim($cookieHeader) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Nenhum cookie enviado. O proxy precisa dos cookies reais do SIGA.']);
    exit;
}

$possiblePaths = [
    '/usuario/preferencias',
    '/Usuario/Preferencias',
    '/account/preferences',
    '/Account/Preferences',
    '/preferencias',
    '/Preferencias',
];

$lastError = null;
$response = null;
foreach ($possiblePaths as $path) {
    $target = rtrim(SIGA_BASE_URL, '/') . $path;
    try {
        $response = siga_proxy_request($target, 'GET', null, [], $cookieHeader);
        if ($response['status_code'] >= 200 && $response['status_code'] < 400 && !empty($response['body'])) {
            break;
        }
    } catch (Throwable $e) {
        $lastError = $e->getMessage();
        $response = null;
    }
}

if (!$response || empty($response['body'])) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Não foi possível carregar a página de preferências do SIGA.',
        'detail' => $lastError,
    ]);
    exit;
}

$data = siga_parse_preferencias_html($response['body']);

// Garantia de login preenchido
if (empty($data['siga_login'])) {
    $data['siga_login'] = $_SESSION['siga_usuario_login'] ?? ('siga_' . substr(session_id(), 0, 8));
}

try {
    // Se chegou aqui com cookies válidos, considere autenticado via SIGA
    $_SESSION['siga_authenticated'] = true;
    $_SESSION['siga_sync_pending'] = false;

    siga_ensure_table($conexao);
    $sigaId = siga_upsert_usuario($conexao, $data);
    $localUserId = siga_sync_local_usuario($conexao, $data);

    // Atualiza sessão interna
    $_SESSION['usuario_id'] = $localUserId;
    $_SESSION['usuario_nome'] = $data['nome'] ?? $data['siga_login'];
    $_SESSION['usuario_email'] = $data['email'] ?? ($data['siga_login'] . '@siga.local');
    $_SESSION['usuario_tipo'] = 'SIGA';
    $_SESSION['siga_sync_pending'] = false;
    $_SESSION['siga_usuario_id'] = $sigaId;

    echo json_encode([
        'ok' => true,
        'siga_usuario_id' => $sigaId,
        'usuario_id' => $localUserId,
        'dados' => $data,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha ao salvar dados do SIGA', 'detail' => $e->getMessage()]);
}
