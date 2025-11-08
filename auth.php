<?php
// auth.php - Middleware de autenticação
// Incluir este arquivo no início de todas as páginas que precisam de autenticação

session_start();

// Função para obter URL do login baseado na profundidade do diretório
function getLoginUrl() {
    $script = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count($script, '/') - 2; // Ajustar baseado na estrutura
    
    return str_repeat('../', max($depth, 0)) . 'login.php';
}

// Modo público: permitir acesso restrito a algumas páginas com base em sessão pública
$isPublic = !empty($_SESSION['public_acesso']) && !empty($_SESSION['public_planilha_id']);
if (!isset($_SESSION['usuario_id'])) {
    if ($isPublic) {
        // Lista de páginas públicas permitidas (entrada do script)
        $allowed = [
            '/app/views/shared/menu-unificado.php',
            '/app/views/planilhas/relatorio-14-1.php',
            '/app/views/planilhas/assinatura-14-1.php',
            '/app/views/planilhas/assinatura-14-1-form.php',
            '/app/views/planilhas/imprimir-alteracao.php',
        ];

        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $root = realpath(__DIR__);
        $ok = false;
        foreach ($allowed as $rel) {
            $full = realpath($root . $rel);
            if ($full && $scriptFile === $full) { $ok = true; break; }
        }

        if (!$ok) {
            // Bloquear acesso fora das páginas permitidas
            header('Location: ' . getLoginUrl());
            exit;
        }
        // Permitido em modo público
    } else {
        // Fluxo normal: exigir login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . getLoginUrl());
        exit;
    }
}

// Função para obter URL do login baseado na profundidade do diretório
// (função getLoginUrl movida acima)

// Atualizar última atividade
$_SESSION['last_activity'] = time();

// Verificar timeout de sessão (30 minutos de inatividade)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: ' . getLoginUrl() . '?timeout=1');
    exit;
}

// Função para verificar se o usuário é Administrador/Acessor
function isAdmin() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'Administrador/Acessor';
}

// Função para verificar se o usuário é Doador/Cônjuge
function isDoador() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'Doador/Cônjuge';
}
