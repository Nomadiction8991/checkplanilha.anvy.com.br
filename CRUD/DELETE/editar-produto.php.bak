<?php
require_once __DIR__ . '/../conexao.php';

// Receber parâmetros
$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id'] ?? null;

// Receber filtros para redirecionamento
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Validação
if (!$id_produto || !$id_planilha) {
    $query_string = http_build_query([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Parâmetros inválidos'
    ]);
    header('Location: ../../VIEW/view-planilha.php?' . $query_string);
    exit;
}

try {
    // Verificar se existe registro na tabela produtos_check
    $sql_verificar = "SELECT COUNT(*) as total FROM produtos_check WHERE produto_id = :produto_id";
    $stmt_verificar = $conexao->prepare($sql_verificar);
    $stmt_verificar->bindValue(':produto_id', $id_produto);
    $stmt_verificar->execute();
    $existe_registro = $stmt_verificar->fetch()['total'] > 0;

    if ($existe_registro) {
        // Atualizar o registro, limpando os campos e definindo editado = 0
        $sql_update = "UPDATE produtos_check SET nome = NULL, dependencia = NULL, imprimir = 0, editado = 0 WHERE produto_id = :produto_id";
        $stmt_update = $conexao->prepare($sql_update);
        $stmt_update->bindValue(':produto_id', $id_produto);
        $stmt_update->execute();
        
        $sucesso = 'Edições limpas com sucesso!';
    } else {
        $sucesso = 'Nenhuma edição encontrada para limpar.';
    }

    // Redirecionar de volta para a view-planilha
    $query_string = http_build_query([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'sucesso' => $sucesso
    ]);
    header('Location: ../../VIEW/view-planilha.php?' . $query_string);
    exit;
    
} catch (Exception $e) {
    $query_string = http_build_query([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Erro ao limpar edições: ' . $e->getMessage()
    ]);
    header('Location: ../../VIEW/view-planilha.php?' . $query_string);
    exit;
}
?>