<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

$id_planilha = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_planilha <= 0) {
    header('Location: ../index.php');
    exit;
}

// Verificar se há mensagem de erro recebida pela URL
$erro = $_GET['erro'] ?? '';
if ($erro !== '') {
    echo "<script>alert('" . addslashes($erro) . "');</script>";
}

// Buscar dados da planilha e, quando existir, a descrição do comum relacionado
try {
    $sql_planilha = "SELECT pl.*, cm.descricao AS comum_descricao
                     FROM planilhas pl
                     LEFT JOIN comums cm ON cm.id = pl.comum_id
                     WHERE pl.id = :id";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':id', $id_planilha, PDO::PARAM_INT);
    $stmt_planilha->execute();
    $planilha = $stmt_planilha->fetch(PDO::FETCH_ASSOC);

    if (!$planilha) {
        throw new Exception('Planilha não encontrada.');
    }
} catch (Exception $e) {
    die('Erro ao carregar planilha: ' . $e->getMessage());
}

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = $pagina > 0 ? $pagina : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

// Filtros recebidos pela tela
$filtro_nome = trim($_GET['nome'] ?? '');
$filtro_dependencia = trim($_GET['dependencia'] ?? '');
$filtro_codigo = trim($_GET['codigo'] ?? '');
$filtro_status = trim($_GET['status'] ?? '');

// Query base usando a tabela produtos com as colunas REAIS do servidor
$sql_base = "SELECT 
                p.id_produto,
                p.codigo,
                p.descricao_completa,
                p.editado_descricao_completa,
                p.complemento,
                p.editado_complemento,
                p.ben,
                p.editado_ben,
                p.tipo_ben_id,
                p.editado_tipo_ben_id,
                p.dependencia_id,
                p.editado_dependencia_id,
                p.observacao,
                COALESCE(p.checado, 0) AS checado,
                COALESCE(p.editado, 0) AS editado,
                COALESCE(p.imprimir_etiqueta, 0) AS imprimir,
                COALESCE(p.imprimir_14_1, 0) AS imprimir_141,
                COALESCE(p.ativo, 1) AS ativo
             FROM produtos p
             WHERE p.planilha_id = :id_planilha";

$params = [':id_planilha' => $id_planilha];

if ($filtro_nome !== '') {
    $sql_base .= " AND (p.descricao_completa LIKE :nome OR p.editado_descricao_completa LIKE :nome)";
    $params[':nome'] = '%' . $filtro_nome . '%';
}

if ($filtro_dependencia !== '') {
    // Filtra por dependencia_id (considerar tanto original quanto editado)
    $sql_base .= " AND (p.dependencia_id LIKE :dependencia OR p.editado_dependencia_id LIKE :dependencia)";
    $params[':dependencia'] = '%' . $filtro_dependencia . '%';
}

if ($filtro_codigo !== '') {
    $codigo_normalizado = preg_replace('/[\s\-\/]/', '', $filtro_codigo);
    $sql_base .= " AND REPLACE(REPLACE(REPLACE(p.codigo, ' ', ''), '-', ''), '/', '') LIKE :codigo";
    $params[':codigo'] = '%' . $codigo_normalizado . '%';
}

if ($filtro_status !== '') {
    switch ($filtro_status) {
        case 'checado':
            $sql_base .= " AND COALESCE(p.checado, 0) = 1";
            break;
        case 'observacao':
            $sql_base .= " AND (p.observacao IS NOT NULL AND p.observacao <> '')";
            break;
        case 'etiqueta':
            $sql_base .= " AND COALESCE(p.imprimir_etiqueta, 0) = 1";
            break;
        case 'pendente':
            $sql_base .= " AND (COALESCE(p.checado, 0) = 0
                                 AND (p.observacao IS NULL OR p.observacao = '')
                                 AND COALESCE(p.imprimir_etiqueta, 0) = 0
                                 AND COALESCE(p.editado, 0) = 0)";
            break;
        case 'editado':
            $sql_base .= " AND COALESCE(p.editado, 0) = 1";
            break;
    }
}

// Total de registros para paginação - query COUNT simplificada
$sql_count = "SELECT COUNT(*) AS total 
              FROM produtos p 
              WHERE p.planilha_id = :id_planilha";

// Aplicar os mesmos filtros do $sql_base na query de contagem
if ($filtro_nome !== '') {
    $sql_count .= " AND (p.descricao_completa LIKE :nome OR p.editado_descricao_completa LIKE :nome)";
}
if ($filtro_dependencia !== '') {
    $sql_count .= " AND (p.dependencia_id LIKE :dependencia OR p.editado_dependencia_id LIKE :dependencia)";
}
if ($filtro_codigo !== '') {
    $sql_count .= " AND REPLACE(REPLACE(REPLACE(p.codigo, ' ', ''), '-', ''), '/', '') LIKE :codigo";
}
if ($filtro_status !== '') {
    switch ($filtro_status) {
        case 'checado':
            $sql_count .= " AND COALESCE(p.checado, 0) = 1";
            break;
        case 'observacao':
            $sql_count .= " AND (p.observacao IS NOT NULL AND p.observacao <> '')";
            break;
        case 'etiqueta':
            $sql_count .= " AND COALESCE(p.imprimir_etiqueta, 0) = 1";
            break;
        case 'pendente':
            $sql_count .= " AND (COALESCE(p.checado, 0) = 0
                                 AND (p.observacao IS NULL OR p.observacao = '')
                                 AND COALESCE(p.imprimir_etiqueta, 0) = 0
                                 AND COALESCE(p.editado, 0) = 0)";
            break;
        case 'editado':
            $sql_count .= " AND COALESCE(p.editado, 0) = 1";
            break;
    }
}

$stmt_count = $conexao->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt_count->execute();
$total_registros = (int)$stmt_count->fetchColumn();
$total_paginas = (int)ceil($total_registros / $limite);

if ($total_paginas > 0 && $pagina > $total_paginas) {
    $pagina = $total_paginas;
    $offset = ($pagina - 1) * $limite;
}

// Busca efetiva dos produtos com ordenação e limites
$sql_dados = $sql_base . " ORDER BY p.id_produto DESC LIMIT :limite OFFSET :offset";
$stmt = $conexao->prepare($sql_dados);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dependências únicas para preencher o select de filtros
try {
    $sql_filtros = "SELECT DISTINCT dep FROM (
                        SELECT p.dependencia_id AS dep
                        FROM produtos p
                        WHERE p.planilha_id = :id_dep_original
                          AND p.dependencia_id IS NOT NULL
                          AND p.dependencia_id <> 0
                        UNION ALL
                        SELECT p.editado_dependencia_id AS dep
                        FROM produtos p
                        WHERE p.planilha_id = :id_dep_editada
                          AND p.editado_dependencia_id IS NOT NULL
                          AND p.editado_dependencia_id <> 0
                    ) deps
                    ORDER BY dep";
    $stmt_filtros = $conexao->prepare($sql_filtros);
    $stmt_filtros->bindValue(':id_dep_original', $id_planilha, PDO::PARAM_INT);
    $stmt_filtros->bindValue(':id_dep_editada', $id_planilha, PDO::PARAM_INT);
    $stmt_filtros->execute();
    $dependencia_options = $stmt_filtros->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $dependencia_options = [];
}
?>