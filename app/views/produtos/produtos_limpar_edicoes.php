<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AutenticaÃƒÂ§ÃƒÂ£o

// ParÃƒÂ¢metros
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
    header('Location: ../planilhas/planilha_visualizar.php?' . $qs);
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
        'erro' => 'ParÃƒÂ¢metros invÃƒÂ¡lidos'
    ]);
}

try {
    // Limpar campos de ediÃƒÂ§ÃƒÂ£o na tabela produtos - USANDO id_produto
    // Importante: usar valores padrÃƒÂ£o vÃƒÂ¡lidos ('' ou 0) pois colunas sÃƒÂ£o NOT NULL em alguns bancos
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
    
    $msg = 'EdiÃƒÂ§ÃƒÂµes limpas com sucesso!';

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
        'erro' => 'Erro ao limpar ediÃƒÂ§ÃƒÂµes: ' . $e->getMessage()
    ]);
}
?>


