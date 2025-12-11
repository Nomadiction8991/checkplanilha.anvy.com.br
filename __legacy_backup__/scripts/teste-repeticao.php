<?php
require_once __DIR__ . '/app/functions/produto_parser.php';

echo "=== TESTE REAL COM DADOS DO BANCO ===\n\n";

// Texto EXATO que vem do CSV (APÓS remoção da descrição do tipo)
$texto = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA";

// Tipo 58: descrição e aliases
$tipo_descricao = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL";
$aliases_originais = ["ESTANTES MUSICAIS E DE PARTITURAS", "QUADRO MUSICAL"]; // Gerados da descrição

echo "Texto: $texto\n";
echo "Tipo desc: $tipo_descricao\n";
echo "Aliases: " . implode(", ", $aliases_originais) . "\n\n";

// Normalizar aliases
$aliases_norm = [];
foreach ($aliases_originais as $alias) {
    $variacoes = pp_gerar_variacoes($alias);
    $aliases_norm = array_merge($aliases_norm, $variacoes);
}
$aliases_norm = array_unique($aliases_norm);

echo "Aliases normalizados: " . implode(", ", $aliases_norm) . "\n\n";

// Executar extração (SEM passar tipo_descricao para testar)
echo "=== TESTE 1: SEM tipo_descricao ===\n";
list($ben1, $comp1) = pp_extrair_ben_complemento($texto, $aliases_norm, $aliases_originais, null);
echo "BEN: '$ben1'\n";
echo "Complemento: '$comp1'\n";
echo "Status: " . ($ben1 === "QUADRO MUSICAL" && $comp1 === "LOUSA BRANCA" ? "✓ CORRETO" : "✗ ERRADO") . "\n\n";

// Executar extração (COM tipo_descricao)
echo "=== TESTE 2: COM tipo_descricao ===\n";
list($ben2, $comp2) = pp_extrair_ben_complemento($texto, $aliases_norm, $aliases_originais, $tipo_descricao);
echo "BEN: '$ben2'\n";
echo "Complemento: '$comp2'\n";
echo "Status: " . ($ben2 === "QUADRO MUSICAL" && $comp2 === "LOUSA BRANCA" ? "✓ CORRETO" : "✗ ERRADO") . "\n\n";

// Análise do texto
echo "=== ANÁLISE ===\n\n";
$texto_norm = pp_normaliza($texto);
echo "Texto normalizado: $texto_norm\n\n";

echo "Contagem de ocorrências:\n";
foreach ($aliases_originais as $alias) {
    $alias_norm = pp_normaliza($alias);
    $pattern = '/\b' . preg_quote($alias_norm, '/') . '\b/iu';
    preg_match_all($pattern, $texto_norm, $matches);
    $count = count($matches[0]);
    echo "  '$alias' aparece $count vez(es)\n";
}

echo "\n'QUADRO MUSICAL' aparece 2x -> deveria ser escolhido como BEN!\n";
echo "'ESTANTES MUSICAIS E DE PARTITURAS' aparece 1x -> NÃO deveria ser BEN!\n";
