<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}

$ids_produtos = $_POST['ids_produtos'] ?? [];
$id_planilha = $_POST['id_planilha'] ?? null;
$condicao_14_1 = $_POST['condicao_14_1'] ?? null;

if (empty($ids_produtos) || !$id_planilha || !$condicao_14_1) {
    $_SESSION['erro'] = 'Dados incompletos para assinatura.';
    header('Location: ../../app/views/planilhas/assinatura-14-1.php?id=' . urlencode($id_planilha));
    exit;
}

$condicao = intval($condicao_14_1);
$id_usuario = $_SESSION['usuario_id'] ?? null;

if (!$id_usuario) {
    $_SESSION['erro'] = 'Usuário não autenticado.';
    header('Location: ../../login.php');
    exit;
}

try {
    $conexao->beginTransaction();
    
    $produtos_assinados = 0;
    
    foreach ($ids_produtos as $id_produto) {
        $id_produto = intval($id_produto);
        
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
            $stmt->bindValue(':condicao', $condicao, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $produtos_assinados++;
            }
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
            $stmt->bindValue(':condicao', $condicao, PDO::PARAM_INT);
            $stmt->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->bindValue(':nota_numero', $nota_numero);
            $stmt->bindValue(':nota_data', $nota_data);
            $stmt->bindValue(':nota_valor', $nota_valor);
            $stmt->bindValue(':nota_fornecedor', $nota_fornecedor);
            $stmt->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $produtos_assinados++;
            }
        }
    }
    
    if ($produtos_assinados == 0) {
        throw new Exception('Nenhum produto foi atualizado. Verifique se os produtos existem.');
    }
    
    $conexao->commit();
    
    $mensagem = $produtos_assinados == 1 
        ? 'Produto assinado com sucesso!' 
        : "{$produtos_assinados} produtos assinados com sucesso!";
    
    $_SESSION['sucesso'] = $mensagem;
    header('Location: ../../app/views/planilhas/assinatura-14-1.php?id=' . urlencode($id_planilha));
    exit;
    
} catch (Exception $e) {
    $conexao->rollBack();
    $_SESSION['erro'] = 'Erro ao assinar produto(s): ' . $e->getMessage();
    
    $redirect_ids = implode(',', $ids_produtos);
    header('Location: ../../app/views/planilhas/assinatura-14-1-form.php?ids=' . urlencode($redirect_ids) . '&id_planilha=' . urlencode($id_planilha));
    exit;
}
?>
