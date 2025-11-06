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
$novo_nome = '';
$nova_dependencia = '';

// Buscar dados do produto - USANDO id_produto
try {
    $sql_produto = "SELECT * FROM produtos WHERE id_produto = :id_produto AND planilha_id = :planilha_id";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id_produto', $id_produto);
    $stmt_produto->bindValue(':planilha_id', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado.');
    }
    // Pré-preencher com edições se existirem no novo schema
    $nova_descricao = $produto['editado_descricao_completa'] ?? '';
    $novo_complemento = $produto['editado_complemento'] ?? '';
    $novo_ben = $produto['editado_ben'] ?? '';
    $nova_dependencia = $produto['editado_dependencia_id'] ?? '';
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Buscar opções de dependência para o select
try {
    $sql_dependencias = "
        SELECT DISTINCT dep FROM (
            SELECT p.dependencia_id AS dep
            FROM produtos p
            WHERE p.planilha_id = :id_planilha
              AND p.dependencia_id IS NOT NULL
              AND p.dependencia_id <> ''
            UNION
            SELECT p.editado_dependencia_id AS dep
            FROM produtos p
            WHERE p.planilha_id = :id_planilha
              AND p.editado_dependencia_id IS NOT NULL
              AND p.editado_dependencia_id <> ''
        ) deps
        ORDER BY dep
    ";
    $stmt_dependencias = $conexao->prepare($sql_dependencias);
    $stmt_dependencias->bindValue(':id_planilha', $id_planilha);
    $stmt_dependencias->execute();
    $dependencia_options = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $dependencia_options = [];
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_descricao = trim($_POST['nova_descricao'] ?? '');
    $novo_complemento = trim($_POST['novo_complemento'] ?? '');
    $novo_ben = trim($_POST['novo_ben'] ?? '');
    $nova_dependencia = trim($_POST['nova_dependencia'] ?? '');
    
    // Receber filtros do POST também
    $pagina = $_POST['pagina'] ?? 1;
    $filtro_nome = $_POST['nome'] ?? '';
    $filtro_dependencia = $_POST['dependencia'] ?? '';
    $filtro_codigo = $_POST['filtro_codigo'] ?? '';
    $filtro_status = $_POST['status'] ?? '';

    try {
        // Se não houver alterações, retorna
        if ($nova_descricao === '' && $novo_complemento === '' && $novo_ben === '' && $nova_dependencia === '') {
            header('Location: ' . getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status));
            exit;
        }

        $sql_update = "UPDATE produtos SET imprimir_etiqueta = 1, editado = 1";
        $params = [':id_produto' => $id_produto, ':planilha_id' => $id_planilha];

        if ($nova_descricao !== '') {
            $sql_update .= ", editado_descricao_completa = :nova_descricao";
            $params[':nova_descricao'] = $nova_descricao;
        }
        if ($novo_complemento !== '') {
            $sql_update .= ", editado_complemento = :novo_complemento";
            $params[':novo_complemento'] = $novo_complemento;
        }
        if ($novo_ben !== '') {
            $sql_update .= ", editado_ben = :novo_ben";
            $params[':novo_ben'] = $novo_ben;
        }
        if ($nova_dependencia !== '') {
            $sql_update .= ", editado_dependencia_id = :nova_dependencia";
            $params[':nova_dependencia'] = $nova_dependencia;
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