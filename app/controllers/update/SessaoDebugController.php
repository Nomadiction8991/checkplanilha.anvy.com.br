<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG DE SESSÃƒO ===\n\n";

echo "SessÃ£o Iniciada: " . (session_status() == PHP_SESSION_ACTIVE ? "SIM" : "NÃƒO") . "\n\n";

echo "--- Dados da SessÃ£o ---\n";
echo "usuario_id: " . (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'NÃƒO DEFINIDO') . "\n";
echo "usuario_nome: " . (isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'NÃƒO DEFINIDO') . "\n";
echo "usuario_email: " . (isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : 'NÃƒO DEFINIDO') . "\n";
echo "usuario_tipo: " . (isset($_SESSION['usuario_tipo']) ? $_SESSION['usuario_tipo'] : 'NÃƒO DEFINIDO') . "\n";

echo "\n--- VerificaÃ§Ã£o de AutenticaÃ§Ã£o ---\n";
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    echo "âœ… UsuÃ¡rio autenticado corretamente!\n";
    echo "âœ… ID vÃ¡lido: " . $_SESSION['usuario_id'] . "\n";
} else {
    echo "âŒ UsuÃ¡rio NÃƒO estÃ¡ autenticado!\n";
}

echo "\n--- Todas as chaves da sessÃ£o ---\n";
foreach ($_SESSION as $key => $value) {
    echo "$key => " . (is_array($value) ? 'Array' : $value) . "\n";
}

echo "\n=== FIM DEBUG ===\n";
?>


