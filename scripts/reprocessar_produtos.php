<?php
/**
 * Script de MigraÃ§Ã£o: Reprocessar Produtos com Parser Atualizado
 * 
 * Este script reprocessa produtos existentes aplicando as melhorias do parser:
 * - DetecÃ§Ã£o inteligente de BEN (com repetiÃ§Ã£o de aliases)
 * - Fuzzy matching para plural/singular
 * - ExtraÃ§Ã£o precisa de complemento
 * 
 * USO:
 *   php scripts/reprocessar_produtos.php [--dry-run] [--limit=N] [--planilha-id=N]
 * 
 * OPÃ‡Ã•ES:
 *   --dry-run          Simula sem salvar no banco (apenas mostra o que seria alterado)
 *   --limit=N          Processa apenas N produtos
 *   --planilha-id=N    Processa apenas produtos da planilha especÃ­fica
 *   --verbose          Mostra detalhes de cada produto processado
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/services/produto_parser_service.php';

// Verificar se conexÃ£o estÃ¡ disponÃ­vel
if (!$conexao) {
    die("ERRO: NÃ£o foi possÃ­vel conectar ao banco de dados.\n");
}

// Parse argumentos da linha de comando
$options = [
    'dry_run' => in_array('--dry-run', $argv),
    'verbose' => in_array('--verbose', $argv),
    'limit' => null,
    'planilha_id' => null,
    'codigo' => null,
];

foreach ($argv as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $options['limit'] = (int)$m[1];
    }
    if (preg_match('/^--planilha-id=(\d+)$/', $arg, $m)) {
        $options['planilha_id'] = (int)$m[1];
    }
    if (preg_match('/^--codigo=(.+)$/', $arg, $m)) {
        $options['codigo'] = trim($m[1], '"\'');
    }
}

echo "=== REPROCESSAMENTO DE PRODUTOS ===\n";
echo "Modo: " . ($options['dry_run'] ? "DRY-RUN (simulaÃ§Ã£o)" : "PRODUÃ‡ÃƒO (vai salvar)") . "\n";
if ($options['limit']) echo "Limite: {$options['limit']} produtos\n";
if ($options['planilha_id']) echo "Planilha ID: {$options['planilha_id']}\n";
echo "\n";

// Carregar configuraÃ§Ã£o do parser
$pp_config = require __DIR__ . '/../config/parser/produto_parser_config.php';

// Carregar todos os tipos de bens
$sql_tipos = "SELECT id, codigo, descricao FROM tipos_bens ORDER BY codigo";
$stmt_tipos = $conexao->query($sql_tipos);
$tipos_bens = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
echo "âœ“ Carregados " . count($tipos_bens) . " tipos de bens\n";

// Construir aliases
$tipos_aliases = pp_construir_aliases_tipos($tipos_bens);
echo "âœ“ Aliases construÃ­dos\n\n";

// Buscar produtos para reprocessar
$sql_produtos = "
    SELECT 
        p.id_produto as id,
        p.planilha_id,
        p.codigo,
        p.tipo_bem_id,
        p.bem,
        p.complemento,
        p.dependencia_id,
        p.descricao_completa as descricao,
        tb.codigo as tipo_codigo,
        tb.descricao as tipo_descricao,
        d.descricao as dependencia_nome
    FROM produtos p
    LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
    LEFT JOIN dependencias d ON p.dependencia_id = d.id
    WHERE p.tipo_bem_id > 0
";

if ($options['planilha_id']) {
    $sql_produtos .= " AND p.planilha_id = " . (int)$options['planilha_id'];
}

if ($options['codigo']) {
    $sql_produtos .= " AND p.codigo = " . $conexao->quote($options['codigo']);
}

$sql_produtos .= " ORDER BY p.id_produto";

if ($options['limit']) {
    $sql_produtos .= " LIMIT " . (int)$options['limit'];
}

$stmt_produtos = $conexao->query($sql_produtos);
$produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
$total_produtos = count($produtos);

echo "Produtos a processar: $total_produtos\n";
echo str_repeat("=", 80) . "\n\n";

// Contadores
$stats = [
    'processados' => 0,
    'alterados' => 0,
    'sem_mudanca' => 0,
    'erros' => 0,
];

// Processar cada produto
foreach ($produtos as $produto) {
    $stats['processados']++;
    
    $produto_id = $produto['id'];
    $tipo_bem_id = (int)$produto['tipo_bem_id'];
    $tipo_codigo = $produto['tipo_codigo'];
    $tipo_descricao = $produto['tipo_descricao'];
    $bem_atual = $produto['bem'] ?? '';
    $complemento_atual = $produto['complemento'] ?? '';
    $descricao_atual = $produto['descricao'] ?? '';
    $dependencia_nome = $produto['dependencia_nome'] ?? '';
    
    if ($options['verbose']) {
        echo "Produto ID: $produto_id (Planilha: {$produto['planilha_id']})\n";
        echo "  Tipo: [$tipo_codigo] $tipo_descricao\n";
        echo "  BEN atual: '$bem_atual'\n";
        echo "  Complemento atual: '$complemento_atual'\n";
    }
    
    // Pegar aliases do tipo
    $aliases_tipo_atual = null;
    $aliases_originais = null;
    foreach ($tipos_aliases as $tb) {
        if ($tb['id'] === $tipo_bem_id) {
            $aliases_tipo_atual = $tb['aliases'];
            $aliases_originais = $tb['aliases_originais'] ?? null;
            break;
        }
    }
    
    if (!$aliases_tipo_atual) {
        echo "  âš  AVISO: Tipo nÃ£o encontrado nos aliases\n\n";
        $stats['erros']++;
        continue;
    }
    
    // Reprocessar: usar COMPLEMENTO como texto base (Ã© o mais prÃ³ximo do texto original do CSV)
    // porque BEN pode estar errado. Se o tipo desc aparecer no inÃ­cio do complemento, o parser vai lidar com isso.
    $texto_completo = trim($complemento_atual);
    
    // Fallback: se complemento vazio mas tem BEN, usar BEN
    if ($texto_completo === '' && $ben_atual !== '') {
        $texto_completo = trim($ben_atual);
    }
    
    if ($texto_completo === '') {
        if ($options['verbose']) echo "  âŠ˜ Produto sem texto para processar\n\n";
        $stats['sem_mudanca']++;
        continue;
    }
    
    // Extrair BEN e complemento com o parser atualizado
    [$ben_novo_raw, $comp_novo_raw] = pp_extrair_ben_complemento(
        $texto_completo, 
        $aliases_tipo_atual, 
        $aliases_originais, 
        $tipo_descricao
    );
    
    $ben_novo = strtoupper(preg_replace('/\s+/', ' ', trim($ben_novo_raw)));
    $comp_novo = strtoupper(preg_replace('/\s+/', ' ', trim($comp_novo_raw)));
    
    // Validar BEN
    $ben_valido = false;
    if ($ben_novo !== '') {
        $ben_norm = pp_normaliza($ben_novo);
        foreach ($aliases_tipo_atual as $alias_norm) {
            if ($alias_norm === $ben_norm || pp_match_fuzzy($ben_novo, $alias_norm)) {
                $ben_valido = true;
                break;
            }
        }
    }
    
    // Se BEN invÃ¡lido, forÃ§ar para um dos aliases
    if (!$ben_valido && !empty($aliases_tipo_atual)) {
        foreach ($aliases_tipo_atual as $alias_norm) {
            if ($alias_norm !== '') {
                $tokens = array_map('trim', preg_split('/\s*\/\s*/', $tipo_descricao));
                $ben_novo = strtoupper($tokens[0]);
                break;
            }
        }
    }
    
    // Montar descriÃ§Ã£o nova
    $descricao_nova = pp_montar_descricao(
        1, // quantidade
        $tipo_codigo,
        $tipo_descricao,
        $ben_novo,
        $comp_novo,
        $dependencia_nome,
        $pp_config
    );
    
    // Verificar se houve mudanÃ§a
    $mudou = (
        $ben_novo !== $ben_atual || 
        $comp_novo !== $complemento_atual || 
        $descricao_nova !== $descricao_atual
    );
    
    if ($mudou) {
        $stats['alterados']++;
        
        echo "â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”\n";
        echo "Produto ID: $produto_id\n";
        echo "Tipo: [$tipo_codigo] $tipo_descricao\n\n";
        
        if ($ben_novo !== $ben_atual) {
            echo "BEN:\n";
            echo "  Antes: '$ben_atual'\n";
            echo "  Depois: '$ben_novo'\n\n";
        }
        
        if ($comp_novo !== $complemento_atual) {
            echo "COMPLEMENTO:\n";
            echo "  Antes: '$complemento_atual'\n";
            echo "  Depois: '$comp_novo'\n\n";
        }
        
        if ($descricao_nova !== $descricao_atual) {
            echo "DESCRIÃ‡ÃƒO:\n";
            echo "  Antes: $descricao_atual\n";
            echo "  Depois: $descricao_nova\n\n";
        }
        
        // Atualizar no banco (se nÃ£o for dry-run)
        if (!$options['dry_run']) {
            $sql_update = "
                UPDATE produtos 
                SET 
                    bem = :bem,
                    complemento = :complemento,
                    descricao = :descricao,
                    editado_tipo_bem_id = tipo_bem_id,
                    editado_bem = bem,
                    editado_complemento = complemento,
                    editado_dependencia_id = dependencia_id
                WHERE id = :id
            ";
            
            try {
                $stmt = $conexao->prepare($sql_update);
                $stmt->execute([
                    ':ben' => $ben_novo,
                    ':complemento' => $comp_novo,
                    ':descricao' => $descricao_nova,
                    ':id' => $produto_id
                ]);
                echo "âœ“ Atualizado no banco\n\n";
            } catch (PDOException $e) {
                echo "âœ— ERRO ao atualizar: " . $e->getMessage() . "\n\n";
                $stats['erros']++;
            }
        } else {
            echo "âŠ˜ NÃ£o salvo (modo dry-run)\n\n";
        }
        
    } else {
        $stats['sem_mudanca']++;
        if ($options['verbose']) {
            echo "  âœ“ Sem mudanÃ§as necessÃ¡rias\n\n";
        }
    }
}

// RelatÃ³rio final
echo "\n";
echo str_repeat("=", 80) . "\n";
echo "=== RELATÃ“RIO FINAL ===\n";
echo str_repeat("=", 80) . "\n";
echo "Total processados: {$stats['processados']}\n";
echo "Alterados: {$stats['alterados']}\n";
echo "Sem mudanÃ§a: {$stats['sem_mudanca']}\n";
echo "Erros: {$stats['erros']}\n";
echo "\n";

if ($options['dry_run']) {
    echo "âš  MODO DRY-RUN - Nenhuma alteraÃ§Ã£o foi salva no banco!\n";
    echo "Execute sem --dry-run para aplicar as mudanÃ§as.\n";
} else {
    echo "âœ“ AlteraÃ§Ãµes salvas no banco de dados.\n";
}

// Fechar conexÃ£o nÃ£o Ã© necessÃ¡rio com PDO (fecha automaticamente)


