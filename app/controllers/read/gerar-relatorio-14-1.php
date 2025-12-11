<?php
 // Autenticação
/**
 * Página para gerar Relatório 14.1 automaticamente
 * 
 * Uso:
 * - gerar-relatorio-14-1.php?id_planilha=123  -> Gera relatório preenchido
 * - gerar-relatorio-14-1.php?em_branco=1      -> Gera template em branco
 * - gerar-relatorio-14-1.php?em_branco=5      -> Gera 5 páginas em branco
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/Relatorio141Generator.php';

// Criar gerador
$gerador = new Relatorio141Generator($pdo);

try {
    // Verificar modo de operação
    if (isset($_GET['id_planilha'])) {
        // Modo: Gerar relatório preenchido com dados da planilha
        $id_planilha = (int)$_GET['id_planilha'];
        $dados = $gerador->gerarRelatorio($id_planilha);
        
    } elseif (isset($_GET['em_branco'])) {
        // Modo: Gerar template em branco
        $num_paginas = max(1, (int)$_GET['em_branco']);
        $dados = $gerador->gerarEmBranco($num_paginas);
        
    } else {
        // Modo padrão: 1 página em branco
        $dados = $gerador->gerarEmBranco(1);
    }
    
    // Extrair variáveis para o template
    extract($dados);
    
    // Incluir o template
    include __DIR__ . '/../../app/views/planilhas/relatorio-14-1-template.php';
    
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao gerar relatório: " . htmlspecialchars($e->getMessage());
}

