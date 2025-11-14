<?php
declare(strict_types=1);
require_once PROJECT_ROOT . '/auth.php';
require_once PROJECT_ROOT . '/conexao.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mensagem = '';
$tipo_mensagem = '';

if ($id <= 0) {
    header('Location: ./read-dependencia.php');
    exit;
}

// Buscar dependência
try {
    $stmt = $conexao->prepare('SELECT * FROM dependencias WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $dependencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dependencia) {
        throw new Exception('Dependência não encontrada.');
    }
} catch (Throwable $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'danger';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = isset($_POST['codigo']) ? trim((string)$_POST['codigo']) : '';
    $descricao = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';

    try {
        if ($descricao === '') {
            throw new Exception('A descrição é obrigatória.');
        }

        // Validar unicidade se código informado
        if ($codigo !== '') {
            $chk = $conexao->prepare('SELECT id FROM dependencias WHERE codigo = :codigo AND id != :id');
            $chk->bindValue(':codigo', $codigo);
            $chk->bindValue(':id', $id, PDO::PARAM_INT);
            $chk->execute();
            if ($chk->fetch()) {
                throw new Exception('Este código já está cadastrado.');
            }
        }

        if ($codigo === '') {
            $sql = 'UPDATE dependencias SET codigo = NULL, descricao = :descricao WHERE id = :id';
            $stmt = $conexao->prepare($sql);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $sql = 'UPDATE dependencias SET codigo = :codigo, descricao = :descricao WHERE id = :id';
            $stmt = $conexao->prepare($sql);
            $stmt->bindValue(':codigo', $codigo);
            $stmt->bindValue(':descricao', $descricao);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }

        $stmt->execute();

        $mensagem = 'Dependência atualizada com sucesso!';
        $tipo_mensagem = 'success';
        header('Location: ../../app/views/dependencias/read-dependencia.php?success=1');
        exit;
    } catch (Throwable $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}
?></content>
<parameter name="filePath">/home/weverton/Documentos/Github-Gitlab/GitHub/checkplanilha.anvy.com.br/CRUD/UPDATE/dependencia.php