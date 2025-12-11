<?php
$linha = '09-0040 / 001568,,,58 - ESTANTES MUSICAIS - PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA ,,,NC,,,,BR 09-0040,1101,,0,,SALA DE MUSICA,,,,31/12/2006,,"1,00","1,00",,,,,"0,00",,,,Depreciado';

$dados = str_getcsv($linha);

echo "=== ANÁLISE DA LINHA DO CSV ===\n\n";
echo "Total de colunas: " . count($dados) . "\n\n";
echo "Colunas com dados:\n";

for ($i = 0; $i < count($dados); $i++) {
    if (trim($dados[$i]) !== '') {
        echo "  Coluna $i: '" . $dados[$i] . "'\n";
    }
}

echo "\n=== DADOS IMPORTANTES ===\n\n";
echo "Código (col 0): " . $dados[0] . "\n";
echo "Nome/Complemento (col 3): " . $dados[3] . "\n";
echo "Dependência (col 14): " . ($dados[14] ?? 'N/A') . "\n";

echo "\n=== PARSEANDO O NOME ===\n\n";
$nome = $dados[3];
echo "Texto completo: '$nome'\n\n";

// Extrair código do tipo
if (preg_match('/^(\d+)\s*-\s*(.+)$/', $nome, $m)) {
    echo "Código do tipo detectado: " . $m[1] . "\n";
    echo "Resto do texto: '" . $m[2] . "'\n";
}
