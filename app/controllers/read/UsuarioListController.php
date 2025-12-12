<?php
// Autenticação
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

$filtroNome = trim((string)($_GET['busca'] ?? ''));
$filtroStatus = isset($_GET['status']) ? $_GET['status'] : '';

$where = [];
$params = [];

if ($filtroNome !== '') {
	$where[] = '(LOWER(nome) LIKE :busca OR LOWER(email) LIKE :busca)';
	$params[':busca'] = '%' . mb_strtolower($filtroNome, 'UTF-8') . '%';
}

if ($filtroStatus !== '' && in_array($filtroStatus, ['0', '1'], true)) {
	$where[] = 'ativo = :status';
	$params[':status'] = $filtroStatus;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Contagem com filtros aplicados
$sql_count = "SELECT COUNT(*) FROM usuarios" . $whereSql;
$stmt = $conexao->prepare($sql_count);
foreach ($params as $key => $value) {
	$stmt->bindValue($key, $value);
}
$stmt->execute();
$total_registros = (int)$stmt->fetchColumn();
$total_paginas = (int)ceil($total_registros / $limite);

// Busca paginada
$sql = "SELECT * FROM usuarios" . $whereSql . " ORDER BY nome ASC LIMIT :limite OFFSET :offset";
$stmt = $conexao->prepare($sql);
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
	$stmt->bindValue($key, $value);
}
$stmt->execute();
$usuarios = $stmt->fetchAll();
?>


