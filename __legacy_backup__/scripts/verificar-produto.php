<?php
require_once 'CRUD/conexao.php';

if (!$conexao) {
    die("Sem conexão com banco.\n");
}

$codigo = '09-0040';

$sql = "SELECT id, planilha_id, codigo, tipo_ben_id, ben, complemento, descricao, observacao 
        FROM produtos 
        WHERE codigo = :codigo 
        LIMIT 1";

try {
    $stmt = $conexao->prepare($sql);
    $stmt->execute([':codigo' => $codigo]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($produto) {
        echo "=== PRODUTO ENCONTRADO NO BANCO ===\n\n";
        foreach ($produto as $campo => $valor) {
            echo "$campo: " . ($valor ?? 'NULL') . "\n";
        }
    } else {
        echo "Produto com código '$codigo' NÃO encontrado no banco.\n";
        echo "Isso significa que o problema está na importação NOVA.\n";
    }
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
