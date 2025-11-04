<?php
require_once __DIR__ . '/../conexao.php';

$id = $_GET['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$id) {
    header('Location: ./read-usuario.php');
    exit;
}

// Buscar usuário
try {
    $stmt = $conexao->prepare('SELECT * FROM usuarios WHERE id = :id');
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('Usuário não encontrado.');
    }
} catch (Exception $e) {
    $mensagem = 'Erro: ' . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    try {
        // Validações
        if (empty($nome)) {
            throw new Exception('O nome é obrigatório.');
        }

        if (empty($email)) {
            throw new Exception('O email é obrigatório.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido.');
        }

        // Verificar se email já existe (exceto o próprio usuário)
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = :email AND id != :id');
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este email já está cadastrado por outro usuário.');
        }

        // Atualizar dados
        if (!empty($senha)) {
            // Se senha foi informada, validar e atualizar
            if (strlen($senha) < 6) {
                throw new Exception('A senha deve ter no mínimo 6 caracteres.');
            }

            if ($senha !== $confirmar_senha) {
                throw new Exception('As senhas não conferem.');
            }

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha, ativo = :ativo WHERE id = :id";
            $stmt = $conexao->prepare($sql);
            $stmt->bindValue(':senha', $senha_hash);
        } else {
            // Sem alteração de senha
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, ativo = :ativo WHERE id = :id";
            $stmt = $conexao->prepare($sql);
        }

        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':ativo', $ativo);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        // Redirecionar para listagem com mensagem de sucesso
        header('Location: ./read-usuario.php?updated=1');
        exit;

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?>
