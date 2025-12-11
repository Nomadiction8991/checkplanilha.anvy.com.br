<?php
require_once __DIR__ . '/CRUD/conexao.php';
require_once __DIR__ . '/app/functions/produto_parser.php';

echo "=== SIMULAÇÃO COMPLETA COM CÓDIGO 58 ===\n\n";

// Carregar tipos
$stmtTipos = $conexao->prepare("SELECT id, codigo, descricao FROM tipos_bens ORDER BY LENGTH(descricao) DESC");
$stmtTipos->execute();
$tipos_bens = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
$tipos_aliases = pp_construir_aliases_tipos($tipos_bens);

// Texto COMPLETO do CSV (COM código 58 -)
$complemento_original = "58 - ESTANTES MUSICAIS - PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA";

echo "Complemento CSV: '$complemento_original'\n\n";

// EXATAMENTE como importação linhas 182-206
$texto_base = $complemento_original;

// 1) Remover prefixo de código
list($codigo_detectado, $texto_sem_prefixo) = pp_extrair_codigo_prefixo($texto_base);
echo "1) Código detectado: " . ($codigo_detectado ?? 'NULL') . "\n";
echo "   Texto sem prefixo: '$texto_sem_prefixo'\n\n";

// 2) Detectar tipo
list($tipo_detectado, $texto_pos_tipo) = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
$tipo_ben_id = (int)$tipo_detectado['id'];
$tipo_ben_codigo = $tipo_detectado['codigo'];
$tipo_bem_desc = $tipo_detectado['descricao'];

echo "2) Tipo detectado:\n";
echo "   ID: $tipo_ben_id\n";
echo "   Código: $tipo_ben_codigo\n";
echo "   Descrição: '$tipo_bem_desc'\n\n";

// 3) Aliases
$aliases_tipo_atual = null;
$aliases_originais = null;
if ($tipo_ben_id) {
    foreach ($tipos_aliases as $tbTmp) {
        if ($tbTmp['id'] === $tipo_ben_id) {
            $aliases_tipo_atual = $tbTmp['aliases'];
            $aliases_originais = $tbTmp['aliases_originais'] ?? null;
            break;
        }
    }
}

// 4) Extrair BEN
list($ben_raw, $comp_raw) = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual ?: [], $aliases_originais, $tipo_bem_desc);
$ben = strtoupper(preg_replace('/\s+/', ' ', trim($ben_raw)));
$complemento_limpo = strtoupper(preg_replace('/\s+/', ' ', trim($comp_raw)));

echo "3) BEN e Complemento:\n";
echo "   BEN: '$ben'\n";
echo "   Complemento: '$complemento_limpo'\n\n";

echo "=== COMPARAÇÃO ===\n\n";
echo "OBTIDO: BEN='$ben', Comp='$complemento_limpo'\n";
echo "ESPERADO: BEN='QUADRO MUSICAL', Comp='LOUSA BRANCA'\n\n";

if ($ben === 'QUADRO MUSICAL' && $complemento_limpo === 'LOUSA BRANCA') {
    echo "✓✓✓ FUNCIONA COM CÓDIGO 58!\n";
} else {
    echo "✗ Ainda há problema mesmo com código 58!\n";
}
