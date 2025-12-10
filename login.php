<?php
require_once __DIR__ . '/bootstrap.php';
session_start();

// Se já estiver logado, manda para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once PROJECT_ROOT . '/CRUD/conexao.php';

$erro = '';
$sucesso = '';
$sigaLoginRedirect = base_url('auth/siga/redirect.php');

// Mensagem de sucesso ao registrar
if (isset($_GET['registered'])) {
    $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    try {
        if ($email === '' || $senha === '') {
            throw new Exception('Email e senha são obrigatórios.');
        }

        // Buscar usuário por email
        $stmt = $conexao->prepare('SELECT * FROM usuarios WHERE email = :email AND ativo = 1');
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch();

        if (!$usuario) {
            throw new Exception('Email ou senha inválidos.');
        }

        // Verificar senha
        if (!password_verify($senha, $usuario['senha'])) {
            throw new Exception('Email ou senha inválidos.');
        }

        // Login bem-sucedido
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'] ?? 'Administrador/Acessor';

        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Planilhas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 15px;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-body">
                <?php if ($sucesso): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo htmlspecialchars($sucesso); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($erro); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <h4 class="text-center mb-4">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Login
                        </h4>
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope me-1"></i>
                            Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="seu@email.com" required autofocus
                               value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>

                    <div class="mb-4">
                        <label for="senha" class="form-label">
                            <i class="bi bi-lock me-1"></i>
                            Senha
                        </label>
                        <input type="password" class="form-control" id="senha" name="senha" 
                               placeholder="Digite sua senha" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Entrar
                        </button>
                    </div>
                </form>
                
                <div class="mt-4">
                    <div class="text-center mb-2 text-muted small">
                        Ou acesse usando sua conta SIGA (login externo)
                    </div>
                    <button type="button" id="btnSigaLogin" class="btn btn-outline-primary w-100">
                        <i class="bi bi-box-arrow-up-right me-2"></i>
                        Entrar pelo SIGA
                    </button>
                    <div class="form-text text-center mt-2">
                        O login é feito diretamente no SIGA. Nenhuma senha é armazenada aqui.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="app/views/usuarios/create-usuario.php?public=1" class="btn btn-light w-100 mb-2">
                <i class="bi bi-person-plus me-2"></i>
                Cadastre-se
            </a>
            <small class="text-white">
                <i class="bi bi-shield-check me-1"></i>
                Acesso seguro e criptografado
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Abre login SIGA em popup para facilitar o retorno
        (function() {
            var btn = document.getElementById('btnSigaLogin');
            if (!btn) return;
            btn.addEventListener('click', function() {
                var w = 520, h = 720;
                var left = (window.screen.width / 2) - (w / 2);
                var top = (window.screen.height / 2) - (h / 2);
                var popupUrl = <?php echo json_encode(base_url('auth/siga/redirect.php?popup=1')); ?>;
                var win = window.open(popupUrl, 'sigaLogin', 'width=' + w + ',height=' + h + ',left=' + left + ',top=' + top);
                if (!win) {
                    window.location.href = popupUrl; // fallback se popup bloqueado
                    return;
                }
                var handler = function(event) {
                    if (event && event.data && event.data.sigaAuth) {
                        window.removeEventListener('message', handler);
                        win.close();
                        window.location.href = <?php echo json_encode(base_url('index.php')); ?>;
                    }
                };
                window.addEventListener('message', handler, false);
            });
        })();
    </script>
</body>
</html>
