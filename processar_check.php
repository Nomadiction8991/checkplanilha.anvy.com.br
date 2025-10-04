<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = $_POST['produto_id'] ?? null;
    $id_planilha = $_POST['id_planilha'] ?? null;
    $checado = $_POST['checado'] ?? 0;
    
    // Preservar filtros
    $filtros = [
        'pagina' => $_POST['pagina'] ?? 1,
        'nome' => $_POST['nome'] ?? '',
        'dependencia' => $_POST['dependencia'] ?? '',
        'codigo' => $_POST['codigo'] ?? ''
    ];
    
    if (!$produto_id || !$id_planilha) {
        // Redirecionar de volta se dados estiverem faltando
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: visualizar_planilha.php?' . $query_string);
        exit;
    }
    
    try {
        // Verificar se já existe registro
        $sql_check = "SELECT * FROM produtos_check WHERE produto_id = :produto_id";
        $stmt_check = $conexao->prepare($sql_check);
        $stmt_check->bindValue(':produto_id', $produto_id);
        $stmt_check->execute();
        $existe = $stmt_check->fetch();
        
        if ($existe) {
            // Atualizar
            $sql = "UPDATE produtos_check SET checado = :checado, atualizado_em = NOW() WHERE produto_id = :produto_id";
        } else {
            // Inserir
            $sql = "INSERT INTO produtos_check (produto_id, checado, criado_em, atualizado_em) VALUES (:produto_id, :checado, NOW(), NOW())";
        }
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':produto_id', $produto_id);
        $stmt->bindValue(':checado', $checado, PDO::PARAM_INT);
        $stmt->execute();
        
        // Redirecionar de volta mantendo os filtros
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: visualizar_planilha.php?' . $query_string);
        exit;
        
    } catch (Exception $e) {
        // Em caso de erro, redirecionar com mensagem de erro
        $query_string = http_build_query(array_merge(
            ['id' => $id_planilha], 
            $filtros,
            ['erro' => 'Erro ao processar check: ' . $e->getMessage()]
        ));
        header('Location: visualizar_planilha.php?' . $query_string);
        exit;
    }
} else {
    // Se não for POST, redirecionar para a página principal
    header('Location: index.php');
    exit;
}
?>