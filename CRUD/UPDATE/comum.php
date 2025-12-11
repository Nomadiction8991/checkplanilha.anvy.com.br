<?php
declare(strict_types=1);
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../app/views/comuns/listar-comuns.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$descricao = trim((string)($_POST['descricao'] ?? ''));
$cnpj = preg_replace('/\D+/', '', (string)($_POST['cnpj'] ?? ''));
$administracao = trim((string)($_POST['administracao'] ?? ''));
$cidade = trim((string)($_POST['cidade'] ?? ''));
$setor = trim((string)($_POST['setor'] ?? ''));

try {
    if ($id <= 0) {
        throw new Exception('ID inválido.');
    }
    if ($descricao === '') {
        throw new Exception('Descrição é obrigatória.');
    }
    if ($cnpj === '' || strlen($cnpj) !== 14) {
        throw new Exception('CNPJ é obrigatório e deve ter 14 dígitos.');
    }
    if ($administracao === '') {
        throw new Exception('Administração é obrigatória.');
    }
    if ($cidade === '') {
        throw new Exception('Cidade é obrigatória.');
    }

    // Garantir unicidade do CNPJ
    $stmtCheck = $conexao->prepare('SELECT id FROM comums WHERE cnpj = :cnpj AND id != :id');
    $stmtCheck->bindValue(':cnpj', $cnpj);
    $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    if ($stmtCheck->fetch()) {
        throw new Exception('Já existe um comum com este CNPJ.');
    }

    $stmt = $conexao->prepare('UPDATE comums 
                               SET descricao = :descricao,
                                   cnpj = :cnpj,
                                   administracao = :administracao,
                                   cidade = :cidade,
                                   setor = :setor
                               WHERE id = :id');
    $stmt->bindValue(':descricao', $descricao);
    $stmt->bindValue(':cnpj', $cnpj);
    $stmt->bindValue(':administracao', $administracao);
    $stmt->bindValue(':cidade', $cidade);
    $stmt->bindValue(':setor', $setor !== '' ? $setor : null, $setor !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['mensagem'] = 'Comum atualizada com sucesso!';
    $_SESSION['tipo_mensagem'] = 'success';
    header('Location: ../../app/views/comuns/listar-comuns.php');
    exit;
} catch (Throwable $e) {
    $_SESSION['mensagem'] = 'Erro ao salvar: ' . $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../../app/views/comuns/editar-comum.php?id=' . urlencode((string)$id));
    exit;
}
