<?php
require_once 'config.php';
require_once 'PlanilhaProcessor.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$codigo = $_POST['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Código não informado']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$processor = new PlanilhaProcessor($conn);

try {
    // Buscar produto pelo código
    $produto = $processor->buscarPorCodigo($codigo);
    
    if (!$produto) {
        echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
        exit;
    }
    
    // Marcar como checado
    $processor->marcarComoChecado($codigo);
    
    echo json_encode([
        'success' => true,
        'message' => 'Produto checado com sucesso',
        'produto' => [
            'codigo' => $produto['codigo'],
            'nome' => $produto['nome'] ?? 'N/A',
            'localidade' => $produto['localidade'] ?? 'N/A'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
?>