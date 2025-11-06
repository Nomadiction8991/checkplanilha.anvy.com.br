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

// Query base usando apenas a nova tabela de produtos
$sql_base = "SELECT 
                p.id,
                p.codigo,
                p.nome,
                p.nome_editado,
                p.dependencia,
                p.dependencia_editada,
                p.observacoes,
                COALESCE(p.checado, 0) AS checado,
                COALESCE(p.dr, 0) AS dr,
                COALESCE(p.imprimir, 0) AS imprimir,
                COALESCE(p.editado, 0) AS editado
             FROM produtos p
             WHERE p.planilha_id = :id_planilha";

$params = [':id_planilha' => $id_planilha];

if ($filtro_nome !== '') {
    $sql_base .= " AND (p.nome LIKE :nome OR p.nome_editado LIKE :nome)";
    $params[':nome'] = '%' . $filtro_nome . '%';
}

if ($filtro_dependencia !== '') {
    // Considera tanto a dependência original quanto uma possível edição pendente
    $sql_base .= " AND (
        p.dependencia LIKE :dependencia OR
        p.dependencia_editada LIKE :dependencia
    )";
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
            $sql_base .= " AND (p.observacoes IS NOT NULL AND p.observacoes <> '')";
            break;
        case 'etiqueta':
            $sql_base .= " AND COALESCE(p.imprimir, 0) = 1";
            break;
        case 'pendente':
            $sql_base .= " AND (COALESCE(p.checado, 0) = 0
                                 AND (p.observacoes IS NULL OR p.observacoes = '')
                                 AND COALESCE(p.dr, 0) = 0
                                 AND COALESCE(p.imprimir, 0) = 0
                                 AND COALESCE(p.editado, 0) = 0)";
            break;
        case 'dr':
            $sql_base .= " AND COALESCE(p.dr, 0) = 1";
            break;
        case 'editado':
            $sql_base .= " AND COALESCE(p.editado, 0) = 1";
            break;
    }
}

// Total de registros para paginação
$sql_count = "SELECT COUNT(*) AS total FROM (" . $sql_base . ") AS produtos_filtrados";
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
$sql_dados = $sql_base . " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";
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
                        SELECT p.dependencia AS dep
                        FROM produtos p
                        WHERE p.planilha_id = :id_dep_original
                          AND p.dependencia IS NOT NULL
                          AND p.dependencia <> ''
                        UNION ALL
                        SELECT p.dependencia_editada AS dep
                        FROM produtos p
                        WHERE p.planilha_id = :id_dep_editada
                          AND p.dependencia_editada IS NOT NULL
                          AND p.dependencia_editada <> ''
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