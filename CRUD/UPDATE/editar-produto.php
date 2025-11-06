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

// Buscar dados do produto
try {
    $sql_produto = "SELECT * FROM produtos WHERE id = :id_produto AND id_planilha = :id_planilha";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id_produto', $id_produto);
    $stmt_produto->bindValue(':id_planilha', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado.');
    }
    // Pré-preencher com edições se existirem no novo schema
    $novo_nome = $produto['nome_editado'] ?? '';
    $nova_dependencia = $produto['dependencia_editada'] ?? '';
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Buscar opções de dependência para o select
try {
    $sql_dependencias = "
        SELECT DISTINCT dependencia FROM produtos WHERE id_planilha = :id_planilha
        UNION
        SELECT DISTINCT dependencia_editada FROM produtos WHERE id_planilha = :id_planilha AND dependencia_editada IS NOT NULL AND dependencia_editada <> ''
        ORDER BY 1
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
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    $nova_dependencia = trim($_POST['nova_dependencia'] ?? '');
    
    // Receber filtros do POST também
    $pagina = $_POST['pagina'] ?? 1;
    $filtro_nome = $_POST['nome'] ?? '';
    $filtro_dependencia = $_POST['dependencia'] ?? '';
    $filtro_codigo = $_POST['filtro_codigo'] ?? '';
    $filtro_status = $_POST['status'] ?? '';

    try {
        // Se não houver alterações, retorna
        if ($novo_nome === '' && $nova_dependencia === '') {
            header('Location: ' . getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status));
            exit;
        }

        $sql_update = "UPDATE produtos SET imprimir = 1, editado = 1";
        $params = [':id' => $produto['id']];

        if ($novo_nome !== '') {
            $sql_update .= ", nome_editado = :nome_editado";
            $params[':nome_editado'] = $novo_nome;
        }
        if ($nova_dependencia !== '') {
            $sql_update .= ", dependencia_editada = :dependencia_editada";
            $params[':dependencia_editada'] = $nova_dependencia;
        }

        $sql_update .= " WHERE id = :id";
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