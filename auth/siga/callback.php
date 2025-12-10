<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once PROJECT_ROOT . '/app/functions/siga_client.php';
session_start();

$state = $_GET['state'] ?? '';
$expectedState = $_SESSION['siga_state'] ?? '';

if (!$expectedState || $state !== $expectedState) {
    http_response_code(400);
    echo "Callback invalido: token de estado nao confere.";
    exit;
}

// Marca sessao como autenticada via SIGA (sem capturar senha)
$_SESSION['siga_authenticated'] = true;
$_SESSION['siga_sync_pending'] = true;

// Dados basicos do usuario se o SIGA devolver algo na query (opcional)
$_SESSION['siga_usuario_login'] = $_GET['user'] ?? $_GET['login'] ?? ($_SESSION['siga_usuario_login'] ?? null);

// Usa fallback para tipo
$_SESSION['usuario_tipo'] = $_SESSION['usuario_tipo'] ?? 'SIGA';

// Limpa state para nao ser reutilizado
unset($_SESSION['siga_state']);

$destino = $_SESSION['redirect_after_login'] ?? base_url('index.php');
unset($_SESSION['redirect_after_login']);

// Se veio de popup, fecha janela e avisa opener
if (isset($_GET['popup']) && $_GET['popup'] === '1') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>SIGA autenticado</title>
    </head>
    <body>
        <script>
            try {
                if (window.opener) {
                    window.opener.postMessage({ sigaAuth: true, ok: true }, '*');
                }
            } catch (e) {}
            window.close();
        </script>
        <p>Login concluido. Esta janela pode ser fechada.</p>
    </body>
    </html>
    <?php
    exit;
}

// Redireciona no fluxo normal
header('Location: ' . $destino);
exit;
