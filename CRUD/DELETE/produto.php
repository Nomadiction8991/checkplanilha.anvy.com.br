<?php
require_once '../conexao.php';

$id_produto = $_GET['id'] ?? null;
$id_planilha = $_GET['id_planilha'] ?? null;

if (!$id_produto || !$id_planilha) {
    header('Location: ../../VIEW/menu-create.php');
    exit;
}

// Buscar dados do produto
try {
    $sql_produto = "SELECT pc.*, tb.codigo as tipo_codigo, tb.descricao as tipo_descricao, d.descricao as dependencia_descricao 
                   FROM produtos_cadastro pc
                   LEFT JOIN tipos_bens tb ON pc.id_tipo_ben = tb.id
                   LEFT JOIN dependencias d ON pc.id_dependencia = d.id
                   WHERE pc.id = :id AND pc.id_planilha = :id_planilha";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id', $id_produto);
    $stmt_produto->bindValue(':id_planilha', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        header('Location: ../VIEW/read-produto.php?id_planilha=' . $id_planilha);
        exit;
    }
} catch (Exception $e) {
    die("Erro ao carregar produto: " . $e->getMessage());
}

// Processar a exclusão quando confirmada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql_excluir = "DELETE FROM produtos_cadastro WHERE id = :id AND id_planilha = :id_planilha";
        $stmt_excluir = $conexao->prepare($sql_excluir);
        $stmt_excluir->bindValue(':id', $id_produto);
        $stmt_excluir->bindValue(':id_planilha', $id_planilha);
        $stmt_excluir->execute();
        
        // Gerar parâmetros de retorno para manter os filtros
        $parametros_retorno = gerarParametrosFiltro();
        
        // Redirecionar de volta para a lista
        header('Location: ../VIEW/read-produto.php?id_planilha=' . $id_planilha . $parametros_retorno);
        exit;
        
    } catch (Exception $e) {
        $erros[] = "Erro ao excluir produto: " . $e->getMessage();
    }
}

// Função para gerar parâmetros de filtro
function gerarParametrosFiltro() {
    $params = '';
    
    if (!empty($_GET['pesquisa_id'])) {
        $params .= '&pesquisa_id=' . urlencode($_GET['pesquisa_id']);
    }
    if (!empty($_GET['filtro_tipo_ben'])) {
        $params .= '&filtro_tipo_ben=' . urlencode($_GET['filtro_tipo_ben']);
    }
    if (!empty($_GET['filtro_bem'])) {
        $params .= '&filtro_bem=' . urlencode($_GET['filtro_bem']);
    }
    if (!empty($_GET['filtro_complemento'])) {
        $params .= '&filtro_complemento=' . urlencode($_GET['filtro_complemento']);
    }
    if (!empty($_GET['filtro_dependencia'])) {
        $params .= '&filtro_dependencia=' . urlencode($_GET['filtro_dependencia']);
    }
    if (!empty($_GET['filtro_status'])) {
        $params .= '&filtro_status=' . urlencode($_GET['filtro_status']);
    }
    if (!empty($_GET['pagina'])) {
        $params .= '&pagina=' . urlencode($_GET['pagina']);
    }
    
    return $params;
}
?>