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
        // Validar se pode desmarcar o check (não pode estar no DR ou marcado para impressão)
        if ($checado == 0) {
            $sql_verifica = "SELECT COALESCE(pc.dr, 0) as dr, COALESCE(pc.imprimir, 0) as imprimir 
                            FROM produtos_check pc 
                            WHERE pc.produto_id = :produto_id";
            $stmt_verifica = $conexao->prepare($sql_verifica);
            $stmt_verifica->bindValue(':produto_id', $produto_id);
            $stmt_verifica->execute();
            $status = $stmt_verifica->fetch();
            
            if ($status && ($status['dr'] == 1 || $status['imprimir'] == 1)) {
                $query_string = http_build_query(array_merge(
                    ['id' => $id_planilha], 
                    $filtros,
                    ['erro' => 'Não é possível desmarcar o check se o produto estiver no DR ou marcado para impressão.']
                ));
                header('Location: ./view-planilha.php?' . $query_string);
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
            $sql = "UPDATE produtos_check SET checado = :checado WHERE produto_id = :produto_id";
        } else {
            // Inserir
            $sql = "INSERT INTO produtos_check (produto_id, checado) VALUES (:produto_id, :checado)";
        }
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':produto_id', $produto_id);
        $stmt->bindValue(':checado', $checado, PDO::PARAM_INT);
        $stmt->execute();
        
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
