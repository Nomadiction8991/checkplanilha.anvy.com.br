<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
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
    $multiplicador = $_POST['multiplicador'] ?? 1;
    $condicao_141 = isset($_POST['condicao_141']) && in_array($_POST['condicao_141'], ['1','2','3'], true) ? (int)$_POST['condicao_141'] : null;
    
    // Campos de nota: aceitar quando condicao_141 = 1 ou 3 (ambas exigem nota fiscal anexa)
    $numero_nota = null;
    $data_emissao = null;
    $valor_nota = null;
    $fornecedor_nota = null;
    
    if ($condicao_141 === 1 || $condicao_141 === 3) {
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
    
    if (empty($multiplicador) || $multiplicador < 1) {
        $erros[] = "O multiplicador deve ser pelo menos 1";
    }
    
    // Validações da nota quando condicao_141 = 1 ou 3 (ambas exigem nota fiscal anexa)
    if ($condicao_141 === 1 || $condicao_141 === 3) {
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
            $sql_inserir = "INSERT INTO produtos (
                           planilha_id, codigo, descricao_completa, editado_descricao_completa,
                           tipo_bem_id, editado_tipo_bem_id, bem, editado_bem,
                           complemento, editado_complemento, dependencia_id, editado_dependencia_id,
                           checado, editado, imprimir_etiqueta, imprimir_14_1,
                           observacao, ativo, novo,
                           condicao_141, numero_nota, data_emissao, valor_nota, fornecedor_nota
                           ) VALUES (
                           :planilha_id, :codigo, :descricao_completa, '',
                           :id_tipo_bem, 0, :tipo_bem, '',
                           :complemento, '', :id_dependencia, 0,
                           1, 0, 1, :imprimir_14_1,
                           '', 1, 1,
                           :condicao_141, :numero_nota, :data_emissao, :valor_nota, :fornecedor_nota
                           )";
            
            $stmt_inserir = $conexao->prepare($sql_inserir);
            
            // Criar cada um dos registros (quantidade de 1 unidade)
            for ($i = 0; $i < $multiplicador; $i++) {
                // Montar descrição completa (quantidade sempre será 1)
                $descricao_completa = "1x [" . $tipo_bem['codigo'] . " - " . $tipo_bem['descricao'] . "] " . $tipo_ben . " - " . $complemento . " - (" . $dependencia['descricao'] . ")";
                
                $stmt_inserir->bindValue(':id_planilha', $id_planilha);
                $stmt_inserir->bindValue(':codigo', !empty($codigo) ? $codigo : null);
                $stmt_inserir->bindValue(':id_tipo_bem', $id_tipo_ben);
                $stmt_inserir->bindValue(':tipo_bem', $tipo_ben);
                $stmt_inserir->bindValue(':complemento', $complemento);
                $stmt_inserir->bindValue(':id_dependencia', $id_dependencia);
                $stmt_inserir->bindValue(':descricao_completa', $descricao_completa);
                $stmt_inserir->bindValue(':numero_nota', $numero_nota);
                $stmt_inserir->bindValue(':data_emissao', $data_emissao);
                $stmt_inserir->bindValue(':valor_nota', $valor_nota);
                $stmt_inserir->bindValue(':fornecedor_nota', $fornecedor_nota);
                $stmt_inserir->bindValue(':imprimir_14_1', $imprimir_14_1);
                $stmt_inserir->bindValue(':condicao_141', $condicao_141, PDO::PARAM_INT);
                
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