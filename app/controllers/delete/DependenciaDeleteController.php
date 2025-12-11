<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID não informado']);
    exit;
}

try {
    $stmt = $conexao->prepare('DELETE FROM dependencias WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Dependência excluída com sucesso']);
} catch (Throwable $e) {
    error_log('Erro ao excluir dependência: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir dependência']);
}

