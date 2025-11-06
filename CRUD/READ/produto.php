<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../index.php');
    exit;
}

// Parâmetros de pesquisa
$pesquisa_id = $_GET['pesquisa_id'] ?? '';
$filtro_tipo_ben = $_GET['filtro_tipo_ben'] ?? '';
$filtro_bem = $_GET['filtro_bem'] ?? ''; // NOME ALTERADO
$filtro_complemento = $_GET['filtro_complemento'] ?? '';
$filtro_dependencia = $_GET['filtro_dependencia'] ?? '';
$filtro_status = $_GET['filtro_status'] ?? '';

// Paginação
$pagina = $_GET['pagina'] ?? 1;
$produtos_por_pagina = 20;
$offset = ($pagina - 1) * $produtos_por_pagina;

// Construir a query base
$sql = "SELECT 
            pc.id,
            pc.codigo,
            pc.descricao_completa,
            pc.quantidade,
            pc.tipo_ben,
            pc.complemento,
            pc.condicao_141,
            pc.imprimir_14_1,
            d.descricao as dependencia_descricao
        FROM produtos_cadastro pc
        LEFT JOIN dependencias d ON pc.id_dependencia = d.id
        WHERE pc.id_planilha = :id_planilha";

$sql_count = "SELECT COUNT(*) as total 
              FROM produtos_cadastro pc
              LEFT JOIN dependencias d ON pc.id_dependencia = d.id
              WHERE pc.id_planilha = :id_planilha";

// Buscar tipos de bens disponíveis para o select (SEM REPETIÇÕES)
$sql_tipos_bens = "SELECT DISTINCT tb.id, tb.codigo, tb.descricao 
                   FROM produtos_cadastro pc
                   JOIN tipos_bens tb ON pc.id_tipo_ben = tb.id
                   WHERE pc.id_planilha = :id_planilha
                   ORDER BY tb.codigo";

// Buscar códigos bem disponíveis para o select (SEM REPETIÇÕES)
$sql_bem_codigos = "SELECT DISTINCT pc.tipo_ben 
                    FROM produtos_cadastro pc
                    WHERE pc.id_planilha = :id_planilha 
                    AND pc.tipo_ben IS NOT NULL 
                    AND pc.tipo_ben != ''
                    ORDER BY pc.tipo_ben";

// Buscar dependências disponíveis para o select (SEM REPETIÇÕES)
$sql_dependencias = "SELECT DISTINCT d.id, d.descricao 
                    FROM produtos_cadastro pc
                    LEFT JOIN dependencias d ON pc.id_dependencia = d.id
                    WHERE pc.id_planilha = :id_planilha AND d.id IS NOT NULL
                    ORDER BY d.descricao";

try {
    // Buscar tipos de bens (SEM REPETIÇÕES)
    $stmt_tipos = $conexao->prepare($sql_tipos_bens);
    $stmt_tipos->bindValue(':id_planilha', $id_planilha);
    $stmt_tipos->execute();
    $tipos_bens = $stmt_tipos->fetchAll();

    // Buscar códigos bem (SEM REPETIÇÕES)
    $stmt_bem = $conexao->prepare($sql_bem_codigos);
    $stmt_bem->bindValue(':id_planilha', $id_planilha);
    $stmt_bem->execute();
    $bem_codigos = $stmt_bem->fetchAll();

    // Buscar dependências (SEM REPETIÇÕES)
    $stmt_deps = $conexao->prepare($sql_dependencias);
    $stmt_deps->bindValue(':id_planilha', $id_planilha);
    $stmt_deps->execute();
    $dependencias = $stmt_deps->fetchAll();
} catch (Exception $e) {
    $tipos_bens = [];
    $bem_codigos = [];
    $dependencias = [];
}

// Adicionar condições de pesquisa
$condicoes = [];
$params = [':id_planilha' => $id_planilha];

if (!empty($pesquisa_id)) {
    $condicoes[] = "pc.id = :pesquisa_id";
    $params[':pesquisa_id'] = $pesquisa_id;
}

if (!empty($filtro_tipo_ben)) {
    $condicoes[] = "pc.id_tipo_ben = :filtro_tipo_ben";
    $params[':filtro_tipo_ben'] = $filtro_tipo_ben;
}

// FILTRO BEM (nome alterado)
if (!empty($filtro_bem)) {
    $condicoes[] = "pc.tipo_ben = :filtro_bem";
    $params[':filtro_bem'] = $filtro_bem;
}

if (!empty($filtro_complemento)) {
    $condicoes[] = "pc.complemento LIKE :filtro_complemento";
    $params[':filtro_complemento'] = "%$filtro_complemento%";
}

if (!empty($filtro_dependencia)) {
    $condicoes[] = "pc.id_dependencia = :filtro_dependencia";
    $params[':filtro_dependencia'] = $filtro_dependencia;
}

if (!empty($filtro_status)) {
    if ($filtro_status === 'com_nota') {
        $condicoes[] = "(pc.condicao_141 = 1 OR pc.condicao_141 = 3)";
    } elseif ($filtro_status === 'com_14_1') {
        $condicoes[] = "pc.imprimir_14_1 = 1";
    } elseif ($filtro_status === 'sem_status') {
        $condicoes[] = "(pc.condicao_141 IS NULL OR pc.condicao_141 = 2) AND pc.imprimir_14_1 = 0";
    }
}

// Adicionar condições à query
if (!empty($condicoes)) {
    $sql .= " AND " . implode(" AND ", $condicoes);
    $sql_count .= " AND " . implode(" AND ", $condicoes);
}

// Ordenação por ID do menor para o maior
$sql .= " ORDER BY pc.id ASC LIMIT :limit OFFSET :offset";

try {
    // Contar total de registros
    $stmt_count = $conexao->prepare($sql_count);
    foreach ($params as $key => $value) {
        $stmt_count->bindValue($key, $value);
    }
    $stmt_count->execute();
    $total_registros = $stmt_count->fetch()['total'];
    
    // Buscar produtos
    $stmt = $conexao->prepare($sql);
    
    // Bind dos parâmetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $produtos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $produtos = $stmt->fetchAll();
    
    // Calcular total de páginas
    $total_paginas = ceil($total_registros / $produtos_por_pagina);
    
} catch (Exception $e) {
    die("Erro ao carregar produtos: " . $e->getMessage());
}

// Função para gerar parâmetros de filtro para URLs
// Função para gerar parâmetros de filtro para URLs
function gerarParametrosFiltro($incluirPagina = false) {
    $params = [];
    
    if (!empty($_GET['pesquisa_id'])) {
        $params['pesquisa_id'] = $_GET['pesquisa_id'];
    }
    if (!empty($_GET['filtro_tipo_ben'])) {
        $params['filtro_tipo_ben'] = $_GET['filtro_tipo_ben'];
    }
    if (!empty($_GET['filtro_bem'])) {
        $params['filtro_bem'] = $_GET['filtro_bem'];
    }
    if (!empty($_GET['filtro_complemento'])) {
        $params['filtro_complemento'] = $_GET['filtro_complemento'];
    }
    if (!empty($_GET['filtro_dependencia'])) {
        $params['filtro_dependencia'] = $_GET['filtro_dependencia'];
    }
    if (!empty($_GET['filtro_status'])) {
        $params['filtro_status'] = $_GET['filtro_status'];
    }
    if ($incluirPagina && !empty($_GET['pagina'])) {
        $params['pagina'] = $_GET['pagina'];
    }
    
    return http_build_query($params);
}
// As variáveis estarão disponíveis para o HTML
?>