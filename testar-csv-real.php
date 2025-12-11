<?php
require_once __DIR__ . '/CRUD/conexao.php';
require_once __DIR__ . '/app/functions/produto_parser.php';

echo "=== TESTE COM TEXTO REAL DO CSV ===\n\n";

// Carregar tipos do banco
$stmtTipos = $conexao->prepare("SELECT id, codigo, descricao FROM tipos_bens ORDER BY LENGTH(descricao) DESC");
$stmtTipos->execute();
$tipos_bens = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
$tipos_aliases = pp_construir_aliases_tipos($tipos_bens);

// Texto EXATO do CSV (após remover código 58 -)
$texto_csv = "ESTANTES MUSICAIS - PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA";

echo "Texto do CSV: '$texto_csv'\n\n";

// Executar parsing
$pp_config = ['prefixo_codigo' => true, 'usa_tipo_desc' => true, 'usa_alias' => true, 'remover_tipo_desc' => true, 'fuzzy_match' => true, 'debug' => false];

// Passo 1: Código
list($codigo_detectado, $texto_sem_prefixo) = pp_extrair_codigo_prefixo($texto_csv);
echo "1) Código detectado: " . ($codigo_detectado ?? 'nenhum') . "\n";
echo "   Texto sem prefixo: '$texto_sem_prefixo'\n\n";

// Passo 2: Tipo
list($tipo_detectado, $texto_pos_tipo) = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
$tipo_ben_id = (int)$tipo_detectado['id'];
$tipo_ben_codigo = $tipo_detectado['codigo'];
$tipo_bem_desc = $tipo_detectado['descricao'];

echo "2) Tipo detectado:\n";
echo "   ID: $tipo_ben_id\n";
echo "   Código: $tipo_ben_codigo\n";
echo "   Descrição: '$tipo_bem_desc'\n";
echo "   Texto pós-tipo: '$texto_pos_tipo'\n\n";

// Passo 3: Aliases
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

if ($aliases_originais) {
    echo "3) Aliases do tipo:\n";
    foreach ($aliases_originais as $alias) {
        echo "   - $alias\n";
    }
    echo "\n";
}

// Passo 4: Extrair BEN
list($ben_raw, $comp_raw) = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual ?: [], $aliases_originais, $tipo_bem_desc);
$ben = strtoupper(preg_replace('/\s+/', ' ', trim($ben_raw)));
$complemento_limpo = strtoupper(preg_replace('/\s+/', ' ', trim($comp_raw)));

echo "4) Extração:\n";
echo "   BEN: '$ben'\n";
echo "   Complemento: '$complemento_limpo'\n\n";

// Passo 5: Validação
$ben_valido = false;
if ($ben !== '' && $tipo_ben_id > 0 && $aliases_tipo_atual) {
    $ben_norm = pp_normaliza($ben);
    foreach ($aliases_tipo_atual as $alias_norm) {
        if ($alias_norm === $ben_norm || pp_match_fuzzy($ben, $alias_norm)) {
            $ben_valido = true;
            break;
        }
    }
}

echo "5) BEN válido? " . ($ben_valido ? "SIM" : "NÃO") . "\n\n";

// Passo 6: Se inválido, forçar
if (!$ben_valido && $tipo_ben_id > 0 && !empty($aliases_tipo_atual)) {
    echo "6) BEN INVÁLIDO - Forçando para primeiro alias\n";
    foreach ($aliases_tipo_atual as $alias_norm) {
        if ($alias_norm !== '') {
            $tokens = array_map('trim', preg_split('/\s*\/\s*/', $tipo_bem_desc));
            foreach ($tokens as $tok) {
                if (pp_normaliza($tok) === $alias_norm) {
                    $ben = strtoupper($tok);
                    echo "   Forçado para: '$ben'\n";
                    break 2;
                }
            }
        }
    }
    echo "\n";
}

echo "=== RESULTADO FINAL ===\n\n";
echo "BEN: '$ben'\n";
echo "Complemento: '$complemento_limpo'\n\n";

echo "ESPERADO:\n";
echo "  BEN: 'QUADRO MUSICAL'\n";
echo "  Complemento: 'LOUSA BRANCA'\n\n";

if ($ben === 'QUADRO MUSICAL' && $complemento_limpo === 'LOUSA BRANCA') {
    echo "✓ CORRETO!\n";
} else {
    echo "✗ ERRADO!\n";
    echo "\nO problema é que o CSV tem 'ESTANTES MUSICAIS - PARTITURAS'\n";
    echo "mas o tipo no banco é 'ESTANTES MUSICAIS E DE PARTITURAS'\n";
    echo "Então o parser não consegue fazer match exato!\n";
}
