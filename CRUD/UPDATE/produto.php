<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id'] ?? null;

if (!$id_produto || !$id_planilha) {
    header('Location: ../index.php');
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
        header('Location: /dev/app/views/produtos/read-produto.php?id=' . $id_planilha);
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
    $codigo = $_POST['codigo'] ?? ''; // Novo campo opcional
    $id_tipo_ben = $_POST['id_tipo_ben'] ?? '';
    $tipo_ben = $_POST['tipo_ben'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $id_dependencia = $_POST['id_dependencia'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 1;
    $condicao_141 = isset($_POST['condicao_141']) && in_array($_POST['condicao_141'], ['1','2','3'], true) ? (int)$_POST['condicao_141'] : null;
    
    // Campos de nota: apenas aceitar quando condicao_141 = 3
    $numero_nota = null;
    $data_emissao = null;
    $valor_nota = null;
    $fornecedor_nota = null;
    
    if ($condicao_141 === 3) {
        $numero_nota = $_POST['numero_nota'] ?? null;
        $data_emissao = $_POST['data_emissao'] ?? null;
        $valor_nota = $_POST['valor_nota'] ?? null;
        $fornecedor_nota = $_POST['fornecedor_nota'] ?? null;
    }
    
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
    
    // Validações da nota quando condicao_141 = 3
    if ($condicao_141 === 3) {
        if (empty($numero_nota)) {
            $erros[] = "O número da nota é obrigatório para a condição selecionada";
        }
        if (empty($data_emissao)) {
            $erros[] = "A data de emissão é obrigatória para a condição selecionada";
        } else {
            // validação simples de data YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_emissao)) {
                $erros[] = "Data de emissão inválida";
            }
        }
        if ($valor_nota === null || $valor_nota === '' || !is_numeric($valor_nota)) {
            $erros[] = "O valor da nota é obrigatório e deve ser numérico para a condição selecionada";
        }
        if (empty($fornecedor_nota)) {
            $erros[] = "O fornecedor é obrigatório para a condição selecionada";
        }
    }
    
    // Se não há erros, atualizar no banco
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
            
            $sql_atualizar = "UPDATE produtos_cadastro 
                             SET codigo = :codigo,
                                 id_tipo_ben = :id_tipo_ben,
                                 tipo_ben = :tipo_ben,
                                 complemento = :complemento,
                                 id_dependencia = :id_dependencia,
                                 quantidade = :quantidade,
                                 descricao_completa = :descricao_completa,
                                 numero_nota = :numero_nota,
                                 data_emissao = :data_emissao,
                                 valor_nota = :valor_nota,
                                 fornecedor_nota = :fornecedor_nota,
                                 imprimir_14_1 = :imprimir_14_1,
                                 condicao_141 = :condicao_141
                             WHERE id = :id AND id_planilha = :id_planilha";
            
            $stmt_atualizar = $conexao->prepare($sql_atualizar);
            $stmt_atualizar->bindValue(':codigo', !empty($codigo) ? $codigo : null);
            $stmt_atualizar->bindValue(':id_tipo_ben', $id_tipo_ben);
            $stmt_atualizar->bindValue(':tipo_ben', $tipo_ben);
            $stmt_atualizar->bindValue(':complemento', $complemento);
            $stmt_atualizar->bindValue(':id_dependencia', $id_dependencia);
            $stmt_atualizar->bindValue(':quantidade', $quantidade);
            $stmt_atualizar->bindValue(':descricao_completa', $descricao_completa);
            $stmt_atualizar->bindValue(':numero_nota', $numero_nota);
            $stmt_atualizar->bindValue(':data_emissao', $data_emissao);
            $stmt_atualizar->bindValue(':valor_nota', $valor_nota);
            $stmt_atualizar->bindValue(':fornecedor_nota', $fornecedor_nota);
            $stmt_atualizar->bindValue(':imprimir_14_1', $imprimir_14_1);
            $stmt_atualizar->bindValue(':condicao_141', $condicao_141, PDO::PARAM_INT);
            $stmt_atualizar->bindValue(':id', $id_produto);
            $stmt_atualizar->bindValue(':id_planilha', $id_planilha);
            
            $stmt_atualizar->execute();
            
            // Gerar parâmetros de retorno para manter os filtros
            $parametros_retorno = gerarParametrosFiltro();
            
            // Redirecionar de volta para a lista (caminho relativo ao document root)
            header('Location: /dev/app/views/produtos/read-produto.php?id=' . $id_planilha . ($parametros_retorno ? '&' . $parametros_retorno : ''));
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