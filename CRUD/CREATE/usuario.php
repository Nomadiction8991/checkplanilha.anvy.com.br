<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Novos campos
    $cpf = trim($_POST['cpf'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $tipo = trim($_POST['tipo'] ?? 'Administrador/Acessor');
    $assinatura = trim($_POST['assinatura'] ?? '');
    
    // Endereço
    $endereco_cep = trim($_POST['endereco_cep'] ?? '');
    $endereco_logradouro = trim($_POST['endereco_logradouro'] ?? '');
    $endereco_numero = trim($_POST['endereco_numero'] ?? '');
    $endereco_complemento = trim($_POST['endereco_complemento'] ?? '');
    $endereco_bairro = trim($_POST['endereco_bairro'] ?? '');
    $endereco_cidade = trim($_POST['endereco_cidade'] ?? '');
    $endereco_estado = trim($_POST['endereco_estado'] ?? '');

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

        if (empty($senha)) {
            throw new Exception('A senha é obrigatória.');
        }

        if (strlen($senha) < 6) {
            throw new Exception('A senha deve ter no mínimo 6 caracteres.');
        }

        if ($senha !== $confirmar_senha) {
            throw new Exception('As senhas não conferem.');
        }
        
        // Validar CPF (básico: apenas formato)
        if (empty($cpf)) {
            throw new Exception('O CPF é obrigatório.');
        }
        
        $cpf_numeros = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf_numeros) !== 11) {
            throw new Exception('CPF inválido. Deve conter 11 dígitos.');
        }
        
        // Validar telefone (básico: formato)
        if (empty($telefone)) {
            throw new Exception('O telefone é obrigatório.');
        }
        
        $telefone_numeros = preg_replace('/\D/', '', $telefone);
        if (strlen($telefone_numeros) < 10 || strlen($telefone_numeros) > 11) {
            throw new Exception('Telefone inválido.');
        }

        // Verificar se email já existe
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este email já está cadastrado.');
        }
        
        // Verificar se CPF já existe
        $stmt = $conexao->prepare('SELECT id FROM usuarios WHERE cpf = :cpf');
        $stmt->bindValue(':cpf', $cpf);
        $stmt->execute();
        if ($stmt->fetch()) {
            throw new Exception('Este CPF já está cadastrado.');
        }

        // Criptografar senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Inserir usuário com todos os campos
        $sql = "INSERT INTO usuarios (
                    nome, email, senha, ativo, cpf, telefone, tipo, assinatura,
                    endereco_cep, endereco_logradouro, endereco_numero, endereco_complemento,
                    endereco_bairro, endereco_cidade, endereco_estado
                ) VALUES (
                    :nome, :email, :senha, :ativo, :cpf, :telefone, :tipo, :assinatura,
                    :endereco_cep, :endereco_logradouro, :endereco_numero, :endereco_complemento,
                    :endereco_bairro, :endereco_cidade, :endereco_estado
                )";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':senha', $senha_hash);
        $stmt->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $stmt->bindValue(':cpf', $cpf);
        $stmt->bindValue(':telefone', $telefone);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->bindValue(':assinatura', $assinatura);
        $stmt->bindValue(':endereco_cep', $endereco_cep);
        $stmt->bindValue(':endereco_logradouro', $endereco_logradouro);
        $stmt->bindValue(':endereco_numero', $endereco_numero);
        $stmt->bindValue(':endereco_complemento', $endereco_complemento);
        $stmt->bindValue(':endereco_bairro', $endereco_bairro);
        $stmt->bindValue(':endereco_cidade', $endereco_cidade);
        $stmt->bindValue(':endereco_estado', $endereco_estado);
        $stmt->execute();

        $mensagem = 'Usuário cadastrado com sucesso!';
        $tipo_mensagem = 'success';

        // Redirecionar após sucesso
        header('Location: ../../app/views/usuarios/read-usuario.php?success=1');
        exit;

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?>
