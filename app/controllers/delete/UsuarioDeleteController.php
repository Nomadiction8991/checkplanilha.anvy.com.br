<?php
 // AutenticaÃ§Ã£o
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$id = $_POST['id'] ?? null;

header('Content-Type: application/json');

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID nÃ£o informado']);
    exit;
}

try {
    $stmt = $conexao->prepare('DELETE FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'UsuÃ¡rio excluÃ­do com sucesso']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?>


