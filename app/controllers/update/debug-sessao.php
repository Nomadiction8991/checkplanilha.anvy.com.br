<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG DE SESSÃO ===\n\n";

echo "Sessão Iniciada: " . (session_status() == PHP_SESSION_ACTIVE ? "SIM" : "NÃO") . "\n\n";

echo "--- Dados da Sessão ---\n";
echo "usuario_id: " . (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 'NÃO DEFINIDO') . "\n";
echo "usuario_nome: " . (isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'NÃO DEFINIDO') . "\n";
echo "usuario_email: " . (isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : 'NÃO DEFINIDO') . "\n";
echo "usuario_tipo: " . (isset($_SESSION['usuario_tipo']) ? $_SESSION['usuario_tipo'] : 'NÃO DEFINIDO') . "\n";

echo "\n--- Verificação de Autenticação ---\n";
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0) {
    echo "✅ Usuário autenticado corretamente!\n";
    echo "✅ ID válido: " . $_SESSION['usuario_id'] . "\n";
} else {
    echo "❌ Usuário NÃO está autenticado!\n";
}

echo "\n--- Todas as chaves da sessão ---\n";
foreach ($_SESSION as $key => $value) {
    echo "$key => " . (is_array($value) ? 'Array' : $value) . "\n";
}

echo "\n=== FIM DEBUG ===\n";
?>

