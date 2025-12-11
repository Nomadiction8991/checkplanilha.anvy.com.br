<?php
require_once __DIR__ . '/app/functions/produto_parser.php';

echo "=== TESTE ESPECÍFICO: TIPO 58 ===\n\n";

// Simular exatamente o caso problemático
$texto_original = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA";
$tipo_descricao = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL";
$aliases_originais = ["QUADRO MUSICAL", "ESTANTE MUSICAL", "ESTANTE DE PARTITURA"];

echo "Texto original: $texto_original\n";
echo "Tipo descrição: $tipo_descricao\n";
echo "Aliases: " . implode(", ", $aliases_originais) . "\n\n";

// Construir aliases normalizados manualmente
$aliases_norm = [];
foreach ($aliases_originais as $alias) {
    $norm = pp_normaliza($alias);
    $aliases_norm[] = $norm;
    // Adicionar variações plural/singular
    if (substr($norm, -1) === 's') {
        $aliases_norm[] = substr($norm, 0, -1);
    } else {
        $aliases_norm[] = $norm . 's';
    }
}

echo "Aliases normalizados: " . implode(", ", $aliases_norm) . "\n\n";

// Executar extração
list($ben, $complemento) = pp_extrair_ben_complemento($texto_original, $aliases_norm, $aliases_originais, $tipo_descricao);

echo "=== RESULTADO ===\n\n";
echo "BEN extraído: '$ben'\n";
echo "Complemento extraído: '$complemento'\n\n";

echo "=== ESPERADO ===\n\n";
echo "BEN esperado: 'QUADRO MUSICAL'\n";
echo "Complemento esperado: 'LOUSA BRANCA'\n\n";

if ($ben === "QUADRO MUSICAL" && $complemento === "LOUSA BRANCA") {
    echo "✓✓✓ SUCESSO! Parser funcionou corretamente!\n";
} else {
    echo "✗✗✗ FALHOU! Parser não extraiu corretamente.\n";
    echo "\nPROBLEMA:\n";
    if ($ben !== "QUADRO MUSICAL") {
        echo "  - BEN incorreto: esperava 'QUADRO MUSICAL', recebeu '$ben'\n";
    }
    if ($complemento !== "LOUSA BRANCA") {
        echo "  - Complemento incorreto: esperava 'LOUSA BRANCA', recebeu '$complemento'\n";
    }
}
