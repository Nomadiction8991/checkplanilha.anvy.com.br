<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

// Apenas admins podem deletar
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$id = $_POST['id'] ?? null;

header('Content-Type: application/json');

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID não informado']);
    exit;
}

try {
    $stmt = $conexao->prepare('DELETE FROM dependencias WHERE id = :id');
    $stmt->bindValue(':id', $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Dependência excluída com sucesso']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir: ' . $e->getMessage()]);
}
?></content>
<parameter name="filePath">/home/weverton/Documentos/Github-Gitlab/GitHub/checkplanilha.anvy.com.br/CRUD/DELETE/dependencia.php