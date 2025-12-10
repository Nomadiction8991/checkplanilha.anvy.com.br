<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once PROJECT_ROOT . '/app/functions/siga_client.php';
session_start();

$isPopup = isset($_GET['popup']) && $_GET['popup'] === '1';

// Gera token de estado para validar retorno
$_SESSION['siga_state'] = bin2hex(random_bytes(16));

// Monta callback preservando prefixo atual (dev/prod)
$callbackUrl = base_url('auth/siga/callback.php?state=' . urlencode($_SESSION['siga_state']) . ($isPopup ? '&popup=1' : ''));

try {
    $loginUrl = siga_build_login_url($callbackUrl);
} catch (Throwable $e) {
    // Fallback seguro: leva ao host base do SIGA (usuário completa manualmente)
    $loginUrl = rtrim(SIGA_BASE_URL, '/');
}

// Permite propagar rota de retorno interna (dashboard)
if (!empty($_GET['redirect_to'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect_to'];
}

if ($isPopup) {
    // Renderiza página simples que apenas redireciona para o SIGA (dentro do popup)
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Login SIGA</title>
        <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($loginUrl, ENT_QUOTES); ?>">
    </head>
    <body>
        <p>Redirecionando para o SIGA...</p>
        <script>location.replace(<?php echo json_encode($loginUrl); ?>);</script>
    </body>
    </html>
    <?php
    exit;
}

header('Location: ' . $loginUrl);
exit;
