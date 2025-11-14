<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação
require_once PROJECT_ROOT . '/conexao.php';

$pagina = isset($_GET['pagina']) ? max(1,(int)$_GET['pagina']) : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

// Contagem total
$sql_count = "SELECT COUNT(*) FROM usuarios";
$total_registros = (int)$conexao->query($sql_count)->fetchColumn();
$total_paginas = (int)ceil($total_registros / $limite);

// Buscar página de usuários
$sql = "SELECT * FROM usuarios ORDER BY nome ASC LIMIT :limite OFFSET :offset";
$stmt = $conexao->prepare($sql);
$stmt->bindValue(':limite',$limite,PDO::PARAM_INT);
$stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll();
?>
