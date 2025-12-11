<?php
require_once __DIR__ . '/../../../auth.php';

// Aceita ?comum_id ou fallback ?id
$comum_id = isset($_GET['comum_id']) ? (int)$_GET['comum_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($comum_id <= 0) {
    header('Location: ../../index.php');
    exit;
}

// Garantir que o controller de produtos receba o parametro
$_GET['comum_id'] = $comum_id;

// Reutiliza a view de produtos, mantendo a URL em /view-planilha.php
require_once __DIR__ . '/../produtos/read-produto.php';
exit;
