<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação
require_once PROJECT_ROOT . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = $_POST['produto_id'] ?? null;
    $id_planilha = $_POST['id_planilha'] ?? null;
    $imprimir = $_POST['imprimir'] ?? 0;
    
    // Preservar filtros
    $filtros = [
        'pagina' => $_POST['pagina'] ?? 1,
        'nome' => $_POST['nome'] ?? '',
        'dependencia' => $_POST['dependencia'] ?? '',
        'codigo' => $_POST['codigo'] ?? '',
        'status' => $_POST['status'] ?? ''
    ];
    
    if (!$produto_id || !$id_planilha) {
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
        exit;
    }
    
    try {
        // Validar se pode marcar para impressão (deve estar checado)
        if ($imprimir == 1) {
            // Validar com flags da própria tabela produtos - USANDO id_produto
            $stmt_verifica = $conexao->prepare('SELECT checado FROM produtos WHERE id_produto = :id_produto AND planilha_id = :planilha_id');
            $stmt_verifica->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
            $stmt_verifica->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
            $stmt_verifica->execute();
            $produto_info = $stmt_verifica->fetch(PDO::FETCH_ASSOC);

            if (!$produto_info || ($produto_info['checado'] ?? 0) == 0) {
                $query_string = http_build_query(array_merge(
                    ['id' => $id_planilha], 
                    $filtros,
                    ['erro' => 'Só é possível marcar para impressão produtos que estão checados.']
                ));
                header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
                exit;
            }
        }
        // Atualizar flag diretamente em produtos - USANDO id_produto
        $stmt = $conexao->prepare('UPDATE produtos SET imprimir_etiqueta = :imprimir WHERE id_produto = :id_produto AND planilha_id = :planilha_id');
        $stmt->bindValue(':imprimir', (int)$imprimir, PDO::PARAM_INT);
        $stmt->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
        $stmt->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
        $stmt->execute();
        
        // Redirecionar de volta mantendo os filtros
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
        exit;
        
    } catch (Exception $e) {
        $query_string = http_build_query(array_merge(
            ['id' => $id_planilha], 
            $filtros,
            ['erro' => 'Erro ao processar impressão: ' . $e->getMessage()]
        ));
        header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}