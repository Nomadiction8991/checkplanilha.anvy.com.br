<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../index.php');
    exit;
}

// Verificar se há mensagem de erro
$erro = $_GET['erro'] ?? '';
if (!empty($erro)) {
    echo "<script>alert('" . addslashes($erro) . "');</script>";
}

// Buscar dados da planilha
try {
    $sql_planilha = "SELECT * FROM planilhas WHERE id = :id";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':id', $id_planilha);
    $stmt_planilha->execute();
    $planilha = $stmt_planilha->fetch();
    
    if (!$planilha) {
        throw new Exception('Planilha não encontrada.');
    }
} catch (Exception $e) {
    die("Erro ao carregar planilha: " . $e->getMessage());
}

// Parâmetros da paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 20; // 20 produtos por página
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Construir a query base
$sql = "SELECT p.*, 
               COALESCE(pc.checado, 0) as checado,
               COALESCE(pc.dr, 0) as dr,
               COALESCE(pc.imprimir, 0) as imprimir,
               COALESCE(pc.editado, 0) as editado,
               pc.observacoes,
               pc.nome as nome_editado,
               pc.dependencia as dependencia_editada
        FROM produtos p 
        LEFT JOIN produtos_check pc ON p.id = pc.produto_id 
        WHERE p.id_planilha = :id_planilha";
$params = [':id_planilha' => $id_planilha];

if (!empty($filtro_nome)) {
    $sql .= " AND p.nome LIKE :nome";
    $params[':nome'] = '%' . $filtro_nome . '%';
}
if (!empty($filtro_dependencia)) {
    // Se o produto foi editado (editado = 1), usa a nova dependência, caso contrário usa a original
    $sql .= " AND (
        (COALESCE(pc.editado, 0) = 1 AND pc.dependencia LIKE :dependencia) OR
        (COALESCE(pc.editado, 0) = 0 AND p.dependencia LIKE :dependencia)
    )";
    $params[':dependencia'] = '%' . $filtro_dependencia . '%';
}
if (!empty($filtro_codigo)) {
    // Normalizar código (remover espaços, traços, barras) para comparação
    $codigo_normalizado = preg_replace('/[\s\-\/]/', '', $filtro_codigo);
    $sql .= " AND REPLACE(REPLACE(REPLACE(p.codigo, ' ', ''), '-', ''), '/', '') LIKE :codigo";
    $params[':codigo'] = '%' . $codigo_normalizado . '%';
}

// Filtro de status
if (!empty($filtro_status)) {
    switch ($filtro_status) {
        case 'checado':
            $sql .= " AND COALESCE(pc.checado, 0) = 1";
            break;
        case 'observacao':
            $sql .= " AND (pc.observacoes IS NOT NULL AND pc.observacoes != '')";
            break;
        case 'etiqueta':
            $sql .= " AND COALESCE(pc.imprimir, 0) = 1";
            break;
        case 'pendente':
            $sql .= " AND (COALESCE(pc.checado, 0) = 0 AND (pc.observacoes IS NULL OR pc.observacoes = '') AND COALESCE(pc.dr, 0) = 0 AND COALESCE(pc.imprimir, 0) = 0 AND COALESCE(pc.editado, 0) = 0)";
            break;
        case 'dr':
            $sql .= " AND COALESCE(pc.dr, 0) = 1";
            break;
        case 'editado':
            $sql .= " AND COALESCE(pc.editado, 0) = 1";
            break;
    }
}

// Contar total
$sql_count = "SELECT COUNT(*) as total FROM ($sql) as count_table";
$stmt_count = $conexao->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $limite);

// Ordenação e paginação
$sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";
$params[':limite'] = $limite;
$params[':offset'] = $offset;

$stmt = $conexao->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, ($key === ':limite' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$produtos = $stmt->fetchAll();

// Filtros únicos
$sql_filtros = "SELECT DISTINCT dependencia FROM produtos WHERE id_planilha = :id_planilha ORDER BY dependencia";
$stmt_filtros = $conexao->prepare($sql_filtros);
$stmt_filtros->bindValue(':id_planilha', $id_planilha);
$stmt_filtros->execute();
$dependencia_options = $stmt_filtros->fetchAll(PDO::FETCH_COLUMN);
?>