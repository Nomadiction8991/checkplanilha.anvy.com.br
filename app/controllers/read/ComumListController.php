<?php
 // AutenticaÃ§Ã£o
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// ParÃ¢metros da paginaÃ§Ã£o
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_comum = isset($_GET['comum']) ? $_GET['comum'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_ativo = isset($_GET['ativo']) ? $_GET['ativo'] : '1'; // PadrÃ£o: apenas ativos
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

// Filtro de status serÃ¡ aplicado apÃ³s calcular status_calc (nÃ£o mais na query SQL)

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

// Contar total de registros (para paginaÃ§Ã£o)
$sql_count = "SELECT COUNT(*) as total FROM ($sql) as count_table";
$stmt_count = $conexao->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $limite);

// Adicionar ordenaÃ§Ã£o e paginaÃ§Ã£o Ã  query principal
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

// Status calculados (nÃ£o mais do banco, mas opÃ§Ãµes fixas para filtro)
$status_options = ['Pendente', 'Em ExecuÃ§Ã£o', 'ConcluÃ­do'];

// Calcular status dinÃ¢mico por planilha
foreach ($planilhas as &$pl) {
    $total_produtos = (int)($pl['total_produtos'] ?? 0);
    $total_pendentes = (int)($pl['total_pendentes'] ?? 0);
    if ($total_produtos > 0 && $total_pendentes === $total_produtos) {
        $pl['status_calc'] = 'Pendente';
    } elseif ($total_produtos > 0 && $total_pendentes === 0) {
        $pl['status_calc'] = 'ConcluÃ­do';
    } else {
        $pl['status_calc'] = 'Em ExecuÃ§Ã£o';
    }
}
unset($pl);

// Aplicar filtro de status calculado (se fornecido)
if (!empty($filtro_status)) {
    $planilhas = array_filter($planilhas, function($pl) use ($filtro_status) {
        return ($pl['status_calc'] ?? '') === $filtro_status;
    });
    // Reindexar array apÃ³s filtro
    $planilhas = array_values($planilhas);
}
?>


