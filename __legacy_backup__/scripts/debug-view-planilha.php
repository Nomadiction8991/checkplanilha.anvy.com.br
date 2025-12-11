<?php
// SCRIPT DE DEBUG - Execute este arquivo no servidor para verificar o problema
require_once __DIR__ . '/CRUD/conexao.php';

echo "<h2>🔍 Debug View-Planilha.php</h2>";

// 1. Verificar estrutura da tabela produtos
echo "<h3>1. Estrutura da tabela PRODUTOS:</h3>";
try {
    $stmt = $conexao->query("SHOW COLUMNS FROM produtos");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($colunas);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>ERRO: " . $e->getMessage() . "</p>";
}

// 2. Verificar se existem tabelas antigas
echo "<h3>2. Verificar tabelas antigas (devem retornar vazio):</h3>";
$tabelas_antigas = ['produtos_check', 'produtos_cadastro', 'config_planilha'];
foreach ($tabelas_antigas as $tabela) {
    try {
        $stmt = $conexao->query("SHOW TABLES LIKE '$tabela'");
        $resultado = $stmt->fetch();
        if ($resultado) {
            echo "<p style='color:orange'>⚠️ Tabela <strong>$tabela</strong> ainda existe!</p>";
        } else {
            echo "<p style='color:green'>✅ Tabela <strong>$tabela</strong> não existe (correto)</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>ERRO ao verificar $tabela: " . $e->getMessage() . "</p>";
    }
}

// 3. Testar query básica com alias
echo "<h3>3. Testar query SELECT com alias 'p':</h3>";
try {
    $sql_teste = "SELECT p.id, p.codigo, p.nome 
                  FROM produtos p 
                  WHERE p.planilha_id = 1 
                  LIMIT 5";
    echo "<p><strong>SQL:</strong> $sql_teste</p>";
    $stmt = $conexao->prepare($sql_teste);
    $stmt->execute();
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($resultado);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERRO: " . $e->getMessage() . "</p>";
}

// 4. Testar query sem alias
echo "<h3>4. Testar query SELECT sem alias:</h3>";
try {
    $sql_teste2 = "SELECT id, codigo, nome 
                   FROM produtos 
                   WHERE planilha_id = 1 
                   LIMIT 5";
    echo "<p><strong>SQL:</strong> $sql_teste2</p>";
    $stmt = $conexao->prepare($sql_teste2);
    $stmt->execute();
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($resultado);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERRO: " . $e->getMessage() . "</p>";
}

// 5. Verificar planilhas disponíveis
echo "<h3>5. Planilhas disponíveis para teste:</h3>";
try {
    $stmt = $conexao->query("SELECT id, comum_id, data_importacao FROM planilhas LIMIT 5");
    $planilhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($planilhas);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>ERRO: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>✅ Diagnóstico Completo!</h3>";
echo "<p>Copie todo o resultado acima e me envie.</p>";
?>
