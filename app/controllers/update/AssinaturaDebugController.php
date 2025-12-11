<?php
// Script de debug para verificar o salvamento das assinaturas

require_once dirname(__DIR__, 2) . '/bootstrap.php';

echo "<h2>Debug - VerificaÃ§Ã£o de Assinaturas</h2>";

// Verificar estrutura da tabela produtos
echo "<h3>Estrutura da tabela 'produtos':</h3>";
try {
    $sql = "DESCRIBE produtos";
    $stmt = $conexao->query($sql);
    $columns = $stmt->fetchAll();
    
    echo "<pre>";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . " - NULL: " . $col['Null'] . " - Default: " . $col['Default'] . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "Erro ao descrever tabela: " . $e->getMessage();
}

// Verificar produtos com doador_conjugue_id preenchido
echo "<h3>Produtos com doador_conjugue_id preenchido:</h3>";
try {
    $sql = "SELECT id_produto, descricao_completa, doador_conjugue_id, condicao_14_1 
            FROM produtos 
            WHERE doador_conjugue_id IS NOT NULL AND doador_conjugue_id != 0 
            LIMIT 10";
    $stmt = $conexao->query($sql);
    $produtos = $stmt->fetchAll();
    
    if (count($produtos) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID Produto</th><th>DescriÃ§Ã£o</th><th>Doador ID</th><th>CondiÃ§Ã£o</th></tr>";
        foreach ($produtos as $p) {
            echo "<tr>";
            echo "<td>" . $p['id_produto'] . "</td>";
            echo "<td>" . substr($p['descricao_completa'], 0, 50) . "...</td>";
            echo "<td>" . $p['doador_conjugue_id'] . "</td>";
            echo "<td>" . $p['condicao_14_1'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum produto assinado encontrado.</p>";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}

// Verificar dados da sessÃ£o
echo "<h3>Dados da SessÃ£o:</h3>";
echo "<pre>";
echo "ID UsuÃ¡rio: " . ($_SESSION['usuario_id'] ?? 'NÃƒO DEFINIDO') . "\n";
echo "Tipo UsuÃ¡rio: " . ($_SESSION['usuario_tipo'] ?? 'NÃƒO DEFINIDO') . "\n";
echo "Nome UsuÃ¡rio: " . ($_SESSION['usuario_nome'] ?? 'NÃƒO DEFINIDO') . "\n";
echo "</pre>";
?>


