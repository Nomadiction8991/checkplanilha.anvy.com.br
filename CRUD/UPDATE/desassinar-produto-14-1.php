<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$ids_produtos = $_POST['ids_produtos'] ?? [];
$id_planilha = $_POST['id_planilha'] ?? null;

if (empty($ids_produtos) || !$id_planilha) {
    $_SESSION['erro'] = 'Dados incompletos para desfazer assinatura.';
    header('Location: ../../app/views/planilhas/assinatura-14-1.php?id=' . urlencode($id_planilha));
    exit;
}

$id_usuario = $_SESSION['usuario_id'] ?? null;
if (!$id_usuario) {
    $_SESSION['erro'] = 'Usuário não autenticado.';
    header('Location: ../../login.php');
    exit;
}

try {
    $conexao->beginTransaction();
    $produtos_atualizados = 0;

    $sql = "UPDATE produtos SET 
                condicao_14_1 = NULL,
                doador_conjugue_id = 0,
                nota_numero = NULL,
                nota_data = NULL,
                nota_valor = NULL,
                nota_fornecedor = NULL
            WHERE id_produto = :id_produto AND doador_conjugue_id = :id_usuario";
    $stmt = $conexao->prepare($sql);

    foreach ($ids_produtos as $id_produto) {
        $stmt->execute([
            ':id_produto' => (int)$id_produto,
            ':id_usuario' => $id_usuario
        ]);
        if ($stmt->rowCount() > 0) {
            $produtos_atualizados++;
        }
    }

    if ($produtos_atualizados == 0) {
        throw new Exception('Nenhum produto elegível para desfazer assinatura.');
    }

    $conexao->commit();
    $_SESSION['sucesso'] = $produtos_atualizados == 1 ? 'Assinatura desfeita com sucesso.' : "$produtos_atualizados assinaturas desfeitas com sucesso.";
    header('Location: ../../app/views/planilhas/assinatura-14-1.php?id=' . urlencode($id_planilha));
    exit;
} catch (Exception $e) {
    $conexao->rollBack();
    $_SESSION['erro'] = 'Erro ao desfazer assinatura(s): ' . $e->getMessage();
    $redirect_ids = implode(',', $ids_produtos);
    header('Location: ../../app/views/planilhas/assinatura-14-1-form.php?ids=' . urlencode($redirect_ids) . '&id_planilha=' . urlencode($id_planilha));
    exit;
}
?>
