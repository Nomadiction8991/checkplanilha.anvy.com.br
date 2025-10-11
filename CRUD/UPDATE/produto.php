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
    $sql_produto = "SELECT * FROM produtos_cadastro WHERE id = :id AND id_planilha = :id_planilha";
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

// Buscar tipos de bens disponíveis
$sql_tipos_bens = "SELECT id, codigo, descricao FROM tipos_bens ORDER BY codigo";
$stmt_tipos = $conexao->prepare($sql_tipos_bens);
$stmt_tipos->execute();
$tipos_bens = $stmt_tipos->fetchAll();

// Buscar dependências disponíveis
$sql_dependencias = "SELECT id, descricao FROM dependencias ORDER BY descricao";
$stmt_deps = $conexao->prepare($sql_dependencias);
$stmt_deps->execute();
$dependencias = $stmt_deps->fetchAll();

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tipo_ben = $_POST['id_tipo_ben'] ?? '';
    $tipo_ben = $_POST['tipo_ben'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $id_dependencia = $_POST['id_dependencia'] ?? '';
    $possui_nota = isset($_POST['possui_nota']) ? 1 : 0;
    $imprimir_doacao = isset($_POST['imprimir_doacao']) ? 1 : 0;
    
    // Validações básicas
    $erros = [];
    
    if (empty($id_tipo_ben)) {
        $erros[] = "O tipo de bem é obrigatório";
    }
    
    if (empty($tipo_ben)) {
        $erros[] = "O bem é obrigatório";
    }
    
    if (empty($complemento)) {
        $erros[] = "O complemento é obrigatório";
    }
    
    // Se não há erros, atualizar no banco
    if (empty($erros)) {
        try {
            $sql_atualizar = "UPDATE produtos_cadastro 
                             SET id_tipo_ben = :id_tipo_ben,
                                 tipo_ben = :tipo_ben,
                                 complemento = :complemento,
                                 id_dependencia = :id_dependencia,
                                 possui_nota = :possui_nota,
                                 imprimir_doacao = :imprimir_doacao
                             WHERE id = :id AND id_planilha = :id_planilha";
            
            $stmt_atualizar = $conexao->prepare($sql_atualizar);
            $stmt_atualizar->bindValue(':id_tipo_ben', $id_tipo_ben);
            $stmt_atualizar->bindValue(':tipo_ben', $tipo_ben);
            $stmt_atualizar->bindValue(':complemento', $complemento);
            $stmt_atualizar->bindValue(':id_dependencia', $id_dependencia ?: null);
            $stmt_atualizar->bindValue(':possui_nota', $possui_nota);
            $stmt_atualizar->bindValue(':imprimir_doacao', $imprimir_doacao);
            $stmt_atualizar->bindValue(':id', $id_produto);
            $stmt_atualizar->bindValue(':id_planilha', $id_planilha);
            
            $stmt_atualizar->execute();
            
            // Gerar parâmetros de retorno para manter os filtros
            $parametros_retorno = gerarParametrosFiltro();
            
            // Redirecionar de volta para a lista
            header('Location: ../VIEW/read-produto.php?id_planilha=' . $id_planilha . $parametros_retorno);
            exit;
            
        } catch (Exception $e) {
            $erros[] = "Erro ao atualizar produto: " . $e->getMessage();
        }
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