<?php
require_once __DIR__ . '/CRUD/conexao.php';
require_once __DIR__ . '/app/functions/produto_parser.php';

echo "=== DEBUG DETALHADO: Por que BEN está sendo sobrescrito? ===\n\n";

// Carregar tipos
$stmtTipos = $conexao->prepare("SELECT id, codigo, descricao FROM tipos_bens ORDER BY LENGTH(descricao) DESC");
$stmtTipos->execute();
$tipos_bens = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
$tipos_aliases = pp_construir_aliases_tipos($tipos_bens);

// Simular importação
$complemento_csv = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA";
$texto_base = $complemento_csv;

// Passo 1: Código
list($codigo_detectado, $texto_sem_prefixo) = pp_extrair_codigo_prefixo($texto_base);

// Passo 2: Tipo
list($tipo_detectado, $texto_pos_tipo) = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
$tipo_ben_id = (int)$tipo_detectado['id'];
$tipo_ben_codigo = $tipo_detectado['codigo'];
$tipo_bem_desc = $tipo_detectado['descricao'];

// Passo 3: Aliases
$aliases_tipo_atual = null;
$aliases_originais = null;
foreach ($tipos_aliases as $tbTmp) {
    if ($tbTmp['id'] === $tipo_ben_id) {
        $aliases_tipo_atual = $tbTmp['aliases'];
        $aliases_originais = $tbTmp['aliases_originais'] ?? null;
        break;
    }
}

echo "Tipo ID: $tipo_ben_id\n";
echo "Tipo desc: $tipo_bem_desc\n";
echo "Aliases normalizados: " . implode(", ", $aliases_tipo_atual) . "\n";
echo "Aliases originais: " . implode(", ", $aliases_originais) . "\n\n";

// Passo 4: Extrair BEN (EXATAMENTE como importação linha 206)
list($ben_raw, $comp_raw) = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual ?: [], $aliases_originais, $tipo_bem_desc);
$ben = strtoupper(preg_replace('/\s+/', ' ', trim($ben_raw)));
$complemento_limpo = strtoupper(preg_replace('/\s+/', ' ', trim($comp_raw)));

echo "=== APÓS pp_extrair_ben_complemento() ===\n";
echo "BEN: '$ben'\n";
echo "Complemento: '$complemento_limpo'\n\n";

// Passo 5: Validação (EXATAMENTE como importação linhas 210-219)
$ben_valido = false;
if ($ben !== '' && $tipo_ben_id > 0 && $aliases_tipo_atual) {
    $ben_norm = pp_normaliza($ben);
    echo "=== VALIDAÇÃO DO BEN ===\n";
    echo "BEN normalizado: '$ben_norm'\n";
    echo "Verificando contra aliases:\n";
    
    foreach ($aliases_tipo_atual as $alias_norm) {
        $match_exato = ($alias_norm === $ben_norm);
        $match_fuzzy = pp_match_fuzzy($ben, $alias_norm);
        
        echo "  - '$alias_norm': exato=" . ($match_exato ? 'SIM' : 'não') . ", fuzzy=" . ($match_fuzzy ? 'SIM' : 'não') . "\n";
        
        if ($alias_norm === $ben_norm || pp_match_fuzzy($ben, $alias_norm)) {
            $ben_valido = true;
            echo "    ✓ MATCH ENCONTRADO!\n";
            break;
        }
    }
}

echo "\nBEN é válido? " . ($ben_valido ? "SIM" : "NÃO") . "\n\n";

// Passo 6: Se inválido, forçar (linhas 222-239)
if (!$ben_valido && $tipo_ben_id > 0 && !empty($aliases_tipo_atual)) {
    echo "=== BEN INVÁLIDO - FORÇANDO PARA PRIMEIRO ALIAS ===\n";
    
    foreach ($aliases_tipo_atual as $alias_norm) {
        if ($alias_norm !== '') {
            $tokens = array_map('trim', preg_split('/\s*\/\s*/', $tipo_bem_desc));
            echo "Tokens do tipo: " . implode(" | ", $tokens) . "\n";
            
            foreach ($tokens as $tok) {
                if (pp_normaliza($tok) === $alias_norm) {
                    $ben_forcado = strtoupper($tok);
                    echo "FORÇANDO BEN para: '$ben_forcado' (alias: $alias_norm)\n";
                    $ben = $ben_forcado;
                    break 2;
                }
            }
        }
    }
}

echo "\n=== RESULTADO FINAL ===\n";
echo "BEN: '$ben'\n";
echo "Complemento: '$complemento_limpo'\n\n";

echo "ESPERADO: BEN='QUADRO MUSICAL', Complemento='LOUSA BRANCA'\n";
echo "Status: " . ($ben === 'QUADRO MUSICAL' && $complemento_limpo === 'LOUSA BRANCA' ? "✓ CORRETO" : "✗ ERRADO") . "\n";
