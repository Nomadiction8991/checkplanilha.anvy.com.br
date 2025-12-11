<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$produto_id = (int) ($_POST['produto_id'] ?? 0);
$id_planilha = (int) ($_POST['id_planilha'] ?? 0);
$imprimir = (int) ($_POST['imprimir'] ?? 0);

$filtros = [
    'pagina' => $_POST['pagina'] ?? 1,
    'nome' => $_POST['nome'] ?? '',
    'dependencia' => $_POST['dependencia'] ?? '',
    'codigo' => $_POST['codigo'] ?? '',
    'status' => $_POST['status'] ?? ''
];

$redirectBase = '../../app/views/planilhas/view-planilha.php';
$buildRedirect = function (string $erro = '') use ($redirectBase, $id_planilha, $filtros): string {
    $params = array_merge(['id' => $id_planilha], $filtros);
    if ($erro !== '') {
        $params['erro'] = $erro;
    }
    return $redirectBase . '?' . http_build_query($params);
};

if ($produto_id <= 0 || $id_planilha <= 0) {
    $msg = 'Parâmetros inválidos para marcar etiqueta.';
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $msg], 400);
    }
    header('Location: ' . $buildRedirect($msg));
    exit;
}

try {
    // Validar se pode marcar para impressão (deve estar checado)
    if ($imprimir === 1) {
        $stmt_verifica = $conexao->prepare('SELECT checado FROM produtos WHERE id_produto = :id_produto AND planilha_id = :planilha_id');
        $stmt_verifica->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
        $stmt_verifica->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
        $stmt_verifica->execute();
        $produto_info = $stmt_verifica->fetch(PDO::FETCH_ASSOC);

        if (!$produto_info || ($produto_info['checado'] ?? 0) == 0) {
            $msg = 'Só é possível marcar para impressão produtos que estejam checados.';
            if (is_ajax_request()) {
                json_response(['success' => false, 'message' => $msg], 422);
            }
            header('Location: ' . $buildRedirect($msg));
            exit;
        }
    }

    // Atualizar flag diretamente em produtos
    $stmt = $conexao->prepare('UPDATE produtos SET imprimir_etiqueta = :imprimir WHERE id_produto = :id_produto AND planilha_id = :planilha_id');
    $stmt->bindValue(':imprimir', $imprimir, PDO::PARAM_INT);
    $stmt->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
    $stmt->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
    $stmt->execute();
    
    if (is_ajax_request()) {
        json_response([
            'success' => true,
            'produto_id' => $produto_id,
            'imprimir' => $imprimir,
            'message' => $imprimir ? 'Produto marcado para etiqueta.' : 'Produto removido das etiquetas.'
        ]);
    }
    
    header('Location: ' . $buildRedirect());
    exit;
    
} catch (Exception $e) {
    $msg = 'Erro ao processar impressão: ' . $e->getMessage();
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $msg], 500);
    }
    header('Location: ' . $buildRedirect($msg));
    exit;
}
