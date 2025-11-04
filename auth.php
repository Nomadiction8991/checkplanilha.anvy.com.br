<?php
// auth.php - Middleware de autenticação
// Incluir este arquivo no início de todas as páginas que precisam de autenticação

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Salvar URL atual para redirecionar depois do login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirecionar para login
    header('Location: ' . getLoginUrl());
    exit;
}

// Função para obter URL do login baseado na profundidade do diretório
function getLoginUrl() {
    $script = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count($script, '/') - 2; // Ajustar baseado na estrutura
    
    return str_repeat('../', $depth) . 'login.php';
}

// Atualizar última atividade
$_SESSION['last_activity'] = time();

// Verificar timeout de sessão (30 minutos de inatividade)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: ' . getLoginUrl() . '?timeout=1');
    exit;
}
