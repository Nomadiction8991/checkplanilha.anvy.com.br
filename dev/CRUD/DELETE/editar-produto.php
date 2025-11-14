<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação
require_once PROJECT_ROOT . '/conexao.php';

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
    // Limpar as edições diretamente na tabela produtos
    $sql_update = "UPDATE produtos 
                   SET nome_editado = NULL, 
                       dependencia_editada = NULL, 
                       imprimir = 0, 
                       editado = 0 
                   WHERE id = :produto_id";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bindValue(':produto_id', $id_produto, PDO::PARAM_INT);
    $stmt_update->execute();
    
    $sucesso = $stmt_update->rowCount() > 0 
        ? 'Edições limpas com sucesso!' 
        : 'Nenhuma edição encontrada para limpar.';

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