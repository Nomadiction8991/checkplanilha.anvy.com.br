<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

// Apenas admins podem criar
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

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
            $stmt = $conexao->prepare('SELECT id FROM dependencias WHERE codigo = :codigo');
            $stmt->bindValue(':codigo', $codigo);
            $stmt->execute();
            if ($stmt->fetch()) {
                throw new Exception('Este código já está cadastrado.');
            }
        }

        // Inserir dependência
        $fields = ['descricao'];
        $placeholders = [':descricao'];
        $values = [':descricao' => $descricao];

        if (!empty($codigo)) {
            $fields[] = 'codigo';
            $placeholders[] = ':codigo';
            $values[':codigo'] = $codigo;
        }

        $sql = "INSERT INTO dependencias (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $conexao->prepare($sql);
        foreach ($values as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $mensagem = 'Dependência cadastrada com sucesso!';
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
<parameter name="filePath">/home/weverton/Documentos/Github-Gitlab/GitHub/checkplanilha.anvy.com.br/CRUD/CREATE/dependencia.php