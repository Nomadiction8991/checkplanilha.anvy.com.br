<?php
session_start();

// Se já está logado, redireciona para o index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Define que é um registro público - permite criar usuário sem autenticação
define('PUBLIC_REGISTER', true);

// Inclui o processamento do backend de criação
$pageTitle = 'Cadastro';
$backUrl = 'login.php';

ob_start();
?>

<!-- Inclui o formulário de usuário -->
<?php include __DIR__ . '/app/views/usuarios/create-usuario.php'; ?>

<div class="text-center mt-3">
    <a href="login.php" class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>
        Voltar para o login
    </a>
</div>

<?php
$contentHtml = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Planilhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .register-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
            padding: 15px;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-bottom: 20px;
        }
        .card {
            border: none;
            box-shadow: none;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
        }
        h4 {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <h4 class="text-center mb-4">
                <i class="bi bi-person-plus me-2"></i>
                Cadastro
            </h4>
            
            <?php echo $contentHtml; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    
    // Novos campos
    $cpf = trim($_POST['cpf'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $rg_igual_cpf = isset($_POST['rg_igual_cpf']) ? 1 : 0;
    $telefone = trim($_POST['telefone'] ?? '');
    $tipo = 'Doador/Ministerio'; // Tipo fixo para registro público
    $assinatura = trim($_POST['assinatura'] ?? '');
    
    // Estado civil e cônjuge
    $casado = isset($_POST['casado']) ? 1 : 0;
    $nome_conjuge = trim($_POST['nome_conjuge'] ?? '');
    $cpf_conjuge = trim($_POST['cpf_conjuge'] ?? '');
    $rg_conjuge = trim($_POST['rg_conjuge'] ?? '');
    $rg_conjuge_igual_cpf = isset($_POST['rg_conjuge_igual_cpf']) ? 1 : 0;
    $telefone_conjuge = trim($_POST['telefone_conjuge'] ?? '');
    $assinatura_conjuge = trim($_POST['assinatura_conjuge'] ?? '');
    
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
        
        // Função para formatar RG (todos menos último + '-' + último)
        $formatarRg = function($valor){
            $d = preg_replace('/\D/','', $valor);
            if (strlen($d) <= 1) return $d; // um dígito sem hífen
            return substr($d,0,-1) . '-' . substr($d,-1);
        };
        if ($rg_igual_cpf) {
            // Se RG igual CPF, mantém exatamente o CPF informado (com máscara) para RG
            $rg = $cpf;
        } else {
            $rg = $formatarRg($rg);
        }
        $rg_numeros = preg_replace('/\D/','', $rg);
        if (strlen($rg_numeros) < 2) {
            throw new Exception('O RG é obrigatório e deve ter ao menos 2 dígitos.');
        }

        // Validar telefone (básico: formato)
        if (empty($telefone)) {
            throw new Exception('O telefone é obrigatório.');
        }
        
        $telefone_numeros = preg_replace('/\D/', '', $telefone);
        if (strlen($telefone_numeros) < 10 || strlen($telefone_numeros) > 11) {
            throw new Exception('Telefone inválido.');
        }

        // Endereço obrigatório (CEP, logradouro, numero, bairro, cidade, estado)
        if (empty($endereco_cep) || empty($endereco_logradouro) || empty($endereco_numero) || empty($endereco_bairro) || empty($endereco_cidade) || empty($endereco_estado)) {
            throw new Exception('Todos os campos de endereço (CEP, logradouro, número, bairro, cidade e estado) são obrigatórios.');
        }

        // Assinatura obrigatória
        if (empty($assinatura)) {
            throw new Exception('A assinatura do usuário é obrigatória.');
        }

        // Se casado, validar dados completos do cônjuge (nome, cpf, telefone, assinatura) e RG formatado se fornecido
        if ($casado) {
            if (empty($nome_conjuge)) {
                throw new Exception('O nome do cônjuge é obrigatório.');
            }
            $cpf_conjuge_num = preg_replace('/\D/','', $cpf_conjuge);
            if (strlen($cpf_conjuge_num) !== 11) {
                throw new Exception('CPF do cônjuge inválido.');
            }
            $tel_conj_num = preg_replace('/\D/','', $telefone_conjuge);
            if (strlen($tel_conj_num) < 10 || strlen($tel_conj_num) > 11) {
                throw new Exception('Telefone do cônjuge inválido.');
            }
            if (empty($assinatura_conjuge)) {
                throw new Exception('A assinatura do cônjuge é obrigatória.');
            }
            // RG do cônjuge
            if ($rg_conjuge_igual_cpf) {
                $rg_conjuge = $cpf_conjuge; // mantém máscara de CPF no RG do cônjuge
            } else if (!empty($rg_conjuge)) {
                $rg_conjuge = $formatarRg($rg_conjuge);
            }
            if (!empty($rg_conjuge)) {
                $rg_conj_nums = preg_replace('/\D/','', $rg_conjuge);
                if (strlen($rg_conj_nums) < 2) {
                    throw new Exception('O RG do cônjuge deve ter ao menos 2 dígitos.');
                }
            }
        } else {
            // Se não casado, limpar campos de cônjuge para evitar dados órfãos
            $nome_conjuge = $cpf_conjuge = $rg_conjuge = $telefone_conjuge = $assinatura_conjuge = '';
            $rg_conjuge_igual_cpf = 0;
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

        // Inserir usuário com todos os campos - usuário público sempre ativo
        $sql = "INSERT INTO usuarios (
                    nome, email, senha, ativo, cpf, rg, rg_igual_cpf, telefone, tipo, assinatura,
                    endereco_cep, endereco_logradouro, endereco_numero, endereco_complemento,
                    endereco_bairro, endereco_cidade, endereco_estado,
                    casado, nome_conjuge, cpf_conjuge, rg_conjuge, rg_conjuge_igual_cpf, telefone_conjuge, assinatura_conjuge
                ) VALUES (
                    :nome, :email, :senha, 1, :cpf, :rg, :rg_igual_cpf, :telefone, :tipo, :assinatura,
                    :endereco_cep, :endereco_logradouro, :endereco_numero, :endereco_complemento,
                    :endereco_bairro, :endereco_cidade, :endereco_estado,
                    :casado, :nome_conjuge, :cpf_conjuge, :rg_conjuge, :rg_conjuge_igual_cpf, :telefone_conjuge, :assinatura_conjuge
                )";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':senha', $senha_hash);
        $stmt->bindValue(':cpf', $cpf);
        $stmt->bindValue(':rg', $rg);
        $stmt->bindValue(':rg_igual_cpf', $rg_igual_cpf, PDO::PARAM_INT);
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
        $stmt->bindValue(':casado', $casado, PDO::PARAM_INT);
        $stmt->bindValue(':nome_conjuge', $nome_conjuge);
        $stmt->bindValue(':cpf_conjuge', $cpf_conjuge);
        $stmt->bindValue(':rg_conjuge', $rg_conjuge);
        $stmt->bindValue(':rg_conjuge_igual_cpf', $rg_conjuge_igual_cpf, PDO::PARAM_INT);
        $stmt->bindValue(':telefone_conjuge', $telefone_conjuge);
        $stmt->bindValue(':assinatura_conjuge', $assinatura_conjuge);
        $stmt->execute();

        $mensagem = 'Cadastro realizado com sucesso! Faça login para continuar.';
        $tipo_mensagem = 'success';

        // Redirecionar após sucesso
        header('Location: login.php?registered=1');
        exit;

    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Planilhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- jQuery e InputMask -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
    <!-- SignaturePad -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .register-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
            padding: 15px;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-bottom: 20px;
        }
        .signature-preview-canvas {
            pointer-events: none;
        }
        .card {
            border: none;
            box-shadow: none;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <h4 class="text-center mb-4">
                <i class="bi bi-person-plus me-2"></i>
                Cadastro
            </h4>
            
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <?php echo $mensagem; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php include __DIR__ . '/app/views/usuarios/create-usuario.php'; ?>
            
            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>
                    Voltar para o login
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
