<?php
require_once __DIR__ . '/CRUD/conexao.php';

if (!$conexao) {
    die("ERRO: Não foi possível conectar ao banco de dados\n");
}

echo "✓ Conectado ao banco de dados\n\n";
echo "=== TABELAS DISPONÍVEIS ===\n\n";

try {
    $stmt = $conexao->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tabelas)) {
        echo "Nenhuma tabela encontrada no banco de dados.\n";
    } else {
        echo "Total de tabelas: " . count($tabelas) . "\n\n";
        foreach ($tabelas as $tabela) {
            echo "  - $tabela\n";
        }
    }
} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICANDO PRODUTOS ===\n\n";

try {
    $stmt = $conexao->query("SELECT COUNT(*) as total FROM produtos");
    $result = $stmt->fetch();
    echo "Total de produtos no banco: " . $result['total'] . "\n";
    
    // Verificar se existe o produto específico
    $stmt = $conexao->prepare("SELECT * FROM produtos WHERE codigo = :codigo ORDER BY id DESC LIMIT 1");
    $stmt->execute([':codigo' => '09-0040']);
    $produto = $stmt->fetch();
    
    if ($produto) {
        echo "\n✓ Produto 09-0040 encontrado!\n\n";
        echo "Dados do produto:\n";
        foreach ($produto as $campo => $valor) {
            if (!is_numeric($campo)) {
                echo "  $campo: " . ($valor ?? 'NULL') . "\n";
            }
        }
    } else {
        echo "\n⚠️  Produto 09-0040 NÃO encontrado no banco.\n";
    }
} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
