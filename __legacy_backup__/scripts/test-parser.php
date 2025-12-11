<?php
/**
 * Script de teste do parser de produtos
 * Testa extração de BEN, complemento e validação
 */

require_once __DIR__ . '/app/functions/produto_parser.php';
$pp_config = require __DIR__ . '/app/config/produto_parser_config.php';

echo "=== TESTE DO PARSER DE PRODUTOS ===\n\n";

// Mock de tipos de bens (casos comuns do sistema)
$tipos_bens = [
    ['id' => 11, 'codigo' => 11, 'descricao' => 'PRATELEIRA / ESTANTE'],
    ['id' => 68, 'codigo' => 68, 'descricao' => 'EQUIPAMENTOS DE CLIMATIZAÇÃO'],
    ['id' => 58, 'codigo' => 58, 'descricao' => 'ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL'],
    ['id' => 1, 'codigo' => 1, 'descricao' => 'CADEIRA'],
    ['id' => 2, 'codigo' => 2, 'descricao' => 'MESA'],
    ['id' => 3, 'codigo' => 3, 'descricao' => 'ARMÁRIO'],
];
echo "✓ Carregados " . count($tipos_bens) . " tipos de bens (mock)\n\n";

// Construir aliases
$tipos_aliases = pp_construir_aliases_tipos($tipos_bens);

// Casos de teste
$casos_teste = [
    [
        'nome' => 'Caso 1: PRATELEIRA com aliases múltiplos',
        'complemento' => 'PRATELEIRA / ESTANTE ARMÁRIO DE AÇO COM 6 BANDEJAS',
        'dependencia' => 'ESPAÇO INFANTIL',
        'esperado_tipo' => 'PRATELEIRA',
        'esperado_ben' => 'PRATELEIRA',
        'esperado_comp' => 'ARMÁRIO DE AÇO COM 6 BANDEJAS'
    ],
    [
        'nome' => 'Caso 2: EQUIPAMENTO (singular) vs EQUIPAMENTOS (plural) - FUZZY MATCH',
        'complemento' => 'EQUIPAMENTO DE CLIMATIZAÇÃO AR CONDICIONADO VIX',
        'dependencia' => 'ESPAÇO INFANTIL',
        'esperado_tipo' => 'EQUIPAMENTOS DE CLIMATIZAÇÃO',
        'esperado_ben' => 'EQUIPAMENTO DE CLIMATIZAÇÃO',
        'esperado_comp' => 'AR CONDICIONADO VIX'
    ],
    [
        'nome' => 'Caso 3: Com código prefixo',
        'complemento' => '68 - EQUIPAMENTOS DE CLIMATIZAÇÃO AR CONDICIONADO SPLIT',
        'dependencia' => 'SALA 1',
        'esperado_tipo' => 'EQUIPAMENTOS DE CLIMATIZAÇÃO',
        'esperado_ben' => 'EQUIPAMENTOS DE CLIMATIZAÇÃO',
        'esperado_comp' => 'AR CONDICIONADO SPLIT'
    ],
    [
        'nome' => 'Caso 4: CADEIRA com hífen',
        'complemento' => 'CADEIRA - UNIVERSITÁRIA AZUL',
        'dependencia' => '',
        'esperado_tipo' => 'CADEIRA',
        'esperado_ben' => 'CADEIRA',
        'esperado_comp' => 'UNIVERSITÁRIA AZUL'
    ],
    [
        'nome' => 'Caso 5: ESTANTE repetida (escolha inteligente)',
        'complemento' => 'PRATELEIRA / ESTANTE ESTANTE METÁLICA 5 PRATELEIRAS',
        'dependencia' => 'DEPÓSITO',
        'esperado_tipo' => 'PRATELEIRA',
        'esperado_ben' => 'ESTANTE',
        'esperado_comp' => 'METÁLICA 5 PRATELEIRAS'
    ],
    [
        'nome' => 'Caso 6: Texto sem hífen separador',
        'complemento' => 'MESA ESCRITÓRIO RETANGULAR 1,20M',
        'dependencia' => 'SALA COORDENAÇÃO',
        'esperado_tipo' => 'MESA',
        'esperado_ben' => 'MESA',
        'esperado_comp' => 'ESCRITÓRIO RETANGULAR 1,20M'
    ],
    [
        'nome' => 'Caso 7: Plural no CSV, singular no tipo',
        'complemento' => 'CADEIRAS PLÁSTICAS EMPILHÁVEIS',
        'dependencia' => 'REFEITÓRIO',
        'esperado_tipo' => 'CADEIRA',
        'esperado_ben' => 'CADEIRAS',
        'esperado_comp' => 'PLÁSTICAS EMPILHÁVEIS'
    ],
    [
        'nome' => 'Caso 8: Tipo complexo com múltiplos aliases',
        'complemento' => 'ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA',
        'dependencia' => 'SALA DE MÚSICA',
        'esperado_tipo' => 'ESTANTES MUSICAIS',
        'esperado_ben' => 'QUADRO MUSICAL',
        'esperado_comp' => 'LOUSA BRANCA'
    ],
    [
        'nome' => 'Caso 9: Sem tipo detectável (texto livre)',
        'complemento' => 'OBJETO NÃO CATALOGADO XYZ',
        'dependencia' => '',
        'esperado_tipo' => null,
        'esperado_ben' => 'OBJETO NÃO CATALOGADO XYZ',
        'esperado_comp' => ''
    ],
    [
        'nome' => 'Caso 10: Armário com singular/plural',
        'complemento' => 'ARMÁRIO DE AÇO 2 PORTAS',
        'dependencia' => 'ALMOXARIFADO',
        'esperado_tipo' => 'ARMÁRIO',
        'esperado_ben' => 'ARMÁRIO',
        'esperado_comp' => 'DE AÇO 2 PORTAS'
    ],
    [
        'nome' => 'Caso 11: Código OT (outros) deve ser removido',
        'complemento' => 'OT-123 - MESA REDONDA MADEIRA',
        'dependencia' => 'SALA REUNIÃO',
        'esperado_tipo' => 'MESA',
        'esperado_ben' => 'MESA',
        'esperado_comp' => 'REDONDA MADEIRA'
    ],
    [
        'nome' => 'Caso 12: Texto com número no prefixo',
        'complemento' => '11 - PRATELEIRA METÁLICA 1,80M',
        'dependencia' => 'DEPÓSITO',
        'esperado_tipo' => 'PRATELEIRA',
        'esperado_ben' => 'PRATELEIRA',
        'esperado_comp' => 'METÁLICA 1,80M'
    ]
];

