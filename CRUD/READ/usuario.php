<?php
require_once __DIR__ . '/../conexao.php';

// Buscar todos os usuários
$sql = "SELECT * FROM usuarios ORDER BY nome ASC";
$stmt = $conexao->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll();
?>
