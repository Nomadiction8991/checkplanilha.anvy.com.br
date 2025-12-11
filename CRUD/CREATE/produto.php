<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

$id_planilha = isset($_GET['planilha_id']) ? (int) $_GET['planilha_id'] : (isset($_GET['id']) ? (int) $_GET['id'] : null);

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
    $multiplicador = $_POST['multiplicador'] ?? 1;
    
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
    
    if (empty($multiplicador) || $multiplicador < 1) {
        $erros[] = "O multiplicador deve ser pelo menos 1";
    }
    
    // Campos de Nota Fiscal e Condição 14.1 foram removidos do cadastro manual

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
            
            // Converter multiplicador para inteiro
            $multiplicador = (int)$multiplicador;
            
            // Inserir múltiplos produtos conforme o multiplicador (agora na tabela produtos)
            // Campos padrão para novo cadastro: novo=1, checado=1, imprimir_etiqueta=1, editado=0, ativo=1
            $administrador_acessor_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
            $sql_inserir = "INSERT INTO produtos (
                           planilha_id, codigo, descricao_completa, editado_descricao_completa,
                           tipo_bem_id, editado_tipo_bem_id, bem, editado_bem,
                           complemento, editado_complemento, dependencia_id, editado_dependencia_id,
                           checado, editado, imprimir_etiqueta, imprimir_14_1,
                           observacao, ativo, novo, condicao_14_1, administrador_acessor_id
                           ) VALUES (
                           :planilha_id, :codigo, :descricao_completa, '',
                           :id_tipo_bem, 0, :tipo_bem, '',
                           :complemento, '', :id_dependencia, 0,
                           1, 0, 1, :imprimir_14_1,
                           '', 1, 1, :condicao_14_1, :administrador_acessor_id
                           )";
            
            $stmt_inserir = $conexao->prepare($sql_inserir);
            
            // Criar cada um dos registros (quantidade de 1 unidade)
            for ($i = 0; $i < $multiplicador; $i++) {
                // Montar descrição completa (quantidade sempre será 1)
                $descricao_completa = "1x [" . $tipo_bem['codigo'] . " - " . $tipo_bem['descricao'] . "] " . $tipo_ben . " - " . $complemento . " - (" . $dependencia['descricao'] . ")";
                
                // Corrige placeholder: na query é :planilha_id (antes usava :id_planilha causando HY093)
                $stmt_inserir->bindValue(':planilha_id', $id_planilha);
                $stmt_inserir->bindValue(':codigo', !empty($codigo) ? $codigo : null);
                $stmt_inserir->bindValue(':id_tipo_bem', $id_tipo_ben);
                $stmt_inserir->bindValue(':tipo_bem', $tipo_ben);
                $stmt_inserir->bindValue(':complemento', $complemento);
                $stmt_inserir->bindValue(':id_dependencia', $id_dependencia);
                $stmt_inserir->bindValue(':descricao_completa', $descricao_completa);
                $stmt_inserir->bindValue(':imprimir_14_1', $imprimir_14_1);
                // Definição padrão para satisfazer NOT NULL no banco (campo não é usado no cadastro manual)
                $stmt_inserir->bindValue(':condicao_14_1', 2, PDO::PARAM_INT);
                $stmt_inserir->bindValue(':administrador_acessor_id', $administrador_acessor_id, $administrador_acessor_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                
                
                $stmt_inserir->execute();
            }
            
            // Gerar parâmetros de retorno para manter os filtros
            $parametros_retorno = gerarParametrosFiltro();
            
            // Redirecionar de volta para a lista (caminho relativo ao document root)
            header('Location: /dev/app/views/produtos/read-produto.php?id=' . $id_planilha . ($parametros_retorno ? '&' . $parametros_retorno : ''));
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
