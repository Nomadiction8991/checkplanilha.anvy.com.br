<?php
require_once 'config.php';

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

try {
    // Buscar produto pelo código
    $query = "SELECT * FROM planilha WHERE codigo = :codigo";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':codigo', $codigo);
    $stmt->execute();
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produto) {
        echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
        exit;
    }
    
    // Marcar como checado
    $updateQuery = "UPDATE planilha SET checado = 1, data_checagem = NOW(), usuario_checagem = 'Sistema' WHERE codigo = :codigo";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindValue(':codigo', $codigo);
    $updateStmt->execute();
    
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