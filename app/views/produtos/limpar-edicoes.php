<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/conexao.php';

// Parâmetros
$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id'] ?? null;

// Filtros para retorno
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

function redirectBack($params) {
    $qs = http_build_query($params);
    header('Location: ../planilhas/view-planilha.php?' . $qs);
    exit;
}

if (!$id_produto || !$id_planilha) {
    redirectBack([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Parâmetros inválidos'
    ]);
}

try {
    // Limpar campos de edição na tabela produtos - USANDO id_produto
    $sql_update = "UPDATE produtos 
                   SET editado_descricao_completa = NULL, 
                       editado_complemento = NULL, 
                       editado_ben = NULL, 
                       editado_dependencia_id = NULL, 
                       imprimir_etiqueta = 0, 
                       editado = 0 
                   WHERE id_produto = :id_produto 
                     AND planilha_id = :planilha_id";
    
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bindValue(':id_produto', $id_produto);
    $stmt_update->bindValue(':planilha_id', $id_planilha);
    $stmt_update->execute();
    
    $msg = 'Edições limpas com sucesso!';

    redirectBack([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'sucesso' => $msg
    ]);

} catch (Exception $e) {
    redirectBack([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Erro ao limpar edições: ' . $e->getMessage()
    ]);
}
?>
