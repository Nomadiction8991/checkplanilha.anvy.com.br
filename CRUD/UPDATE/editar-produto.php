<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';
// Funções de montagem de descrição
require_once __DIR__ . '/../../app/functions/produto_parser.php';

// Receber parâmetros
$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id'] ?? null;

// Receber filtros para redirecionamento
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Validação
if (!$id_produto || !$id_planilha) {
    $query_string = http_build_query([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Parâmetros inválidos'
    ]);
    header('Location: ../planilhas/view-planilha.php?' . $query_string);
    exit;
}

$mensagem = '';
$tipo_mensagem = '';
// Valores do formulário (pré-preenchidos com editados ou originais)
$novo_tipo_bem_id = '';
$novo_bem = '';
$novo_complemento = '';
$nova_dependencia_id = '';

// Buscar dados do produto - USANDO id_produto
try {
    $sql_produto = "SELECT p.*, d.descricao as dependencia_descricao
                    FROM produtos p
                    LEFT JOIN dependencias d ON p.dependencia_id = d.id
                    WHERE p.id_produto = :id_produto AND p.planilha_id = :planilha_id";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id_produto', $id_produto);
    $stmt_produto->bindValue(':planilha_id', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado.');
    }
    
    // Pré-preencher com edições se existirem (senão usa original)
    // Tipo de bem: usar tipo_ben_id do produto (não persistimos editado_tipo_bem_id nesta instalação)
    if (!empty($produto['tipo_ben_id']) && (int)$produto['tipo_ben_id'] > 0) {
        $novo_tipo_bem_id = (int)$produto['tipo_ben_id'];
    }
    $novo_bem = $produto['editado_ben'] !== '' ? $produto['editado_ben'] : ($produto['ben'] ?? '');
    $novo_complemento = $produto['editado_complemento'] !== '' ? $produto['editado_complemento'] : ($produto['complemento'] ?? '');
    // Dependência: usar editado se > 0, senão usar original
    $nova_dependencia_id = (!empty($produto['editado_dependencia_id']) && (int)$produto['editado_dependencia_id'] > 0) 
        ? (int)$produto['editado_dependencia_id'] 
        : (int)($produto['dependencia_id'] ?? 0);
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Buscar todos os tipos de bens
try {
    $sql_tipos_bens = "SELECT id, codigo, descricao FROM tipos_bens ORDER BY codigo";
    $stmt_tipos = $conexao->prepare($sql_tipos_bens);
    $stmt_tipos->execute();
    $tipos_bens = $stmt_tipos->fetchAll();
} catch (Exception $e) {
    $tipos_bens = [];
}

// Buscar todas as dependências
try {
    $sql_dependencias = "SELECT id, descricao FROM dependencias ORDER BY descricao";
    $stmt_deps = $conexao->prepare($sql_dependencias);
    $stmt_deps->execute();
    $dependencias = $stmt_deps->fetchAll();
} catch (Exception $e) {
    $dependencias = [];
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_tipo_bem_id = trim($_POST['novo_tipo_bem_id'] ?? '');
    $novo_bem = strtoupper(trim($_POST['novo_bem'] ?? ''));
    $novo_complemento = strtoupper(trim($_POST['novo_complemento'] ?? ''));
    $nova_dependencia_id = trim($_POST['nova_dependencia_id'] ?? '');
    
    // Receber filtros do POST também
    $pagina = $_POST['pagina'] ?? 1;
    $filtro_nome = $_POST['nome'] ?? '';
    $filtro_dependencia = $_POST['dependencia'] ?? '';
    $filtro_codigo = $_POST['filtro_codigo'] ?? '';
    $filtro_status = $_POST['status'] ?? '';

    try {
        // Determinar campos originais para fallback
    $orig_tipo_id = (int)($produto['tipo_ben_id']);
        $orig_ben = $produto['editado_ben'] !== '' ? $produto['editado_ben'] : ($produto['ben'] ?? '');
        $orig_comp = $produto['editado_complemento'] !== '' ? $produto['editado_complemento'] : ($produto['complemento'] ?? '');
        $orig_dep_id = (int)($produto['editado_dependencia_id'] ?: $produto['dependencia_id']);

        // Verificar se houve realmente alguma alteração
        $houve_alteracao = false;
        if ($novo_tipo_bem_id !== '' && (int)$novo_tipo_bem_id !== $orig_tipo_id) $houve_alteracao = true;
        if ($novo_bem !== '' && $novo_bem !== strtoupper($orig_ben)) $houve_alteracao = true;
        if ($novo_complemento !== '' && $novo_complemento !== strtoupper($orig_comp)) $houve_alteracao = true;
        if ($nova_dependencia_id !== '' && (int)$nova_dependencia_id !== $orig_dep_id) $houve_alteracao = true;

        if (!$houve_alteracao) {
            // Nada mudou, retorna sem marcar edição
            header('Location: ' . getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status));
            exit;
        }

        $sql_update = "UPDATE produtos SET imprimir_etiqueta = 1, editado = 1";
        $params = [':id_produto' => $id_produto, ':planilha_id' => $id_planilha];

        // Observação: nesta instalação não persistimos coluna editado_tipo_bem_id
        if ($novo_bem !== '') {
            $sql_update .= ", editado_ben = :novo_bem";
            $params[':novo_bem'] = $novo_bem;
        }
        if ($novo_complemento !== '') {
            $sql_update .= ", editado_complemento = :novo_complemento";
            $params[':novo_complemento'] = $novo_complemento;
        }
        if ($nova_dependencia_id !== '') {
            $sql_update .= ", editado_dependencia_id = :nova_dependencia_id";
            $params[':nova_dependencia_id'] = (int)$nova_dependencia_id;
        }

        // Montar descrição completa editada usando fallback para originais se campo editado em branco
        // Determinar valores finais (se não enviados, usa original)
        $final_tipo_id = ($novo_tipo_bem_id !== '') ? (int)$novo_tipo_bem_id : $orig_tipo_id;
        $final_ben = ($novo_bem !== '') ? $novo_bem : strtoupper($orig_ben);
        $final_comp = ($novo_complemento !== '') ? $novo_complemento : strtoupper($orig_comp);
        $final_dep_id = ($nova_dependencia_id !== '') ? (int)$nova_dependencia_id : $orig_dep_id;

        // Buscar dados do tipo selecionado
        $tipo_codigo = null; $tipo_desc = '';
        foreach ($tipos_bens as $tb) {
            if ((int)$tb['id'] === (int)$final_tipo_id) {
                $tipo_codigo = $tb['codigo'];
                $tipo_desc = $tb['descricao'];
                break;
            }
        }
        // Buscar descrição da dependência
        $dep_desc = '';
        foreach ($dependencias as $dep) {
            if ((int)$dep['id'] === (int)$final_dep_id) {
                $dep_desc = $dep['descricao'];
                break;
            }
        }
        if ($dep_desc === '' && !empty($produto['dependencia_descricao'])) {
            $dep_desc = $produto['dependencia_descricao'];
        }

        // Usar função de montagem (quantidade padrão 1)
        $descricao_editada = pp_montar_descricao(1, $tipo_codigo, $tipo_desc, $final_ben, $final_comp, $dep_desc, []);
        $sql_update .= ", editado_descricao_completa = :editado_desc";
        $params[':editado_desc'] = strtoupper($descricao_editada);

        $sql_update .= " WHERE id_produto = :id_produto AND planilha_id = :planilha_id";
        $stmt_update = $conexao->prepare($sql_update);
        foreach ($params as $key => $value) {
            $stmt_update->bindValue($key, $value);
        }
        $stmt_update->execute();

        header('Location: ' . getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status));
        exit;
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar alterações: " . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}

// Função para gerar URL de retorno com filtros
function getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status) {
    $params = [
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status
    ];
    return '../planilhas/view-planilha.php?' . http_build_query($params);
}
?>