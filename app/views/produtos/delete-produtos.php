<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/conexao.php';

header('Content-Type: application/json');

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter IDs e ID da planilha
$id_planilha = $_POST['id_planilha'] ?? null;
$ids_produtos = $_POST['ids_produtos'] ?? [];

if (!$id_planilha || empty($ids_produtos)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

try {
    // Converter array de IDs para valores seguros
    $ids_produtos = array_map('intval', $ids_produtos);
    $placeholders = implode(',', array_fill(0, count($ids_produtos), '?'));
    
    // Preparar SQL
    $sql = "DELETE FROM produtos WHERE planilha_id = ? AND id_produto IN ($placeholders)";
    $stmt = $conexao->prepare($sql);
    
    // Bind do ID da planilha
    $stmt->bindValue(1, $id_planilha, PDO::PARAM_INT);
    
    // Bind dos IDs dos produtos
    foreach ($ids_produtos as $index => $id) {
        $stmt->bindValue($index + 2, $id, PDO::PARAM_INT);
    }
    
    // Executar
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Produtos excluídos com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir produtos']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
