<?php
require_once '../conexao.php';

$id_planilha = $_GET['id_planilha'] ?? null;

if (!$id_planilha) {
    header('Location: ../../VIEW/menu-create.php');
    exit;
}

// Parâmetros de pesquisa
$pesquisa_id = $_GET['pesquisa_id'] ?? '';
$pesquisa_descricao = $_GET['pesquisa_descricao'] ?? '';
$filtro_status = $_GET['filtro_status'] ?? '';

// Paginação
$pagina = $_GET['pagina'] ?? 1;
$produtos_por_pagina = 10;
$offset = ($pagina - 1) * $produtos_por_pagina;

// Construir a query base
$sql = "SELECT 
            pc.id,
            pc.tipo_ben,
            pc.complemento,
            pc.possui_nota,
            pc.imprimir_doacao,
            tb.codigo as tipo_codigo,
            tb.descricao as tipo_descricao,
            d.descricao as dependencia_descricao
        FROM produtos_cadastro pc
        LEFT JOIN tipos_bens tb ON pc.id_tipo_ben = tb.id
        LEFT JOIN dependencias d ON pc.id_dependencia = d.id
        WHERE pc.id_planilha = :id_planilha";

$sql_count = "SELECT COUNT(*) as total 
              FROM produtos_cadastro pc
              LEFT JOIN tipos_bens tb ON pc.id_tipo_ben = tb.id
              LEFT JOIN dependencias d ON pc.id_dependencia = d.id
              WHERE pc.id_planilha = :id_planilha";

// Adicionar condições de pesquisa
$condicoes = [];
$params = [':id_planilha' => $id_planilha];

if (!empty($pesquisa_id)) {
    $condicoes[] = "pc.id = :pesquisa_id";
    $params[':pesquisa_id'] = $pesquisa_id;
}

if (!empty($pesquisa_descricao)) {
    $condicoes[] = "(tb.codigo LIKE :pesquisa_descricao OR tb.descricao LIKE :pesquisa_descricao OR pc.tipo_ben LIKE :pesquisa_descricao OR pc.complemento LIKE :pesquisa_descricao OR d.descricao LIKE :pesquisa_descricao)";
    $params[':pesquisa_descricao'] = "%$pesquisa_descricao%";
}

if (!empty($filtro_status)) {
    if ($filtro_status === 'com_nota') {
        $condicoes[] = "pc.possui_nota = 1";
    } elseif ($filtro_status === 'com_doacao') {
        $condicoes[] = "pc.imprimir_doacao = 1";
    } elseif ($filtro_status === 'sem_status') {
        $condicoes[] = "pc.possui_nota = 0 AND pc.imprimir_doacao = 0";
    }
}

// Adicionar condições à query
if (!empty($condicoes)) {
    $sql .= " AND " . implode(" AND ", $condicoes);
    $sql_count .= " AND " . implode(" AND ", $condicoes);
}

// Ordenação e paginação
$sql .= " ORDER BY pc.id DESC LIMIT :limit OFFSET :offset";

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
    
    // Calcular total de páginas - CORREÇÃO AQUI: usar $produtos_por_pagina (sem 's')
    $total_paginas = ceil($total_registros / $produtos_por_pagina);
    
} catch (Exception $e) {
    die("Erro ao carregar produtos: " . $e->getMessage());
}

// Função para gerar parâmetros de filtro para URLs
function gerarParametrosFiltro($incluirPagina = false) {
    $params = [];
    
    if (!empty($_GET['pesquisa_id'])) {
        $params['pesquisa_id'] = $_GET['pesquisa_id'];
    }
    if (!empty($_GET['pesquisa_descricao'])) {
        $params['pesquisa_descricao'] = $_GET['pesquisa_descricao'];
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