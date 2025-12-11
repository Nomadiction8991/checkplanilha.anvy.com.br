<?php
 // AutenticaÃ§Ã£o
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$comum_id = isset($_GET['comum_id']) ? (int)$_GET['comum_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($comum_id <= 0) {
    header('Location: ../index.php');
    exit;
}

// ParÃ¢metros de pesquisa
$pesquisa_id = $_GET['pesquisa_id'] ?? '';
$filtro_tipo_ben = $_GET['filtro_tipo_ben'] ?? '';
$filtro_bem = $_GET['filtro_bem'] ?? ''; // NOME ALTERADO
$filtro_complemento = $_GET['filtro_complemento'] ?? '';
$filtro_dependencia = $_GET['filtro_dependencia'] ?? '';
$filtro_status = $_GET['filtro_status'] ?? '';

// PaginaÃ§Ã£o
$pagina = $_GET['pagina'] ?? 1;
$produtos_por_pagina = 20;
$offset = ($pagina - 1) * $produtos_por_pagina;

// Construir a query base
$sql = "SELECT 
            p.id_produto AS id,
            p.codigo,
            p.descricao_completa,
            1 AS quantidade,
            p.bem AS tipo_ben,
            p.complemento,
            d.descricao AS dependencia_descricao,
            COALESCE(p.condicao_14_1, 0) AS condicao_141,
            COALESCE(p.imprimir_14_1, 0) AS imprimir_14_1
        FROM produtos p
        LEFT JOIN dependencias d ON p.dependencia_id = d.id
        WHERE p.comum_id = :comum_id";

$sql_count = "SELECT COUNT(*) as total 
              FROM produtos p
              LEFT JOIN dependencias d ON p.dependencia_id = d.id
              WHERE p.comum_id = :comum_id";

// Buscar tipos de bens disponÃ­veis para o select (SEM REPETIÃ‡Ã•ES)
$sql_tipos_bens = "SELECT DISTINCT tb.id, tb.codigo, tb.descricao 
                   FROM produtos p
                   JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                   WHERE p.comum_id = :comum_id
                   ORDER BY tb.codigo";

// Buscar cÃ³digos bem disponÃ­veis para o select (SEM REPETIÃ‡Ã•ES)
$sql_bem_codigos = "SELECT DISTINCT p.bem AS tipo_ben
                    FROM produtos p
                    WHERE p.comum_id = :comum_id 
                    AND p.bem IS NOT NULL 
                    AND p.bem != ''
                    ORDER BY p.bem";

// DependÃªncias distintas (por ID)
$sql_dependencias = "SELECT DISTINCT d.id, d.descricao 
                    FROM produtos p
                    LEFT JOIN dependencias d ON p.dependencia_id = d.id
                    WHERE p.comum_id = :comum_id AND d.id IS NOT NULL
                    ORDER BY d.descricao";

try {
    // Buscar tipos de bens (SEM REPETIÃ‡Ã•ES)
    $stmt_tipos = $conexao->prepare($sql_tipos_bens);
    $stmt_tipos->bindValue(':comum_id', $comum_id);
    $stmt_tipos->execute();
    $tipos_bens = $stmt_tipos->fetchAll();

    // Buscar cÃ³digos bem (SEM REPETIÃ‡Ã•ES)
    $stmt_bem = $conexao->prepare($sql_bem_codigos);
    $stmt_bem->bindValue(':comum_id', $comum_id);
    $stmt_bem->execute();
    $bem_codigos = $stmt_bem->fetchAll();

    // Buscar dependÃªncias (SEM REPETIÃ‡Ã•ES)
    $stmt_deps = $conexao->prepare($sql_dependencias);
    $stmt_deps->bindValue(':comum_id', $comum_id);
    $stmt_deps->execute();
    $dependencias = $stmt_deps->fetchAll();
} catch (Exception $e) {
    $tipos_bens = [];
    $bem_codigos = [];
    $dependencias = [];
}

// Adicionar condiÃ§Ãµes de pesquisa
$condicoes = [];
$params = [':comum_id' => $comum_id];

if (!empty($pesquisa_id)) {
    $condicoes[] = "p.id_produto = :pesquisa_id";
    $params[':pesquisa_id'] = $pesquisa_id;
}

if (!empty($filtro_tipo_ben)) {
    $condicoes[] = "p.tipo_bem_id = :filtro_tipo_ben";
    $params[':filtro_tipo_ben'] = $filtro_tipo_ben;
}

// FILTRO BEM (nome alterado)
if (!empty($filtro_bem)) {
    $condicoes[] = "p.bem = :filtro_bem";
    $params[':filtro_bem'] = $filtro_bem;
}

if (!empty($filtro_complemento)) {
    $condicoes[] = "p.complemento LIKE :filtro_complemento";
    $params[':filtro_complemento'] = "%$filtro_complemento%";
}

if (!empty($filtro_dependencia)) {
    $condicoes[] = "p.dependencia_id = :filtro_dependencia";
    $params[':filtro_dependencia'] = $filtro_dependencia;
}

if (!empty($filtro_status)) {
    if ($filtro_status === 'com_nota') {
        $condicoes[] = "(COALESCE(p.condicao_14_1,0) = 1 OR COALESCE(p.condicao_14_1,0) = 3)";
    } elseif ($filtro_status === 'com_14_1') {
        $condicoes[] = "COALESCE(p.imprimir_14_1,0) = 1";
    } elseif ($filtro_status === 'sem_status') {
        $condicoes[] = "(p.condicao_14_1 IS NULL OR p.condicao_14_1 = 2) AND COALESCE(p.imprimir_14_1,0) = 0";
    }
}

// Adicionar condiÃ§Ãµes Ã  query
if (!empty($condicoes)) {
    $sql .= " AND " . implode(" AND ", $condicoes);
    $sql_count .= " AND " . implode(" AND ", $condicoes);
}

// OrdenaÃ§Ã£o por ID do menor para o maior
$sql .= " ORDER BY p.id_produto ASC LIMIT :limit OFFSET :offset";

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
    
    // Bind dos parÃ¢metros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $produtos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $produtos = $stmt->fetchAll();
    
    // Calcular total de pÃ¡ginas
    $total_paginas = ceil($total_registros / $produtos_por_pagina);
    
} catch (Exception $e) {
    die("Erro ao carregar produtos: " . $e->getMessage());
}

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
    if (!empty($_GET['comum_id'])) {
        $params['comum_id'] = $_GET['comum_id'];
    }
    if ($incluirPagina && !empty($_GET['pagina'])) {
        $params['pagina'] = $_GET['pagina'];
    }
    
    return http_build_query($params);
}
// As variÃ¡veis estarÃ£o disponÃ­veis para o HTML
?>


