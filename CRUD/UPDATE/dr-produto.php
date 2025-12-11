<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$produto_id = (int) ($_POST['produto_id'] ?? 0);
$comum_id = (int) ($_POST['comum_id'] ?? 0);
$dr = (int) ($_POST['dr'] ?? 0);

$filtros = [
    'pagina' => $_POST['pagina'] ?? 1,
    'nome' => $_POST['nome'] ?? '',
    'dependencia' => $_POST['dependencia'] ?? '',
    'codigo' => $_POST['codigo'] ?? '',
    'status' => $_POST['status'] ?? ''
];

$redirectBase = '../../app/views/planilhas/view-planilha.php';
$buildRedirect = function (string $erro = '') use ($redirectBase, $comum_id, $filtros): string {
    $params = array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $filtros);
    if ($erro !== '') {
        $params['erro'] = $erro;
    }
    return $redirectBase . '?' . http_build_query($params);
};

if ($produto_id <= 0 || $comum_id <= 0) {
    $msg = 'Parâmetros inválidos para atualizar DR.';
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $msg], 400);
    }
    header('Location: ' . $buildRedirect($msg));
    exit;
}

try {
    if ($dr === 1) {
        // Marcar DR: limpar observações, desmarcar imprimir e checado, marcar como inativo
        $stmt = $conexao->prepare('UPDATE produtos SET ativo = 0, checado = 0, observacao = :obs, imprimir_etiqueta = 0 WHERE id_produto = :id');
        $stmt->bindValue(':id', $produto_id, PDO::PARAM_INT);
        $stmt->bindValue(':obs', '', PDO::PARAM_STR);
        $stmt->execute();
    } else {
        // Desmarcar DR: ativar novamente
        $stmt = $conexao->prepare('UPDATE produtos SET ativo = 1 WHERE id_produto = :id');
        $stmt->bindValue(':id', $produto_id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    if (is_ajax_request()) {
        json_response([
            'success' => true,
            'produto_id' => $produto_id,
            'dr' => $dr,
            'message' => $dr ? 'Produto enviado para DR.' : 'Produto reativado.'
        ]);
    }

    header('Location: ' . $buildRedirect());
    exit;
    
} catch (Exception $e) {
    $msg = 'Erro ao processar DR: ' . $e->getMessage();
    if (is_ajax_request()) {
        json_response(['success' => false, 'message' => $msg], 500);
    }
    header('Location: ' . $buildRedirect($msg));
    exit;
}
