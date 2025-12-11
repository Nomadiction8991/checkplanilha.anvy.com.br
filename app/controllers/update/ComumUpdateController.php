<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
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
        throw new Exception('ID invÃƒÂ¡lido.');
    }
    if ($descricao === '') {
        throw new Exception('DescriÃƒÂ§ÃƒÂ£o ÃƒÂ© obrigatÃƒÂ³ria.');
    }
    if ($cnpj === '' || strlen($cnpj) !== 14) {
        throw new Exception('CNPJ ÃƒÂ© obrigatÃƒÂ³rio e deve ter 14 dÃƒÂ­gitos.');
    }
    if ($administracao === '') {
        throw new Exception('AdministraÃƒÂ§ÃƒÂ£o ÃƒÂ© obrigatÃƒÂ³ria.');
    }
    if ($cidade === '') {
        throw new Exception('Cidade ÃƒÂ© obrigatÃƒÂ³ria.');
    }

    // Garantir unicidade do CNPJ
    $stmtCheck = $conexao->prepare('SELECT id FROM comums WHERE cnpj = :cnpj AND id != :id');
    $stmtCheck->bindValue(':cnpj', $cnpj);
    $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtCheck->execute();
    if ($stmtCheck->fetch()) {
        throw new Exception('JÃƒÂ¡ existe um comum com este CNPJ.');
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
    header('Location: ../../views/comuns/comum_editar.php?id=' . urlencode((string)$id));
    exit;
} catch (Throwable $e) {
    $_SESSION['mensagem'] = 'Erro ao salvar: ' . $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../../views/comuns/comum_editar.php?id=' . urlencode((string)$id));
    exit;
}




