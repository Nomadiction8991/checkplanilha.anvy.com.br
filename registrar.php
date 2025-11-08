<?php
session_start();

// Se já está logado, redireciona para o index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Define que é um registro público - permite criar usuário sem autenticação
define('PUBLIC_REGISTER', true);

// Processa o formulário se for POST (inclui o backend)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/CRUD/CREATE/usuario.php';
    // Se chegou aqui sem redirecionar, houve erro (a mensagem está em $_SESSION)
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
        .card {
            border: none;
            box-shadow: none;
            margin-bottom: 1rem;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
        }
        .signature-pad-wrapper {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }
        canvas {
            width: 100%;
            height: 150px;
            cursor: crosshair;
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
            
            <?php if (!empty($_SESSION['mensagem'])): ?>
                <div class="alert alert-<?php echo $_SESSION['tipo_mensagem'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <?php 
                    echo htmlspecialchars($_SESSION['mensagem']); 
                    unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']);
                    ?>
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
