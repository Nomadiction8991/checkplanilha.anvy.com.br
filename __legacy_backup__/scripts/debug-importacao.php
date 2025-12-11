<?php
/**
 * Debug completo do fluxo de importação para o produto 09-0040/001568
 */

// Carregar conexão do banco (igual ao sistema usa)
require_once __DIR__ . '/CRUD/conexao.php';
require_once __DIR__ . '/app/functions/produto_parser.php';

if (!$conexao) {
    die("ERRO: Não foi possível conectar ao banco de dados\n");
}

echo "✓ Conectado ao banco de dados\n\n";
echo "=== DEBUG COMPLETO DO FLUXO DE IMPORTAÇÃO ===\n\n";

// Simular dados do CSV conforme você informou
$complemento_csv = "ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA";
$codigo_csv = "09-0040";

echo "1) DADOS ORIGINAIS DO CSV:\n";
echo "   Código: $codigo_csv\n";
echo "   Complemento: $complemento_csv\n\n";

// Carregar configuração do parser
$pp_config = [
    'prefixo_codigo' => true,
    'usa_tipo_desc' => true,
    'usa_alias' => true,
    'remover_tipo_desc' => true,
    'fuzzy_match' => true,
    'debug' => true
];

// Buscar tipos e aliases do banco
$tipos_aliases = [];

$sql_tipos = "SELECT id, codigo, descricao FROM tipo_ben WHERE ativo = 1 ORDER BY codigo";
$stmt_tipos = $conexao->query($sql_tipos);
$tipos = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

foreach ($tipos as $tipo) {
    $sql_aliases = "SELECT alias FROM tipo_ben_alias WHERE tipo_ben_id = :tipo_id";
    $stmt_alias = $conexao->prepare($sql_aliases);
    $stmt_alias->execute([':tipo_id' => $tipo['id']]);
    $aliases_raw = $stmt_alias->fetchAll(PDO::FETCH_COLUMN);
    
    $tipos_aliases[] = [
        'id' => (int)$tipo['id'],
        'codigo' => $tipo['codigo'],
        'descricao' => $tipo['descricao'],
        'aliases' => pp_construir_aliases_tipos($aliases_raw),
        'aliases_originais' => $aliases_raw
    ];
}

echo "2) TIPOS CARREGADOS DO BANCO: " . count($tipos_aliases) . " tipos\n\n";

// Simular fluxo exato da importação
echo "3) EXECUTANDO FLUXO DE PARSING (igual importar-planilha.php):\n\n";

$texto_base = $complemento_csv;

// Passo 1: Remover prefixo de código
list($codigo_detectado, $texto_sem_prefixo) = pp_extrair_codigo_prefixo($texto_base);
echo "   3.1) Código detectado: " . ($codigo_detectado ?? 'nenhum') . "\n";
echo "   3.2) Texto sem prefixo: $texto_sem_prefixo\n\n";

// Passo 2: Detectar tipo
list($tipo_detectado, $texto_pos_tipo) = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
$tipo_ben_id = (int)$tipo_detectado['id'];
$tipo_ben_codigo = $tipo_detectado['codigo'];
$tipo_bem_desc = $tipo_detectado['descricao'];

echo "   3.3) Tipo detectado:\n";
echo "        ID: $tipo_ben_id\n";
echo "        Código: $tipo_ben_codigo\n";
echo "        Descrição: $tipo_bem_desc\n";
echo "   3.4) Texto pós-tipo: $texto_pos_tipo\n\n";

// Passo 3: Buscar aliases do tipo
$aliases_tipo_atual = null;
foreach ($tipos_aliases as $tb) {
    if ($tb['id'] === $tipo_ben_id) {
        $aliases_tipo_atual = $tb['aliases'];
        break;
    }
}

echo "   3.5) Aliases do tipo encontrados: " . ($aliases_tipo_atual ? count($aliases_tipo_atual) . " aliases" : "nenhum") . "\n\n";

// Passo 4: Extrair BEN e complemento
list($ben, $complemento_extraido) = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual, $pp_config);

echo "   3.6) EXTRAÇÃO BEN/COMPLEMENTO:\n";
echo "        BEN extraído: '$ben'\n";
echo "        Complemento extraído: '$complemento_extraido'\n\n";

// Passo 5: Validar BEN (simulando lógica da importação)
$ben_valido = false;
if ($tipo_ben_id > 0 && $ben !== '') {
    $sql_check = "SELECT 1 FROM ben WHERE tipo_ben_id = :tipo AND UPPER(descricao) = :ben LIMIT 1";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->execute([':tipo' => $tipo_ben_id, ':ben' => strtoupper($ben)]);
    $ben_valido = (bool)$stmt_check->fetchColumn();
}

