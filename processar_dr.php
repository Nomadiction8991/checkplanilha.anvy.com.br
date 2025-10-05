<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = $_POST['produto_id'] ?? null;
    $id_planilha = $_POST['id_planilha'] ?? null;
    $dr = $_POST['dr'] ?? 0;
    
    // Preservar filtros
// Adicione o filtro de status nos arrays de filtros
$filtros = [
    'pagina' => $_POST['pagina'] ?? 1,
    'nome' => $_POST['nome'] ?? '',
    'dependencia' => $_POST['dependencia'] ?? '',
    'codigo' => $_POST['codigo'] ?? '',
    'status' => $_POST['status'] ?? '' // Adicionar esta linha
];
    
    if (!$produto_id || !$id_planilha) {
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: visualizar_planilha.php?' . $query_string);
        exit;
    }
    
    try {
        // Se estiver marcando DR (dr = 1), limpar observações e desmarcar imprimir
        if ($dr == 1) {
            // Verificar se já existe registro
            $sql_check = "SELECT * FROM produtos_check WHERE produto_id = :produto_id";
            $stmt_check = $conexao->prepare($sql_check);
            $stmt_check->bindValue(':produto_id', $produto_id);
            $stmt_check->execute();
            $existe = $stmt_check->fetch();
            
            if ($existe) {
                // Atualizar - limpar observações e desmarcar imprimir
                $sql = "UPDATE produtos_check SET dr = :dr, observacoes = '', imprimir = 0 WHERE produto_id = :produto_id";
            } else {
                // Inserir com valores padrão
                $sql = "INSERT INTO produtos_check (produto_id, dr, observacoes, imprimir) VALUES (:produto_id, :dr, '', 0)";
            }
        } else {
            // Se estiver desmarcando DR, manter outros valores
            $sql_check = "SELECT * FROM produtos_check WHERE produto_id = :produto_id";
            $stmt_check = $conexao->prepare($sql_check);
            $stmt_check->bindValue(':produto_id', $produto_id);
            $stmt_check->execute();
            $existe = $stmt_check->fetch();
            
            if ($existe) {
                $sql = "UPDATE produtos_check SET dr = :dr WHERE produto_id = :produto_id";
            } else {
                $sql = "INSERT INTO produtos_check (produto_id, dr) VALUES (:produto_id, :dr)";
            }
        }
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':produto_id', $produto_id);
        $stmt->bindValue(':dr', $dr, PDO::PARAM_INT);
        $stmt->execute();
        
        // Redirecionar de volta mantendo os filtros
        $query_string = http_build_query(array_merge(['id' => $id_planilha], $filtros));
        header('Location: visualizar_planilha.php?' . $query_string);
        exit;
        
    } catch (Exception $e) {
        $query_string = http_build_query(array_merge(
            ['id' => $id_planilha], 
            $filtros,
            ['erro' => 'Erro ao processar DR: ' . $e->getMessage()]
        ));
        header('Location: visualizar_planilha.php?' . $query_string);
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}