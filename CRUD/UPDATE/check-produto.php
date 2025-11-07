<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = $_POST['produto_id'] ?? null;
    $id_planilha = $_POST['id_planilha'] ?? null;
    $checado = $_POST['checado'] ?? 0;
    
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
        // Validar se pode desmarcar o check (não pode estar inativo/DR ou marcado para impressão)
        if ($checado == 0) {
            $sql_verifica = "SELECT COALESCE(p.ativo, 1) as ativo, COALESCE(p.imprimir_etiqueta, 0) as imprimir 
                            FROM produtos p 
                            WHERE p.id_produto = :produto_id";
            $stmt_verifica = $conexao->prepare($sql_verifica);
            $stmt_verifica->bindValue(':produto_id', $produto_id);
            $stmt_verifica->execute();
            $status = $stmt_verifica->fetch();
            
            if ($status && ($status['ativo'] == 0 || $status['imprimir'] == 1)) {
                $query_string = http_build_query(array_merge(
                    ['id' => $id_planilha], 
                    $filtros,
                    ['erro' => 'Não é possível desmarcar o check se o produto estiver no DR ou marcado para impressão.']
                ));
                header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
                exit;
            }
        }
        
        // Atualizar coluna checado na tabela produtos
        $sql = "UPDATE produtos SET checado = :checado WHERE id_produto = :produto_id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':produto_id', $produto_id);
        $stmt->bindValue(':checado', $checado, PDO::PARAM_INT);
        $stmt->execute();
        
        // Redirecionar de volta mantendo os filtros
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
        exit;
        
    } catch (Exception $e) {
        $query_string = http_build_query(array_merge(
            ['id' => $id_planilha], 
            $filtros,
            ['erro' => 'Erro ao processar check: ' . $e->getMessage()]
        ));
        header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}