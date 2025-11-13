<?php
session_start();

// Se já está logado, redireciona para o index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/CRUD/conexao.php';

// Ambiente atual (podemos usar ANVY_ENV para identificar 'dev' ou 'prod')
$CURRENT_ENV = getenv('ANVY_ENV') ?: '';
$erro = '';
$sucesso = '';

// Mensagem de sucesso ao registrar
if (isset($_GET['registered'])) {
    $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    try {
        if (empty($email) || empty($senha)) {
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
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
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
                                    <div id="pwa-install-container" class="d-grid mt-3" style="display:none;">
                                            <button id="btn-install-pwa" type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#installModal">
                                                    <i class="bi bi-phone me-2"></i>
                                                    📲 Instalar Aplicativo
                                            </button>
                                    </div>

                                    <!-- Modal para escolher ambiente de instalação -->
                                    <div class="modal fade" id="installModal" tabindex="-1" aria-labelledby="installModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="installModalLabel">Instalar Aplicativo</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Escolha o ambiente que você deseja instalar:</p>
                                                    <div class="d-grid gap-2">
                                                        <button id="install-prod" type="button" class="btn btn-primary">Instalar Produção</button>
                                                        <button id="install-dev" type="button" class="btn btn-outline-secondary">Instalar Desenvolvimento</button>
                                                    </div>
                                                    <hr>
                                                    <small class="text-muted">Se o navegador não suportar instalação automática (ex.: iOS), você será redirecionado para a página de login do ambiente escolhido com instruções.</small>
                                                </div>
                                            </div>
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
        // Variável definida no servidor para controlar se estamos em produção
        const IS_PROD = <?php echo $isProdInstall ? 'true' : 'false'; ?>;

        function isInPWA() {
            try {
                return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            } catch (e) {
                return false;
            }
        }

        (function() {
            if (!IS_PROD) return; // não ativa nada em ambientes que não sejam produção

            let deferredPrompt = null;
            const container = document.getElementById('pwa-install-container');
            const btn = document.getElementById('btn-install-pwa');

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                // só mostrar o botão se não estivermos já em modo PWA
                if (!isInPWA() && container) {
                    container.style.display = 'block';
                }
            });

            // Se já estivermos em standalone, esconde imediatamente (fallback)
            <script>
                // Ambiente corrente e flags do servidor
                const CURRENT_ENV = <?php echo json_encode($CURRENT_ENV); ?>;

                function isInPWA() {
                    try {
                        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                    } catch (e) {
                        return false;
                    }
                }

                (function() {
                    let deferredPrompt = null;
                    const container = document.getElementById('pwa-install-container');
                    const btnProd = document.getElementById('install-prod');
                    const btnDev = document.getElementById('install-dev');

                    // Mostrar o botão principal se não estivermos em modo PWA
                    if (!isInPWA() && container) {
                        container.style.display = 'block';
                    }

                    // Flag para auto-install quando chegarmos por ?auto_install=1
                    const urlParams = new URLSearchParams(window.location.search);
                    const autoInstall = urlParams.get('auto_install') === '1';

                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        deferredPrompt = e;
                        // se chegamos via ?auto_install=1, disparar prompt automaticamente
                        if (autoInstall) {
                            deferredPrompt.prompt();
                            deferredPrompt.userChoice.then(() => { deferredPrompt = null; });
                            return;
                        }
                    });

                    // Função auxiliar que tenta usar deferredPrompt ou redireciona para a página de login do ambiente
                    function installForEnv(targetEnv) {
                        // Se o usuário escolheu o ambiente atual e temos prompt salvo, usar prompt
                        if (targetEnv === CURRENT_ENV && deferredPrompt) {
                            deferredPrompt.prompt();
                            deferredPrompt.userChoice.then(() => { deferredPrompt = null; });
                            // fechar modal (Bootstrap)
                            const modalEl = document.getElementById('installModal');
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                            return;
                        }

                        // Caso contrário, redirecionar para o login do ambiente desejado com auto_install=1
                        const targetPath = targetEnv === 'prod' ? '/prod/login.php?auto_install=1' : '/dev/login.php?auto_install=1';
                        window.location.href = targetPath;
                    }

                    if (btnProd) btnProd.addEventListener('click', () => installForEnv('prod'));
                    if (btnDev) btnDev.addEventListener('click', () => installForEnv('dev'));

                    // Se já estivermos em standalone esconder o container
                    if (isInPWA() && container) {
                        container.style.display = 'none';
                    }
                })();
            </script>
