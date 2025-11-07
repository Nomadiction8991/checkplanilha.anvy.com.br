<?php
/**
 * Script de Verificação: Confirma se o parser está atualizado no servidor
 * 
 * Este script testa se as melhorias do parser estão ativas:
 * - Detecção de repetição de aliases
 * - Remoção da descrição do tipo
 * - Fuzzy matching
 * 
 * Execute no servidor ANTES de importar para garantir que o código está atualizado!
 */

require_once __DIR__ . '/app/functions/produto_parser.php';

echo "=== VERIFICAÇÃO DO PARSER ===\n\n";

// Teste 1: Função existe?
$funcoes_necessarias = [
    'pp_extrair_ben_complemento',
    'pp_gerar_variacoes',
    'pp_match_fuzzy',
    'pp_normaliza',
    'pp_construir_aliases_tipos',
    'pp_detectar_tipo',
    'pp_montar_descricao'
];

echo "1) Verificando funções necessárias:\n";
$todas_existem = true;
foreach ($funcoes_necessarias as $func) {
    $existe = function_exists($func);
    echo "   " . ($existe ? "✓" : "✗") . " $func\n";
    if (!$existe) $todas_existem = false;
}

if (!$todas_existem) {
    die("\n✗ ERRO: Funções faltando! Faça upload do arquivo produto_parser.php atualizado.\n");
}

echo "\n2) Testando caso específico (Tipo 58):\n";

// Teste 2: Caso real do tipo 58
$texto = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA";
$tipo_desc = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL";
$aliases_originais = ["ESTANTES MUSICAIS E DE PARTITURAS", "QUADRO MUSICAL"];

// Gerar aliases normalizados
$aliases_norm = [];
foreach ($aliases_originais as $alias) {
    $variacoes = pp_gerar_variacoes($alias);
    $aliases_norm = array_merge($aliases_norm, $variacoes);
}
$aliases_norm = array_unique($aliases_norm);

list($ben, $comp) = pp_extrair_ben_complemento($texto, $aliases_norm, $aliases_originais, $tipo_desc);

echo "   Texto: $texto\n";
echo "   BEN extraído: '$ben'\n";
echo "   Complemento: '$comp'\n";
echo "   Esperado: BEN='QUADRO MUSICAL', Comp='LOUSA BRANCA'\n";

if ($ben === "QUADRO MUSICAL" && $comp === "LOUSA BRANCA") {
    echo "   ✓ TESTE PASSOU!\n\n";
} else {
    echo "   ✗ TESTE FALHOU!\n";
    echo "   O parser NÃO está funcionando corretamente.\n";
    echo "   Faça upload dos arquivos atualizados!\n\n";
    die("✗ PARSER DESATUALIZADO!\n");
}

// Teste 3: Fuzzy matching
echo "3) Testando fuzzy matching (plural/singular):\n";
$match1 = pp_match_fuzzy("CADEIRA", "CADEIRAS");
$match2 = pp_match_fuzzy("EQUIPAMENTO", "EQUIPAMENTOS");

echo "   'CADEIRA' match 'CADEIRAS': " . ($match1 ? "✓ SIM" : "✗ NÃO") . "\n";
echo "   'EQUIPAMENTO' match 'EQUIPAMENTOS': " . ($match2 ? "✓ SIM" : "✗ NÃO") . "\n";

if (!$match1 || !$match2) {
    echo "   ✗ Fuzzy matching não está funcionando!\n\n";
    die("✗ PARSER DESATUALIZADO!\n");
}

echo "\n";

// Teste 4: Detecção de repetição
echo "4) Testando detecção de repetição:\n";
$texto_rep = "CADEIRA CADEIRA ESTOFADA";
$aliases_rep = ["CADEIRA", "MESA", "ARMÁRIO"];
$aliases_rep_norm = [];
foreach ($aliases_rep as $a) {
    $aliases_rep_norm = array_merge($aliases_rep_norm, pp_gerar_variacoes($a));
}

list($ben_rep, $comp_rep) = pp_extrair_ben_complemento($texto_rep, $aliases_rep_norm, $aliases_rep, null);

echo "   Texto: '$texto_rep'\n";
echo "   BEN extraído: '$ben_rep'\n";
echo "   Esperado: 'CADEIRA' (palavra repetida)\n";

if ($ben_rep === "CADEIRA") {
    echo "   ✓ Detecção de repetição funcionando!\n\n";
} else {
    echo "   ✗ Detecção de repetição NÃO está funcionando!\n";
    echo "   BEN extraído: '$ben_rep' (deveria ser 'CADEIRA')\n\n";
    die("✗ PARSER DESATUALIZADO!\n");
}

// Resultado final
echo "═══════════════════════════════════════════════════════\n";
echo "✓✓✓ PARSER ATUALIZADO E FUNCIONANDO CORRETAMENTE!\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\nVocê pode importar planilhas com segurança.\n";
echo "Os produtos serão processados corretamente.\n\n";

// Info da versão
echo "Versão do parser: 2.0 (com melhorias)\n";
echo "Data de verificação: " . date('Y-m-d H:i:s') . "\n";
