<?php
 // AutenticaÃ§Ã£o
require_once dirname(__DIR__, 2) . '/bootstrap.php';

$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id'] ?? null;

if (!$id_produto || !$id_planilha) {
    header('Location: ../index.php');
    exit;
}

// Buscar dados do produto
try {
    $sql_produto = "SELECT * FROM produtos WHERE id_produto = :id AND planilha_id = :id_planilha";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id', $id_produto);
    $stmt_produto->bindValue(':id_planilha', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        header('Location: /dev/app/views/produtos/produtos_listar.php?id=' . $id_planilha);
        exit;
    }
} catch (Exception $e) {
    die("Erro ao carregar produto: " . $e->getMessage());
}

// Buscar tipos de bens disponÃ­veis
$sql_tipos_bens = "SELECT id, codigo, descricao FROM tipos_bens ORDER BY codigo";
$stmt_tipos = $conexao->prepare($sql_tipos_bens);
$stmt_tipos->execute();
$tipos_bens = $stmt_tipos->fetchAll();

// Buscar dependÃªncias disponÃ­veis
$sql_dependencias = "SELECT id, descricao FROM dependencias ORDER BY descricao";
$stmt_deps = $conexao->prepare($sql_dependencias);
$stmt_deps->execute();
$dependencias = $stmt_deps->fetchAll();

// Processar o formulÃ¡rio quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? ''; // Novo campo opcional
    $id_tipo_ben = $_POST['id_tipo_ben'] ?? '';
    $tipo_ben = $_POST['tipo_ben'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $id_dependencia = $_POST['id_dependencia'] ?? '';
    
    $imprimir_14_1 = isset($_POST['imprimir_14_1']) ? 1 : 0;
    
    // ValidaÃ§Ãµes bÃ¡sicas
    $erros = [];
    
    if (empty($id_tipo_ben)) {
        $erros[] = "O tipo de bem Ã© obrigatÃ³rio";
    }
    
    if (empty($tipo_ben)) {
        $erros[] = "O bem Ã© obrigatÃ³rio";
    }
    
    if (empty($complemento)) {
        $erros[] = "O complemento Ã© obrigatÃ³rio";
    }
    
    if (empty($id_dependencia)) {
        $erros[] = "A dependÃªncia Ã© obrigatÃ³ria";
    }
    
    // Se nÃ£o hÃ¡ erros, atualizar no banco
    if (empty($erros)) {
        try {
            // Buscar dados para montar a descriÃ§Ã£o completa
            $sql_tipo = "SELECT codigo, descricao FROM tipos_bens WHERE id = :id_tipo_ben";
            $stmt_tipo = $conexao->prepare($sql_tipo);
            $stmt_tipo->bindValue(':id_tipo_ben', $id_tipo_ben);
            $stmt_tipo->execute();
            $tipo_bem = $stmt_tipo->fetch();
            
            $sql_dep = "SELECT descricao FROM dependencias WHERE id = :id_dependencia";
            $stmt_dep = $conexao->prepare($sql_dep);
            $stmt_dep->bindValue(':id_dependencia', $id_dependencia);
            $stmt_dep->execute();
            $dependencia = $stmt_dep->fetch();
            
            // Montar descriÃ§Ã£o completa (mantendo quantidade = 1)
            $descricao_completa = "1x [" . $tipo_bem['codigo'] . " - " . $tipo_bem['descricao'] . "] " . $tipo_ben . " - " . $complemento . " - (" . $dependencia['descricao'] . ")";
            
            $sql_atualizar = "UPDATE produtos 
                             SET codigo = :codigo,
                                 tipo_bem_id = :tipo_bem_id,
                                 bem = :bem,
                                 complemento = :complemento,
                                 dependencia_id = :dependencia_id,
                                 descricao_completa = :descricao_completa,
                                 imprimir_14_1 = :imprimir_14_1
                             WHERE id_produto = :id AND planilha_id = :id_planilha";
            
            $stmt_atualizar = $conexao->prepare($sql_atualizar);
            $stmt_atualizar->bindValue(':codigo', !empty($codigo) ? $codigo : null);
            $stmt_atualizar->bindValue(':tipo_bem_id', $id_tipo_ben);
            $stmt_atualizar->bindValue(':bem', $tipo_ben);
            $stmt_atualizar->bindValue(':complemento', $complemento);
            $stmt_atualizar->bindValue(':dependencia_id', $id_dependencia);
            $stmt_atualizar->bindValue(':descricao_completa', $descricao_completa);
            $stmt_atualizar->bindValue(':imprimir_14_1', $imprimir_14_1);
            $stmt_atualizar->bindValue(':id', $id_produto);
            $stmt_atualizar->bindValue(':id_planilha', $id_planilha);
            
            $stmt_atualizar->execute();
            
            // Gerar parÃ¢metros de retorno para manter os filtros
            $parametros_retorno = gerarParametrosFiltro();
            
            // Redirecionar de volta para a lista (caminho relativo ao document root)
            header('Location: /dev/app/views/produtos/produtos_listar.php?id=' . $id_planilha . ($parametros_retorno ? '&' . $parametros_retorno : ''));
            exit;
            
        } catch (Exception $e) {
            $erros[] = "Erro ao atualizar produto: " . $e->getMessage();
        }
    }
}

// FunÃ§Ã£o para gerar parÃ¢metros de filtro
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


