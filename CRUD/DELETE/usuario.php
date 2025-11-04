<?php
require_once __DIR__ . '/../conexao.php';

$id = $_POST['id'] ?? null;

header('Content-Type: application/json');

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID não informado']);
    exit;
}

try {
    $stmt = $conexao->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>
