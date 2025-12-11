<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação

// Redireciona para a listagem de produtos usando comum_id
$comum_id = isset($_GET['comum_id']) ? (int)$_GET['comum_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($comum_id > 0) {
    header('Location: ../produtos/read-produto.php?comum_id=' . $comum_id);
    exit;
}
header('Location: ../../index.php');
exit;
