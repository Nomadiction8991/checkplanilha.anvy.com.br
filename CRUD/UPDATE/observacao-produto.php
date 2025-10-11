<?php
require_once '../conexao.php';

// Receber parâmetros via GET - AGORA USANDO ID
$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id_planilha'] ?? null;

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
    header('Location: ../../VIEW/view-planilha.php?' . $query_string);
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
            
            $mensagem = "Observações atualizadas com sucesso!";
            $tipo_mensagem = 'success';
        } else {
            // Inserir novo registro
            $sql_insert = "INSERT INTO produtos_check (produto_id, observacoes) VALUES (:produto_id, :observacoes)";
            $stmt_insert = $conexao->prepare($sql_insert);
            $stmt_insert->bindValue(':produto_id', $id_produto);
            $stmt_insert->bindValue(':observacoes', $observacoes);
            $stmt_insert->execute();
            
            $mensagem = "Observações salvas com sucesso!";
            $tipo_mensagem = 'success';
        }
        
        // Atualizar dados do check após salvar
        $stmt_check->execute();
        $check_result = $stmt_check->fetch();
        if ($check_result) {
            $check = $check_result;
        }
        
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar observações: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO SALVAR OBSERVAÇÕES: " . $e->getMessage());
    }
}

// Função para gerar URL de retorno com filtros
function getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status) {
    $params = [
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status
    ];
    return '../../VIEW/view-planilha.php?' . http_build_query($params);
}

// Incluir o arquivo de visualização
include '../../VIEW/observacao-produto.php';
?>