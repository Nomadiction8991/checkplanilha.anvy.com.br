<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once PROJECT_ROOT . '/app/functions/siga_client.php';
session_start();

// Gera token de estado para validar retorno
$_SESSION['siga_state'] = bin2hex(random_bytes(16));

$callbackUrl = base_url('auth/siga/callback.php?state=' . urlencode($_SESSION['siga_state']));
$loginUrl = siga_build_login_url($callbackUrl);

// Permite propagar rota de retorno interna (dashboard)
if (!empty($_GET['redirect_to'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect_to'];
}

header('Location: ' . $loginUrl);
exit;
