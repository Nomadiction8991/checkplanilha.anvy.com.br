<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

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
        // Validar se pode marcar para impressão (deve estar checado e não estar no DR)
        if ($imprimir == 1) {
            $sql_verifica = "SELECT p.*, COALESCE(pc.checado, 0) as checado, COALESCE(pc.dr, 0) as dr 
                            FROM produtos p 
                            LEFT JOIN produtos_check pc ON p.id = pc.produto_id 
                            WHERE p.id = :produto_id";
            $stmt_verifica = $conexao->prepare($sql_verifica);
            $stmt_verifica->bindValue(':produto_id', $produto_id);
            $stmt_verifica->execute();
            $produto_info = $stmt_verifica->fetch();
            
            if (!$produto_info || $produto_info['checado'] == 0 || $produto_info['dr'] == 1) {
                $query_string = http_build_query(array_merge(
                    ['id' => $id_planilha], 
                    $filtros,
                    ['erro' => 'Só é possível marcar para impressão produtos que estão checados e não estão no DR.']
                ));
                header('Location: ../../app/views/planilhas/view-planilha.php?' . $query_string);
                exit;
            }
        }
        
        // Verificar se já existe registro
        $sql_check = "SELECT * FROM produtos_check WHERE produto_id = :produto_id";
        $stmt_check = $conexao->prepare($sql_check);
        $stmt_check->bindValue(':produto_id', $produto_id);
        $stmt_check->execute();
        $existe = $stmt_check->fetch();
        
        if ($existe) {
            // Atualizar
            $sql = "UPDATE produtos_check SET imprimir = :imprimir WHERE produto_id = :produto_id";
        } else {
            // Inserir
            $sql = "INSERT INTO produtos_check (produto_id, imprimir) VALUES (:produto_id, :imprimir)";
        }
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':produto_id', $produto_id);
        $stmt->bindValue(':imprimir', $imprimir, PDO::PARAM_INT);
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