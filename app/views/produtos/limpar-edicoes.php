<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AutenticaÃ§Ã£o

// ParÃ¢metros
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
        'erro' => 'ParÃ¢metros invÃ¡lidos'
    ]);
}

try {
    // Limpar campos de ediÃ§Ã£o na tabela produtos - USANDO id_produto
    // Importante: usar valores padrÃ£o vÃ¡lidos ('' ou 0) pois colunas sÃ£o NOT NULL em alguns bancos
    $sql_update = "UPDATE produtos 
                   SET editado_tipo_bem_id = 0,
                       editado_bem = '',
                       editado_complemento = '',
                       editado_dependencia_id = 0,
                       editado_descricao_completa = '',
                       imprimir_etiqueta = 0,
                       editado = 0
                   WHERE id_produto = :id_produto 
                     AND planilha_id = :planilha_id";
    
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bindValue(':id_produto', $id_produto);
    $stmt_update->bindValue(':planilha_id', $id_planilha);
    $stmt_update->execute();
    
    $msg = 'EdiÃ§Ãµes limpas com sucesso!';

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
        'erro' => 'Erro ao limpar ediÃ§Ãµes: ' . $e->getMessage()
    ]);
}
?>

