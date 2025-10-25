<?php
require_once __DIR__ . '/../conexao.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../index.php');
    exit;
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
    $codigo = $_POST['codigo'] ?? ''; // Novo campo opcional
    $id_tipo_ben = $_POST['id_tipo_ben'] ?? '';
    $tipo_ben = $_POST['tipo_ben'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $id_dependencia = $_POST['id_dependencia'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 1;
    $possui_nota = isset($_POST['possui_nota']) ? 1 : 0;
    $imprimir_14_1 = isset($_POST['imprimir_14_1']) ? 1 : 0;
    
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
    
    if (empty($id_dependencia)) {
        $erros[] = "A dependência é obrigatória";
    }
    
    if (empty($quantidade) || $quantidade < 1) {
        $erros[] = "A quantidade deve ser pelo menos 1";
    }
    
    // Se não há erros, inserir no banco
    if (empty($erros)) {
        try {
            // Buscar dados para montar a descrição completa
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
            
            // Montar descrição completa
            $descricao_completa = $quantidade . "x [" . $tipo_bem['codigo'] . " - " . $tipo_bem['descricao'] . "] " . $tipo_ben . " - " . $complemento . " - (" . $dependencia['descricao'] . ")";
            
            $sql_inserir = "INSERT INTO produtos_cadastro 
                           (id_planilha, codigo, id_tipo_ben, tipo_ben, complemento, id_dependencia, quantidade, descricao_completa, possui_nota, imprimir_14_1) 
                           VALUES 
                           (:id_planilha, :codigo, :id_tipo_ben, :tipo_ben, :complemento, :id_dependencia, :quantidade, :descricao_completa, :possui_nota, :imprimir_14_1)";
            
            $stmt_inserir = $conexao->prepare($sql_inserir);
            $stmt_inserir->bindValue(':id_planilha', $id_planilha);
            $stmt_inserir->bindValue(':codigo', !empty($codigo) ? $codigo : null);
            $stmt_inserir->bindValue(':id_tipo_ben', $id_tipo_ben);
            $stmt_inserir->bindValue(':tipo_ben', $tipo_ben);
            $stmt_inserir->bindValue(':complemento', $complemento);
            $stmt_inserir->bindValue(':id_dependencia', $id_dependencia);
            $stmt_inserir->bindValue(':quantidade', $quantidade);
            $stmt_inserir->bindValue(':descricao_completa', $descricao_completa);
            $stmt_inserir->bindValue(':possui_nota', $possui_nota);
            $stmt_inserir->bindValue(':imprimir_14_1', $imprimir_14_1);
            
            $stmt_inserir->execute();            // Gerar parâmetros de retorno para manter os filtros
            $parametros_retorno = gerarParametrosFiltro();
            
            // Redirecionar de volta para a lista (caminho correto)
            $base_path = str_replace('/CRUD/CREATE', '', dirname($_SERVER['SCRIPT_NAME']));
            header('Location: ' . $base_path . '/app/views/produtos/read-produto.php?id=' . $id_planilha . ($parametros_retorno ? '&' . $parametros_retorno : ''));
            exit;
            
        } catch (Exception $e) {
            $erros[] = "Erro ao cadastrar produto: " . $e->getMessage();
        }
    }
}

// Função para gerar parâmetros de filtro (similar à do read)
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