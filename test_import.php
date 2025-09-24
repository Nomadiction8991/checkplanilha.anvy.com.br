<?php
// test_import.php - Script para testar a importação
require_once 'config.php';
require_once 'PlanilhaProcessor.php';

$db = new Database();
$pdo = $db->getConnection();
$processor = new PlanilhaProcessor($pdo);

// Teste de inserção manual
try {
    $result = $processor->inserirLinha(
        '09-0040 / 000001',
        '60 - CONSTRUÇÃO EDIFICACAO E ALVENARIA (CONSTRUCAO)',
        'NC',
        'BR 09-0040',
        '1100',
        '0',
        'TEMPLO',
        '2006-12-31',
        4549.94,
        1897.02,
        2652.92,
        'Ativo'
    );
    
    if ($result) {
        echo "Item inserido com sucesso!\n";
    } else {
        echo "Erro ao inserir item.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}