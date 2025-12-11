<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../../app/functions/comum_functions.php';

$comum_id = isset($_GET['comum_id']) ? (int)$_GET['comum_id'] : 0;
$planilha_id = isset($_GET['planilha_id']) ? (int)$_GET['planilha_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($planilha_id <= 0 && $comum_id > 0) {
    $planilha_id = resolver_planilha_id_por_comum($conexao, $comum_id) ?? 0;
}

if ($planilha_id <= 0) {
    header('Location: ../index.php');
    exit;
}

if ($comum_id <= 0) {
    $stmtPlanilha = $conexao->prepare('SELECT comum_id FROM planilhas WHERE id = :id');
    $stmtPlanilha->bindValue(':id', $planilha_id, PDO::PARAM_INT);
    $stmtPlanilha->execute();
    $rowPlanilha = $stmtPlanilha->fetch();
    if ($rowPlanilha && !empty($rowPlanilha['comum_id'])) {
        $comum_id = (int) $rowPlanilha['comum_id'];
    }
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
        WHERE p.planilha_id = :planilha_id";

$sql_count = "SELECT COUNT(*) as total 
              FROM produtos p
              LEFT JOIN dependencias d ON p.dependencia_id = d.id
              WHERE p.planilha_id = :planilha_id";

// Buscar tipos de bens disponíveis para o select (SEM REPETIÇÕES)
$sql_tipos_bens = "SELECT DISTINCT tb.id, tb.codigo, tb.descricao 
                   FROM produtos p
                   JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
                   WHERE p.planilha_id = :planilha_id
                   ORDER BY tb.codigo";

// Buscar códigos bem disponíveis para o select (SEM REPETIÇÕES)
$sql_bem_codigos = "SELECT DISTINCT p.bem AS tipo_ben
                    FROM produtos p
                    WHERE p.planilha_id = :planilha_id 
                    AND p.bem IS NOT NULL 
                    AND p.bem != ''
                    ORDER BY p.bem";

// Dependências distintas (por ID)
$sql_dependencias = "SELECT DISTINCT d.id, d.descricao 
                    FROM produtos p
                    LEFT JOIN dependencias d ON p.dependencia_id = d.id
                    WHERE p.planilha_id = :planilha_id AND d.id IS NOT NULL
                    ORDER BY d.descricao";

try {
    // Buscar tipos de bens (SEM REPETIÇÕES)
    $stmt_tipos = $conexao->prepare($sql_tipos_bens);
    $stmt_tipos->bindValue(':planilha_id', $planilha_id);
    $stmt_tipos->execute();
    $tipos_bens = $stmt_tipos->fetchAll();

    // Buscar códigos bem (SEM REPETIÇÕES)
    $stmt_bem = $conexao->prepare($sql_bem_codigos);
    $stmt_bem->bindValue(':planilha_id', $planilha_id);
    $stmt_bem->execute();
    $bem_codigos = $stmt_bem->fetchAll();

    // Buscar dependências (SEM REPETIÇÕES)
    $stmt_deps = $conexao->prepare($sql_dependencias);
    $stmt_deps->bindValue(':planilha_id', $planilha_id);
    $stmt_deps->execute();
    $dependencias = $stmt_deps->fetchAll();
} catch (Exception $e) {
    $tipos_bens = [];
    $bem_codigos = [];
    $dependencias = [];
}

// Adicionar condições de pesquisa
$condicoes = [];
$params = [':planilha_id' => $planilha_id];

if (!empty($pesquisa_id)) {
    $condicoes[] = "p.id_produto = :pesquisa_id";
    $params[':pesquisa_id'] = $pesquisa_id;
}

if (!empty($filtro_tipo_ben)) {
    $condicoes[] = "p.tipo_bem_id = :filtro_tipo_bem";
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

// Adicionar condições à query
if (!empty($condicoes)) {
    $sql .= " AND " . implode(" AND ", $condicoes);
    $sql_count .= " AND " . implode(" AND ", $condicoes);
}

// Ordenação por ID do menor para o maior
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
    if (!empty($_GET['planilha_id'])) {
        $params['planilha_id'] = $_GET['planilha_id'];
    }
    if (!empty($_GET['comum_id'])) {
        $params['comum_id'] = $_GET['comum_id'];
    }
    if ($incluirPagina && !empty($_GET['pagina'])) {
        $params['pagina'] = $_GET['pagina'];
    }
    
    return http_build_query($params);
}
// As variáveis estarão disponíveis para o HTML
?>
