<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

// Apenas admins podem editar
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$id = $_GET['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$id) {
    header('Location: ./read-dependencia.php');
    exit;
}

// Buscar dependência
try {
    $stmt = $conexao->prepare('SELECT * FROM dependencias WHERE id = :id');
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $dependencia = $stmt->fetch();

    if (!$dependencia) {
        throw new Exception('Dependência não encontrada.');
    }
} catch (Exception $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    try {
        // Validações
        if (empty($descricao)) {
            throw new Exception('A descrição é obrigatória.');
        }

        // Se código fornecido, verificar unicidade
        if (!empty($codigo)) {
            $stmt = $conexao->prepare('SELECT id FROM dependencias WHERE codigo = :codigo AND id != :id');
            $stmt->bindValue(':codigo', $codigo);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            if ($stmt->fetch()) {
                throw new Exception('Este código já está cadastrado.');
            }
        }

        // Atualizar dependência
        $setParts = ['descricao = :descricao'];
        $values = [':descricao' => $descricao, ':id' => $id];

        if (!empty($codigo)) {
            $setParts[] = 'codigo = :codigo';
            $values[':codigo'] = $codigo;
        } else {
            $setParts[] = 'codigo = NULL';
        }

        $sql = "UPDATE dependencias SET " . implode(', ', $setParts) . " WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        foreach ($values as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $mensagem = 'Dependência atualizada com sucesso!';
        $tipo_mensagem = 'success';

        // Redirecionar para listagem
        header('Location: ../../app/views/dependencias/read-dependencia.php?success=1');
        exit;

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?></content>
<parameter name="filePath">/home/weverton/Documentos/Github-Gitlab/GitHub/checkplanilha.anvy.com.br/CRUD/UPDATE/dependencia.php