$total = count($casos_teste);
$passou = 0;
$falhou = 0;

foreach ($casos_teste as $i => $caso) {
    echo str_repeat("-", 80) . "\n";
    echo "TESTE " . ($i + 1) . ": " . $caso['nome'] . "\n";
    echo str_repeat("-", 80) . "\n";
    
    $complemento_original = $caso['complemento'];
    $dependencia_original = $caso['dependencia'];
    
    echo "Input:\n";
    echo "  Complemento: {$complemento_original}\n";
    echo "  Dependência: {$dependencia_original}\n\n";
    
    // 1) Extrair código prefixo
    [$codigo_detectado, $texto_sem_prefixo] = pp_extrair_codigo_prefixo($complemento_original);
    
    // 2) Detectar tipo
    [$tipo_detectado, $texto_pos_tipo] = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
    $tipo_ben_id = (int)$tipo_detectado['id'];
    $tipo_ben_codigo = $tipo_detectado['codigo'];
    $tipo_bem_desc = $tipo_detectado['descricao'];
    
    // 3) Extrair BEN e COMPLEMENTO
    $aliases_tipo_atual = null;
    $aliases_originais = null;
    if ($tipo_ben_id) {
        foreach ($tipos_aliases as $tb) {
            if ($tb['id'] === $tipo_ben_id) {
                $aliases_tipo_atual = $tb['aliases'];
                $aliases_originais = $tb['aliases_originais'] ?? null;
                break;
            }
        }
    }
    
    [$ben_raw, $comp_raw] = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual ?: [], $aliases_originais, $tipo_bem_desc);
    $ben = strtoupper(preg_replace('/\s+/', ' ', trim($ben_raw)));
    $complemento_limpo = strtoupper(preg_replace('/\s+/', ' ', trim($comp_raw)));
    
    // Validar BEN
    $ben_valido = false;
    if ($ben !== '' && $tipo_ben_id > 0 && $aliases_tipo_atual) {
        $ben_norm = pp_normaliza($ben);
        foreach ($aliases_tipo_atual as $alias_norm) {
            // Usar match fuzzy para aceitar variações
            if ($alias_norm === $ben_norm || pp_match_fuzzy($ben, $alias_norm)) {
                $ben_valido = true;
                break;
            }
        }
    }
    
    // Fallback
    if (!$ben_valido && $tipo_ben_id > 0 && !empty($aliases_tipo_atual)) {
        foreach ($aliases_tipo_atual as $alias_norm) {
            if ($alias_norm !== '') {
                $tokens = array_map('trim', preg_split('/\s*\/\s*/', $tipo_bem_desc));
                foreach ($tokens as $tok) {
                    if (pp_normaliza($tok) === $alias_norm) {
                        $ben = strtoupper($tok);
                        $ben_valido = true;
                        break 2;
                    }
                }
            }
        }
    }
    
    if ($ben === '' && $complemento_limpo === '') {
        $complemento_limpo = strtoupper(trim($texto_sem_prefixo));
    }
    
    if ($ben !== '' && $complemento_limpo !== '') {
        $complemento_limpo = pp_remover_ben_do_complemento($ben, $complemento_limpo);
    }
    
    // Montar descrição
    $descricao_final = pp_montar_descricao(1, $tipo_ben_codigo, $tipo_bem_desc, $ben, $complemento_limpo, $dependencia_original, $pp_config);
    
    // Verificar erro
    $tem_erro = ($tipo_ben_id === 0 && $codigo_detectado !== null) || ($tipo_ben_id > 0 && $ben !== '' && !$ben_valido);
    
    echo "Processamento:\n";
    echo "  Código detectado: " . ($codigo_detectado ?? 'nenhum') . "\n";
    echo "  Tipo ID: {$tipo_ben_id}\n";
    echo "  Tipo Código: " . ($tipo_ben_codigo ?? '?') . "\n";
    echo "  Tipo Desc: " . ($tipo_bem_desc ?? '?') . "\n";
    echo "  BEN extraído: '{$ben}'\n";
    echo "  BEN válido: " . ($ben_valido ? 'SIM' : 'NÃO') . "\n";
    echo "  Complemento: '{$complemento_limpo}'\n";
    echo "  Erro parsing: " . ($tem_erro ? 'SIM ❌' : 'NÃO ✓') . "\n\n";
    
    echo "Descrição final:\n";
    echo "  {$descricao_final}\n\n";
    
    // Validar resultado
    $tipo_ok = true;
    $ben_ok = true;
    $comp_ok = true;
    
    if (isset($caso['esperado_tipo'])) {
        if ($caso['esperado_tipo'] === null) {
            $tipo_ok = ($tipo_ben_id === 0);
        } else {
            $tipo_ok = stripos($tipo_bem_desc ?? '', $caso['esperado_tipo']) !== false;
        }
    }
    
    if (isset($caso['esperado_ben'])) {
        // Usar match fuzzy para comparar BEN
        $ben_ok = pp_match_fuzzy($ben, $caso['esperado_ben']);
    }
    
    if (isset($caso['esperado_comp'])) {
        $comp_ok = (pp_normaliza($complemento_limpo) === pp_normaliza($caso['esperado_comp']));
    }
    
    $teste_passou = $tipo_ok && $ben_ok && $comp_ok;
    
    if ($teste_passou) {
        echo "RESULTADO: ✅ PASSOU\n";
        $passou++;
    } else {
        echo "RESULTADO: ❌ FALHOU\n";
        if (!$tipo_ok) echo "  ❌ Tipo esperado: '{$caso['esperado_tipo']}'\n";
        if (!$ben_ok) echo "  ❌ BEN esperado: '{$caso['esperado_ben']}', obtido: '{$ben}'\n";
        if (!$comp_ok) echo "  ❌ Complemento esperado: '{$caso['esperado_comp']}', obtido: '{$complemento_limpo}'\n";
        $falhou++;
    }
    
    echo "\n";
}

echo str_repeat("=", 80) . "\n";
echo "RESUMO DOS TESTES\n";
echo str_repeat("=", 80) . "\n";
echo "Total: {$total}\n";
echo "Passou: {$passou} ✅\n";
echo "Falhou: {$falhou} ❌\n";
echo "Taxa de sucesso: " . round(($passou / $total) * 100, 1) . "%\n";
echo "\n";
?>
