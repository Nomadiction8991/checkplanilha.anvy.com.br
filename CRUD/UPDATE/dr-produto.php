<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = $_POST['produto_id'] ?? null;
    $id_planilha = $_POST['id_planilha'] ?? null;
    $dr = $_POST['dr'] ?? 0;
    
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
        // Se estiver marcando DR (dr = 1), limpar observações, desmarcar imprimir,
        // desmarcar checado e marcar ativo=0
        if ($dr == 1) {
            // Marcar DR: limpar observações (string vazia), desmarcar imprimir, desmarcar checado e marcar como inativo
            $stmt = $conexao->prepare('UPDATE produtos SET ativo = 0, checado = 0, observacao = :obs, imprimir_etiqueta = 0 WHERE id_produto = :id');
            $stmt->bindValue(':id', $produto_id, PDO::PARAM_INT);
            $stmt->bindValue(':obs', '', PDO::PARAM_STR);
            $stmt->execute();
        } else {
            // Desmarcar DR: marcar como ativo novamente
            $stmt = $conexao->prepare('UPDATE produtos SET ativo = 1 WHERE id_produto = :id');
            $stmt->bindValue(':id', $produto_id, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Redirecionar de volta mantendo os filtros
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
        exit;
        
    } catch (Exception $e) {
        $query_string = http_build_query(array_merge(
            ['id' => $id_planilha], 
            $filtros,
            ['erro' => 'Erro ao processar DR: ' . $e->getMessage()]
        ));
        header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}