<?php
 // AutenticaÃ§Ã£o
/**
 * PÃ¡gina para gerar RelatÃ³rio 14.1 automaticamente
 * 
 * Uso:
 * - gerar-relatorio-14-1.php?id_planilha=123  -> Gera relatÃ³rio preenchido
 * - gerar-relatorio-14-1.php?em_branco=1      -> Gera template em branco
 * - gerar-relatorio-14-1.php?em_branco=5      -> Gera 5 pÃ¡ginas em branco
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/../../services/Relatorio141Generator.php';

// Criar gerador
$gerador = new Relatorio141Generator($pdo);

try {
    // Verificar modo de operaÃ§Ã£o
    if (isset($_GET['id_planilha'])) {
        // Modo: Gerar relatÃ³rio preenchido com dados da planilha
        $id_planilha = (int)$_GET['id_planilha'];
        $dados = $gerador->gerarRelatorio($id_planilha);
        
    } elseif (isset($_GET['em_branco'])) {
        // Modo: Gerar template em branco
        $num_paginas = max(1, (int)$_GET['em_branco']);
        $dados = $gerador->gerarEmBranco($num_paginas);
        
    } else {
        // Modo padrÃ£o: 1 pÃ¡gina em branco
        $dados = $gerador->gerarEmBranco(1);
    }
    
    // Extrair variÃ¡veis para o template
    extract($dados);
    
    // Incluir o template
    include __DIR__ . '/../../app/views/planilhas/relatorio141_template.php';
    
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao gerar relatÃ³rio: " . htmlspecialchars($e->getMessage());
}

