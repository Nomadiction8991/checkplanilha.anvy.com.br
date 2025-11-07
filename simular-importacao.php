<?php
require_once __DIR__ . '/CRUD/conexao.php';
require_once __DIR__ . '/app/functions/produto_parser.php';

echo "=== SIMULAÇÃO EXATA DA IMPORTAÇÃO (Planilha 30, Produto 001568) ===\n\n";

// 1. Carregar tipos do banco (igual importação)
$tipos_bens = [];
$stmtTipos = $conexao->prepare("SELECT id, codigo, descricao FROM tipos_bens ORDER BY LENGTH(descricao) DESC");
$stmtTipos->execute();
$tipos_bens = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

echo "Tipos carregados: " . count($tipos_bens) . "\n\n";

// 2. Construir aliases (igual import. linha 145)
$tipos_aliases = pp_construir_aliases_tipos($tipos_bens);

// 3. Simular linha do CSV
$complemento_csv = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA";

echo "Complemento CSV: $complemento_csv\n\n";

// 4. Executar parsing (igual importação linhas 182-206)
$pp_config = ['prefixo_codigo' => true, 'usa_tipo_desc' => true, 'usa_alias' => true, 'remover_tipo_desc' => true, 'fuzzy_match' => true, 'debug' => false];

$texto_base = $complemento_csv;

// Passo 1: Remover prefixo código
list($codigo_detectado, $texto_sem_prefixo) = pp_extrair_codigo_prefixo($texto_base);
echo "1) Código detectado: " . ($codigo_detectado ?? 'nenhum') . "\n";
echo "   Texto sem prefixo: $texto_sem_prefixo\n\n";

// Passo 2: Detectar tipo
list($tipo_detectado, $texto_pos_tipo) = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
$tipo_ben_id = (int)$tipo_detectado['id'];
$tipo_ben_codigo = $tipo_detectado['codigo'];
$tipo_bem_desc = $tipo_detectado['descricao'];

echo "2) Tipo detectado:\n";
echo "   ID: $tipo_ben_id\n";
echo "   Código: $tipo_ben_codigo\n";
echo "   Descrição: $tipo_bem_desc\n";
echo "   Texto pós-tipo: $texto_pos_tipo\n\n";

// Passo 3: Buscar aliases do tipo
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

echo "3) Aliases do tipo:\n";
if ($aliases_originais) {
    foreach ($aliases_originais as $alias_orig) {
        echo "   - $alias_orig\n";
    }
} else {
    echo "   NENHUM!\n";
}
echo "\n";

// Passo 4: Extrair BEN e complemento (LINHA 206 - A CRÍTICA!)
echo "4) Chamando pp_extrair_ben_complemento():\n";
echo "   Parâmetros:\n";
echo "   - texto: '$texto_pos_tipo'\n";
echo "   - aliases_tipo_atual: " . (is_array($aliases_tipo_atual) ? count($aliases_tipo_atual) . " aliases" : "null") . "\n";
echo "   - aliases_originais: " . (is_array($aliases_originais) ? count($aliases_originais) . " aliases" : "null") . "\n";
echo "   - tipo_descricao: '$tipo_bem_desc'\n\n";

list($ben_raw, $comp_raw) = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual ?: [], $aliases_originais, $tipo_bem_desc);
$ben = strtoupper(preg_replace('/\s+/', ' ', trim($ben_raw)));
$complemento_limpo = strtoupper(preg_replace('/\s+/', ' ', trim($comp_raw)));

echo "5) RESULTADO:\n";
echo "   BEN: '$ben'\n";
echo "   Complemento: '$complemento_limpo'\n\n";

echo "=== COMPARAÇÃO ===\n\n";
echo "ESPERADO:\n";
echo "  BEN: 'QUADRO MUSICAL'\n";
echo "  Complemento: 'LOUSA BRANCA'\n\n";

echo "OBTIDO:\n";
echo "  BEN: '$ben'\n";
echo "  Complemento: '$complemento_limpo'\n\n";

if ($ben === 'QUADRO MUSICAL' && $complemento_limpo === 'LOUSA BRANCA') {
    echo "✓✓✓ SUCESSO! Simulação funcionou corretamente!\n";
    echo "\nSe funcionou aqui mas está errado no banco, significa que:\n";
    echo "  1) Os dados foram importados com código antigo\n";
    echo "  2) OU há algum processamento adicional que corrompe os dados\n";
} else {
    echo "✗✗✗ FALHOU! Mesmo problema na simulação.\n";
    echo "\nIsso confirma que o bug está no código atual.\n";
}
