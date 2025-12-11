<?php
require_once __DIR__ . '/bootstrap.php';

// Logar mas nao exibir erros em producao
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Middleware de autenticacao
// Incluir este arquivo no inicio de todas as paginas que precisam de autenticacao

// URL de login baseada na profundidade do diretorio
function getLoginUrl(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $depth = substr_count($script, '/') - 2;
    return str_repeat('../', max($depth, 0)) . 'login.php';
}

// Modo publico: permitir acesso restrito a algumas paginas com base em sessao publica
$isPublic = !empty($_SESSION['public_acesso']) && !empty($_SESSION['public_planilha_id']);
if (!isset($_SESSION['usuario_id'])) {
    if ($isPublic) {
        // Lista de paginas publicas permitidas (entrada do script)
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
            if ($full && $scriptFile === $full) {
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            header('Location: ' . getLoginUrl());
            exit;
        }
    } else {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . getLoginUrl());
        exit;
    }
}

// Atualizar ultima atividade
$_SESSION['last_activity'] = time();

// Timeout de sessao (30 minutos de inatividade)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: ' . getLoginUrl() . '?timeout=1');
    exit;
}

// Verifica se o usuario e Administrador/Acessor
function isAdmin(): bool {
    $tipo = $_SESSION['usuario_tipo'] ?? '';
    return $tipo === 'Administrador/Acessor' || stripos($tipo, 'administrador') !== false;
}

// Verifica se o usuario e Doador/Conjuge
function isDoador(): bool {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'Doador/Conjuge';
}