echo "   3.7) BEN é válido no banco? " . ($ben_valido ? "SIM" : "NÃO") . "\n\n";

// Passo 6: Remover BEN do complemento (LINHA 250 - SUSPEITA!)
$complemento_limpo = pp_remover_ben_do_complemento($ben, $complemento_extraido);

echo "   3.8) APÓS pp_remover_ben_do_complemento():\n";
echo "        Complemento limpo: '$complemento_limpo'\n\n";

// Passo 7: Montar descrição final
$descricao_completa_calc = pp_montar_descricao(1, $tipo_ben_codigo, $tipo_bem_desc, $ben, $complemento_limpo, '', $pp_config);

echo "4) DESCRIÇÃO FINAL CALCULADA:\n";
echo "   '$descricao_completa_calc'\n\n";

echo "=== COMPARAÇÃO ===\n";
echo "Você disse que a descrição errada é:\n";
echo "'ESTANTES MUSICAIS E DE PARTITURAS - PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA'\n\n";
echo "A descrição calculada pelo parser é:\n";
echo "'$descricao_completa_calc'\n\n";

if ($descricao_completa_calc === $complemento_csv) {
    echo "⚠️  PROBLEMA: A descrição calculada É IGUAL ao texto original do CSV!\n";
    echo "    Isso significa que o parser NÃO está sendo usado corretamente.\n";
} else {
    echo "✓ O parser está gerando uma descrição DIFERENTE (e correta).\n";
    echo "  Se a descrição no banco está errada, pode ser:\n";
    echo "  1) Campo errado sendo salvo\n";
    echo "  2) Produto antigo não reprocessado\n";
    echo "  3) Outra parte do código sobrescrevendo a descrição\n";
}

echo "\n=== FIM DO DEBUG ===\n";

// BONUS: Verificar se o produto existe no banco e mostrar dados reais
echo "\n=== VERIFICAÇÃO NO BANCO DE DADOS ===\n\n";

$sql_produto = "SELECT id, planilha_id, codigo, descricao_completa, tipo_ben_id, ben, complemento, observacao 
                FROM produtos 
                WHERE codigo = :codigo 
                ORDER BY id DESC 
                LIMIT 1";

try {
    $stmt = $conexao->prepare($sql_produto);
    $stmt->execute([':codigo' => $codigo_csv]);
    $produto_db = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($produto_db) {
        echo "✓ Produto encontrado no banco (mais recente):\n\n";
        echo "   ID: " . $produto_db['id'] . "\n";
        echo "   Planilha ID: " . $produto_db['planilha_id'] . "\n";
        echo "   Código: " . $produto_db['codigo'] . "\n";
        echo "   Tipo BEN ID: " . $produto_db['tipo_ben_id'] . "\n";
        echo "   BEN: " . ($produto_db['ben'] ?? 'NULL') . "\n";
        echo "   Complemento: " . ($produto_db['complemento'] ?? 'NULL') . "\n";
        echo "   Observação: " . ($produto_db['observacao'] ?? 'NULL') . "\n\n";
        
        echo "   DESCRIÇÃO COMPLETA NO BANCO:\n";
        echo "   '" . $produto_db['descricao_completa'] . "'\n\n";
        
        echo "   DESCRIÇÃO CALCULADA PELO PARSER:\n";
        echo "   '$descricao_completa_calc'\n\n";
        
        if ($produto_db['descricao_completa'] === $descricao_completa_calc) {
            echo "   ✓✓✓ DESCRIÇÕES SÃO IGUAIS! Parser funcionou corretamente!\n";
        } else {
            echo "   ⚠️⚠️⚠️ DESCRIÇÕES SÃO DIFERENTES!\n";
            echo "   \n";
            echo "   Isso pode significar:\n";
            echo "   1) Produto antigo (importado antes das melhorias do parser)\n";
            echo "   2) Produto foi editado manualmente\n";
            echo "   3) Bug na importação (improvável, código parece correto)\n";
        }
    } else {
        echo "⚠️  Produto com código '$codigo_csv' NÃO encontrado no banco.\n";
        echo "    Isso significa que a importação ainda não foi feita ou falhou.\n";
    }
} catch (Exception $e) {
    echo "ERRO ao buscar produto: " . $e->getMessage() . "\n";
}

