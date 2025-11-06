<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

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
$novo_tipo_bem_id = '';
$novo_bem = '';
$novo_complemento = '';
$nova_dependencia_id = '';

// Buscar dados do produto - USANDO id_produto
try {
    $sql_produto = "SELECT p.*, tb.codigo as tipo_bem_codigo, tb.descricao as tipo_bem_descricao, d.descricao as dependencia_descricao
                    FROM produtos p
                    LEFT JOIN tipos_bens tb ON p.id_tipo_ben = tb.id
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
    
    // Pré-preencher com edições se existirem
    $novo_tipo_bem_id = $produto['editado_id_tipo_bem'] ?? '';
    $novo_bem = $produto['editado_ben'] ?? '';
    $novo_complemento = $produto['editado_complemento'] ?? '';
    $nova_dependencia_id = $produto['editado_dependencia_id'] ?? '';
    
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
        // Se não houver alterações, retorna
        if ($novo_tipo_bem_id === '' && $novo_bem === '' && $novo_complemento === '' && $nova_dependencia_id === '') {
            header('Location: ' . getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status));
            exit;
        }

        $sql_update = "UPDATE produtos SET imprimir_etiqueta = 1, editado = 1";
        $params = [':id_produto' => $id_produto, ':planilha_id' => $id_planilha];

        if ($novo_tipo_bem_id !== '') {
            $sql_update .= ", editado_id_tipo_bem = :novo_tipo_bem_id";
            $params[':novo_tipo_bem_id'] = $novo_tipo_bem_id;
        }
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
            $params[':nova_dependencia_id'] = $nova_dependencia_id;
        }

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