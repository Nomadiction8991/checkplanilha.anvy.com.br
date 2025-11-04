<?php
require_once '../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/conexao.php';

// Parâmetros
$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id'] ?? null;

// Filtros para retorno
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

function redirectBack($params) {
    $qs = http_build_query($params);
    header('Location: ../planilhas/view-planilha.php?' . $qs);
    exit;
}

if (!$id_produto || !$id_planilha) {
    redirectBack([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Parâmetros inválidos'
    ]);
}

try {
    // Verificar existência em produtos_check
    $stmt = $conexao->prepare('SELECT COUNT(*) AS total FROM produtos_check WHERE produto_id = :pid');
    $stmt->bindValue(':pid', $id_produto);
    $stmt->execute();
    $existe = $stmt->fetch()['total'] > 0;

    if ($existe) {
        // Limpar campos de edição e imprimir
        $up = $conexao->prepare('UPDATE produtos_check SET nome = NULL, dependencia = NULL, imprimir = 0, editado = 0 WHERE produto_id = :pid');
        $up->bindValue(':pid', $id_produto);
        $up->execute();
        $msg = 'Edições limpas com sucesso!';
    } else {
        $msg = 'Nenhuma edição encontrada para limpar.';
    }

    redirectBack([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'sucesso' => $msg
    ]);

} catch (Exception $e) {
    redirectBack([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Erro ao limpar edições: ' . $e->getMessage()
    ]);
}
?>
