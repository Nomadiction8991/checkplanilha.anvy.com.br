<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação
require_once PROJECT_ROOT . '/conexao.php';

// Receber parâmetros via GET - AGORA USANDO ID
$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id'] ?? null;

// Receber filtros
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Validação dos parâmetros obrigatórios
if (!$id_produto || !$id_planilha) {
    $query_string = http_build_query([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Parâmetros inválidos para acessar a página'
    ]);
    header('Location: ../planilhas/view-planilha.php?' . $query_string);
    exit;
}

// Inicializar variáveis
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
    $sql_produto = "SELECT * FROM produtos WHERE id_produto = :id_produto AND planilha_id = :planilha_id";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id_produto', $id_produto);
    $stmt_produto->bindValue(':planilha_id', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado na planilha.');
    }
    
    // Preencher informações do check com dados da própria tabela produtos
    $check = [
        'checado' => $produto['checado'] ?? 0,
        'observacoes' => $produto['observacao'] ?? '',
        'imprimir' => $produto['imprimir_etiqueta'] ?? 0
    ];
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    // Receber filtros do POST
    $pagina = $_POST['pagina'] ?? 1;
    $filtro_nome = $_POST['nome'] ?? '';
    $filtro_dependencia = $_POST['dependencia'] ?? '';
    $filtro_codigo = $_POST['filtro_codigo'] ?? '';
    $filtro_status = $_POST['status'] ?? '';
    
    try {
        // Atualizar observações diretamente na tabela produtos - USANDO id_produto
        $sql_update = "UPDATE produtos SET observacao = :observacao WHERE id_produto = :id_produto AND planilha_id = :planilha_id";
        $stmt_update = $conexao->prepare($sql_update);
        $stmt_update->bindValue(':observacao', $observacoes);
        $stmt_update->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);
        $stmt_update->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
        $stmt_update->execute();
        
        // REDIRECIONAR PARA view-planilha.php APÓS SALVAR
        $query_string = http_build_query([
            'id' => $id_planilha,
            'pagina' => $pagina,
            'nome' => $filtro_nome,
            'dependencia' => $filtro_dependencia,
            'codigo' => $filtro_codigo,
            'status' => $filtro_status,
            'sucesso' => 'Observações salvas com sucesso!'
        ]);
    header('Location: ../planilhas/view-planilha.php?' . $query_string);
        exit;
        
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar observações: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO SALVAR OBSERVAÇÕES: " . $e->getMessage());
    }
}

// Função para gerar URL de retorno com filtros - CORRIGIDA
function getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status) {
    $params = [
        'id' => $id_planilha, // CORRETO: view-planilha.php usa 'id' como parâmetro
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status
    ];
    return '../planilhas/view-planilha.php?' . http_build_query($params);
}
?>