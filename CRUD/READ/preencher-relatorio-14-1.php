<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação
// Preenche o PDF relatorios/relatorio-14-1.pdf (ou variações) com o campo de formulário "dataemissao"
// Modos de uso:
// - Via GET:  preencher-relatorio-14-1.php?dataemissao=26/10/2025
// - Sem parâmetro: usa a data atual

// 1) Localizar o PDF (aceita variações de nome informadas)
$baseDir = realpath(PROJECT_ROOT . '/relatorios');
$possiveisArquivos = [
    'relatorio-14-1.pdf',
    'relatorio 14-1.pdf',
    'relatorio14-1.pdf',
    'ralatorio14-1.pdf', // detectado na pasta
];
$pdfPath = null;
foreach ($possiveisArquivos as $nome) {
    $caminho = $baseDir . DIRECTORY_SEPARATOR . $nome;
    if (file_exists($caminho)) {
        $pdfPath = $caminho;
        $pdfName = $nome;
        break;
    }
}

if (!$pdfPath) {
    http_response_code(404);
    echo 'PDF do relatório não encontrado na pasta relatorios/. Verifique o nome do arquivo.';
    exit;
}

// 2) Valor do campo
$dataEmissao = isset($_GET['dataemissao']) ? trim($_GET['dataemissao']) : date('d/m/Y');

// 3) Gerar FDF
function fdfEscape($str) {
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $str);
}

function gerarFdf($fields, $pdfUrl = null) {
    $fdf  = "%FDF-1.2\n";
    $fdf .= "1 0 obj\n";
    $fdf .= "<< /FDF << ";
    if ($pdfUrl) {
        $fdf .= "/F (" . fdfEscape($pdfUrl) . ") ";
    }
    $fdf .= "/Fields [ ";
    foreach ($fields as $name => $value) {
        $fdf .= "<< /T (" . fdfEscape($name) . ") /V (" . fdfEscape($value) . ") >> ";
    }
    $fdf .= "] >> >>\n";
    $fdf .= "endobj\n";
    $fdf .= "trailer\n";
    $fdf .= "<< /Root 1 0 R >>\n";
    $fdf .= "%%EOF\n";
    return $fdf;
}

// Construir URL absoluto do PDF para uso em FDF, caso o usuário abra o FDF no Acrobat
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$pdfUrl = $scheme . '://' . $host . '/relatorios/' . $pdfName;

$fdfContent = gerarFdf([
    'dataemissao' => $dataEmissao,
], $pdfUrl);

// 4) Tentar preencher com pdftk (se instalado)
function comandoExiste($cmd) {
    $where = trim(shell_exec("command -v " . escapeshellarg($cmd) . " 2>/dev/null"));
    return $where !== '';
}

$outDir = $baseDir; // salvar na mesma pasta
$fdfPath = $outDir . DIRECTORY_SEPARATOR . 'relatorio-14-1-dados.fdf';
file_put_contents($fdfPath, $fdfContent);

$saidaPdf = $outDir . DIRECTORY_SEPARATOR . 'relatorio-14-1-preenchido.pdf';

if (comandoExiste('pdftk')) {
    $cmd = sprintf(
        'pdftk %s fill_form %s output %s flatten',
        escapeshellarg($pdfPath),
        escapeshellarg($fdfPath),
        escapeshellarg($saidaPdf)
    );
    $output = [];
    $ret = 0;
    exec($cmd . ' 2>&1', $output, $ret);

    if ($ret === 0 && file_exists($saidaPdf)) {
        // Enviar o PDF preenchido direto para o navegador
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="relatorio-14-1-preenchido.pdf"');
        header('Content-Length: ' . filesize($saidaPdf));
        readfile($saidaPdf);
        exit;
    }
}

// 5) Fallback: entregar o FDF para abrir no Acrobat Reader
// Observação: abra o FDF no Acrobat/Reader (não no visualizador do navegador) para que ele carregue o PDF e aplique os dados.
header('Content-Type: application/vnd.fdf');
header('Content-Disposition: attachment; filename="relatorio-14-1-dados.fdf"');
header('Content-Length: ' . strlen($fdfContent));

echo $fdfContent;
