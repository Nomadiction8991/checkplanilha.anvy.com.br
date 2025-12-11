<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
// Endpoint público para processar o check do produto
// Inclui a lógica do CRUD e ajusta os redirecionamentos para o contexto correto

// Capturar dados antes de incluir
$_POST_BACKUP = $_POST;
$_REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];

// Incluir conexão
require_once __DIR__ . '/../../../CRUD/conexao.php';

if ($_REQUEST_METHOD === 'POST') {
    $produto_id = $_POST_BACKUP['produto_id'] ?? null;
    $id_planilha = $_POST_BACKUP['id_planilha'] ?? null;
    $checado = $_POST_BACKUP['checado'] ?? 0;
    
    // Preservar filtros
    $filtros = [
        'pagina' => $_POST_BACKUP['pagina'] ?? 1,
        'nome' => $_POST_BACKUP['nome'] ?? '',
        'dependencia' => $_POST_BACKUP['dependencia'] ?? '',
        'codigo' => $_POST_BACKUP['codigo'] ?? '',
        'status' => $_POST_BACKUP['status'] ?? ''
    ];
    
    if (!$produto_id || !$id_planilha) {
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: ./view-planilha.php?' . $query_string);
        exit;
    }
    
    try {
        // Buscar status atual no novo schema (produtos) - USANDO id_produto
        $stmt_status = $conexao->prepare('SELECT checado, imprimir_etiqueta, imprimir_14_1 FROM produtos WHERE id_produto = :id_produto AND planilha_id = :planilha_id');
        $stmt_status->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
        $stmt_status->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
        $stmt_status->execute();
        $status = $stmt_status->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            throw new Exception('Produto não encontrado.');
        }

        // Regra: não pode desmarcar checado se estiver marcado para impressão
        if ((int)$checado === 0 && (($status['imprimir_etiqueta'] ?? 0) == 1 || ($status['imprimir_14_1'] ?? 0) == 1)) {
            $query_string = http_build_query(array_merge(
                ['id' => $id_planilha], 
                $filtros,
                ['erro' => 'Não é possível desmarcar o check se o produto estiver marcado para impressão.']
            ));
            header('Location: ./view-planilha.php?' . $query_string);
            exit;
        }

        // Atualizar flag no próprio produto - USANDO id_produto
        $stmt_up = $conexao->prepare('UPDATE produtos SET checado = :checado WHERE id_produto = :id_produto AND planilha_id = :planilha_id');
        $stmt_up->bindValue(':checado', (int)$checado, PDO::PARAM_INT);
        $stmt_up->bindValue(':id_produto', $produto_id, PDO::PARAM_INT);
        $stmt_up->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
        $stmt_up->execute();
        
        // Redirecionar de volta mantendo os filtros
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: ./view-planilha.php?' . $query_string);
        exit;
        
    } catch (Exception $e) {
        $query_string = http_build_query(array_merge(
            ['id' => $id_planilha], 
            $filtros,
            ['erro' => 'Erro ao processar check: ' . $e->getMessage()]
        ));
        header('Location: ./view-planilha.php?' . $query_string);
        exit;
    }
} else {
    header('Location: ../../../index.php');
    exit;
}
