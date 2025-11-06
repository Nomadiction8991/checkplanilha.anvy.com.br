<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

// Parâmetros da paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_comum = isset($_GET['comum']) ? $_GET['comum'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_ativo = isset($_GET['ativo']) ? $_GET['ativo'] : '1'; // Padrão: apenas ativos
$filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Construir a query base
$sql = "SELECT p.*, 
    (SELECT COUNT(*) FROM produtos pr WHERE pr.planilha_id = p.id) AS total_produtos,
    (SELECT COUNT(*) FROM produtos pr WHERE pr.planilha_id = p.id AND COALESCE(pr.checado, 0) = 0) AS total_pendentes
    FROM planilhas p WHERE 1=1";
$params = [];

// Aplicar filtro de comum
if (!empty($filtro_comum)) {
    $sql .= " AND comum LIKE :comum";
    $params[':comum'] = '%' . $filtro_comum . '%';
}

// Filtro de status será aplicado após calcular status_calc (não mais na query SQL)

// Aplicar filtro de ativo/inativo
if ($filtro_ativo !== 'todos') {
    $sql .= " AND ativo = :ativo";
    $params[':ativo'] = $filtro_ativo;
}

// Aplicar filtro de intervalo de datas
if (!empty($filtro_data_inicio)) {
    $sql .= " AND DATE(data_posicao) >= :data_inicio";
    $params[':data_inicio'] = $filtro_data_inicio;
}

if (!empty($filtro_data_fim)) {
    $sql .= " AND DATE(data_posicao) <= :data_fim";
    $params[':data_fim'] = $filtro_data_fim;
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
$sql .= " ORDER BY data_posicao DESC, id DESC LIMIT :limite OFFSET :offset";
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

// Status calculados (não mais do banco, mas opções fixas para filtro)
$status_options = ['Pendente', 'Em Execução', 'Concluído'];

// Calcular status dinâmico por planilha
foreach ($planilhas as &$pl) {
    $total_produtos = (int)($pl['total_produtos'] ?? 0);
    $total_pendentes = (int)($pl['total_pendentes'] ?? 0);
    if ($total_produtos > 0 && $total_pendentes === $total_produtos) {
        $pl['status_calc'] = 'Pendente';
    } elseif ($total_produtos > 0 && $total_pendentes === 0) {
        $pl['status_calc'] = 'Concluído';
    } else {
        $pl['status_calc'] = 'Em Execução';
    }
}
unset($pl);

// Aplicar filtro de status calculado (se fornecido)
if (!empty($filtro_status)) {
    $planilhas = array_filter($planilhas, function($pl) use ($filtro_status) {
        return ($pl['status_calc'] ?? '') === $filtro_status;
    });
    // Reindexar array após filtro
    $planilhas = array_values($planilhas);
}
?>