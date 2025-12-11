<?php
// Script de debug para verificar o salvamento das assinaturas
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';

echo "<h2>Debug - Verificação de Assinaturas</h2>";

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
        echo "<tr><th>ID Produto</th><th>Descrição</th><th>Doador ID</th><th>Condição</th></tr>";
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

// Verificar dados da sessão
echo "<h3>Dados da Sessão:</h3>";
echo "<pre>";
echo "ID Usuário: " . ($_SESSION['usuario_id'] ?? 'NÃO DEFINIDO') . "\n";
echo "Tipo Usuário: " . ($_SESSION['usuario_tipo'] ?? 'NÃO DEFINIDO') . "\n";
echo "Nome Usuário: " . ($_SESSION['usuario_nome'] ?? 'NÃO DEFINIDO') . "\n";
echo "</pre>";
?>
