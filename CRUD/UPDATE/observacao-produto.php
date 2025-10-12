<?php
require_once '../CRUD/conexao.php';

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
    header('Location: view-planilha.php?' . $query_string);
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
    $sql_produto = "SELECT * FROM produtos WHERE id = :id_produto AND id_planilha = :id_planilha";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id_produto', $id_produto);
    $stmt_produto->bindValue(':id_planilha', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado na planilha.');
    }
    
    // Buscar dados do check (se existir)
    $sql_check = "SELECT * FROM produtos_check WHERE produto_id = :produto_id";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bindValue(':produto_id', $id_produto);
    $stmt_check->execute();
    $check_result = $stmt_check->fetch();

    // Se existir registro, atualizar array $check
    if ($check_result) {
        $check = $check_result;
    }
    
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
        // Verificar se já existe registro na tabela produtos_check
        $sql_verificar = "SELECT COUNT(*) as total FROM produtos_check WHERE produto_id = :produto_id";
        $stmt_verificar = $conexao->prepare($sql_verificar);
        $stmt_verificar->bindValue(':produto_id', $id_produto);
        $stmt_verificar->execute();
        $existe_registro = $stmt_verificar->fetch()['total'] > 0;

        if ($existe_registro) {
            // Atualizar registro existente
            $sql_update = "UPDATE produtos_check SET observacoes = :observacoes WHERE produto_id = :produto_id";
            $stmt_update = $conexao->prepare($sql_update);
            $stmt_update->bindValue(':observacoes', $observacoes);
            $stmt_update->bindValue(':produto_id', $id_produto);
            $stmt_update->execute();
        } else {
            // Inserir novo registro
            $sql_insert = "INSERT INTO produtos_check (produto_id, observacoes) VALUES (:produto_id, :observacoes)";
            $stmt_insert = $conexao->prepare($sql_insert);
            $stmt_insert->bindValue(':produto_id', $id_produto);
            $stmt_insert->bindValue(':observacoes', $observacoes);
            $stmt_insert->execute();
        }
        
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
        header('Location: view-planilha.php?' . $query_string);
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
    return 'view-planilha.php?' . http_build_query($params);
}
?>