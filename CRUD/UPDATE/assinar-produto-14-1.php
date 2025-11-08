<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$id_produto = $_POST['id_produto'] ?? null;
$id_planilha = $_POST['id_planilha'] ?? null;
$condicao_14_1 = $_POST['condicao_14_1'] ?? null;

if (!$id_produto || !$id_planilha || !$condicao_14_1) {
    $_SESSION['erro'] = 'Dados incompletos para assinatura.';
    header('Location: ../../app/views/planilhas/assinatura-14-1.php?id=' . urlencode($id_planilha));
    exit;
}

$condicao = intval($condicao_14_1);
$id_usuario = $_SESSION['id_usuario'];

try {
    $conexao->beginTransaction();
    
    // Se a condição for 2 (sem nota), limpar campos de nota
    if ($condicao == 2) {
        $sql = "UPDATE produtos SET 
                condicao_14_1 = :condicao,
                doador_conjugue_id = :id_usuario,
                nota_numero = NULL,
                nota_data = NULL,
                nota_valor = NULL,
                nota_fornecedor = NULL
                WHERE id_produto = :id_produto";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':condicao', $condicao);
        $stmt->bindValue(':id_usuario', $id_usuario);
        $stmt->bindValue(':id_produto', $id_produto);
        $stmt->execute();
    } 
    // Se for condição 1 ou 3 (com nota), validar e salvar campos de nota
    else {
        $nota_numero = $_POST['nota_numero'] ?? null;
        $nota_data = $_POST['nota_data'] ?? null;
        $nota_valor = $_POST['nota_valor'] ?? null;
        $nota_fornecedor = $_POST['nota_fornecedor'] ?? null;
        
        if (!$nota_numero || !$nota_data || !$nota_valor || !$nota_fornecedor) {
            throw new Exception('Todos os campos da nota fiscal são obrigatórios.');
        }
        
        $sql = "UPDATE produtos SET 
                condicao_14_1 = :condicao,
                doador_conjugue_id = :id_usuario,
                nota_numero = :nota_numero,
                nota_data = :nota_data,
                nota_valor = :nota_valor,
                nota_fornecedor = :nota_fornecedor
                WHERE id_produto = :id_produto";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':condicao', $condicao);
        $stmt->bindValue(':id_usuario', $id_usuario);
        $stmt->bindValue(':nota_numero', $nota_numero);
        $stmt->bindValue(':nota_data', $nota_data);
        $stmt->bindValue(':nota_valor', $nota_valor);
        $stmt->bindValue(':nota_fornecedor', $nota_fornecedor);
        $stmt->bindValue(':id_produto', $id_produto);
        $stmt->execute();
    }
    
    $conexao->commit();
    
    $_SESSION['sucesso'] = 'Produto assinado com sucesso!';
    header('Location: ../../app/views/planilhas/assinatura-14-1.php?id=' . urlencode($id_planilha));
    exit;
    
} catch (Exception $e) {
    $conexao->rollBack();
    $_SESSION['erro'] = 'Erro ao assinar produto: ' . $e->getMessage();
    header('Location: ../../app/views/planilhas/assinatura-14-1-form.php?id=' . urlencode($id_produto) . '&id_planilha=' . urlencode($id_planilha));
    exit;
}
?>
