<?php
 // AutenticaÃ§Ã£o
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Receber parÃ¢metros via GET - AGORA USANDO ID
$id_produto = isset($_GET['id_produto']) ? (int) $_GET['id_produto'] : null;
$comum_id = isset($_GET['comum_id']) ? (int) $_GET['comum_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : null);

// Receber filtros
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// ValidaÃ§Ã£o dos parÃ¢metros obrigatÃ³rios
if (!$id_produto || !$comum_id) {
    $query_string = http_build_query([
        'id' => $comum_id,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'ParÃ¢metros invÃ¡lidos para acessar a pÃ¡gina'
    ]);
    header('Location: ../planilhas/planilha_visualizar.php?' . $query_string);
    exit;
}

// Inicializar variÃ¡veis
$mensagem = '';
$tipo_mensagem = '';
$produto = [];
$check = [
    'checado' => 0,
    'observacoes' => '',
    'dr' => 0,
    'imprimir' => 0
];

// Buscar dados do produto POR ID
try {
    $sql_produto = "SELECT * FROM produtos WHERE id_produto = :id_produto AND comum_id = :comum_id";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id_produto', $id_produto);
    $stmt_produto->bindValue(':comum_id', $comum_id);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        throw new Exception('Produto nÃ£o encontrado na planilha.');
    }
    
    // Preencher informaÃ§Ãµes do check com dados da prÃ³pria tabela produtos
    $check = [
        'checado' => $produto['checado'] ?? 0,
        'observacoes' => $produto['observacao'] ?? '',
        'imprimir' => $produto['imprimir_etiqueta'] ?? 0
    ];
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar o formulÃ¡rio quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    // Receber filtros do POST
    $pagina = $_POST['pagina'] ?? 1;
    $filtro_nome = $_POST['nome'] ?? '';
    $filtro_dependencia = $_POST['dependencia'] ?? '';
    $filtro_codigo = $_POST['filtro_codigo'] ?? '';
    $filtro_status = $_POST['status'] ?? '';
    
    try {
        // Atualizar observaÃ§Ãµes diretamente na tabela produtos - USANDO id_produto
        $sql_update = "UPDATE produtos SET observacao = :observacao WHERE id_produto = :id_produto AND comum_id = :comum_id";
        $stmt_update = $conexao->prepare($sql_update);
        $stmt_update->bindValue(':observacao', $observacoes);
        $stmt_update->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);
        $stmt_update->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt_update->execute();
        
        // REDIRECIONAR PARA view-planilha.php APÃ“S SALVAR
        $query_string = http_build_query([
            'id' => $comum_id,
            'pagina' => $pagina,
            'nome' => $filtro_nome,
            'dependencia' => $filtro_dependencia,
            'codigo' => $filtro_codigo,
            'status' => $filtro_status,
            'sucesso' => 'ObservaÃ§Ãµes salvas com sucesso!'
        ]);
    header('Location: ../planilhas/planilha_visualizar.php?' . $query_string);
        exit;
        
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar observaÃ§Ãµes: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO SALVAR OBSERVAÃ‡Ã•ES: " . $e->getMessage());
    }
}

// FunÃ§Ã£o para gerar URL de retorno com filtros - CORRIGIDA
function getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status) {
    $params = [
        'id' => $id_planilha, // CORRETO: view-planilha.php usa 'id' como parÃ¢metro
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status
    ];
    return '../planilhas/planilha_visualizar.php?' . http_build_query($params);
}
?>


