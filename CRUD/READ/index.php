<?php
require_once '../conexao.php';

// Parâmetros da paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_comum = isset($_GET['comum']) ? $_GET['comum'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$mostrar_inativos = isset($_GET['mostrar_inativos']) && $_GET['mostrar_inativos'] == '1';

// Construir a query base
$sql = "SELECT * FROM planilhas WHERE 1=1";
$params = [];

// Aplicar filtro de comum
if (!empty($filtro_comum)) {
    $sql .= " AND comum LIKE :comum";
    $params[':comum'] = '%' . $filtro_comum . '%';
}

// Aplicar filtro de status
if (!empty($filtro_status)) {
    $sql .= " AND status = :status";
    $params[':status'] = $filtro_status;
}

// Lógica para ativo/inativo
if ($mostrar_inativos) {
    $sql .= " AND ativo = 0";
} else {
    $sql .= " AND ativo = 1";
}

// Contar total de registros (para paginação)
$sql_count = "SELECT COUNT(*) as total FROM ($sql) as count_table";
$stmt_count = $conexao->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $limite);

// Adicionar ordenação e paginação à query principal
$sql .= " ORDER BY id DESC LIMIT :limite OFFSET :offset";
$params[':limite'] = $limite;
$params[':offset'] = $offset;

// Executar a query principal
$stmt = $conexao->prepare($sql);
foreach ($params as $key => $value) {
    if ($key === ':limite' || $key === ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$planilhas = $stmt->fetchAll();

// Buscar valores únicos de status para o select
$sql_status = "SELECT DISTINCT status FROM planilhas ORDER BY status";
$stmt_status = $conexao->query($sql_status);
$status_options = $stmt_status->fetchAll(PDO::FETCH_COLUMN);
?>