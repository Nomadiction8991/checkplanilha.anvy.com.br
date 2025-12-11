<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AutenticaÃ§Ã£o
/**
 * SobrepÃµe textos em um PDF existente usando FPDI (100% via Composer)
 *
 * Requer:
 *   composer require setasign/fpdi setasign/fpdf
 *
 * Uso simples (um campo):
 *   /CRUD/READ/overlay-pdf.php?file=relatorio-14-1.pdf&text=26/10/2025&x=160&y=25&size=10&page=1
 *
 * Uso avanÃ§ado (mÃºltiplos campos via JSON base64):
 *   map = base64_encode(json_encode([
 *      {"text":"26/10/2025","x":160,"y":25,"size":10,"page":1},
 *      {"text":"12345678","x":30,"y":55,"size":10,"page":1}
 *   ]))
 *   /CRUD/READ/overlay-pdf.php?file=relatorio-14-1.pdf&map=... 
 */

$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo "Autoloader do Composer nÃ£o encontrado. Instale as dependÃªncias com:\n";
    echo "composer require setasign/fpdi setasign/fpdf\n";
    exit;
}
require_once $autoloadPath;

use setasign\Fpdi\Fpdi;

// Localizar o PDF na pasta relatorios
$baseDir = realpath(__DIR__ . '/../../relatorios');
if (!$baseDir) {
    http_response_code(404);
    echo 'Pasta relatorios/ nÃ£o encontrada.';
    exit;
}

$file = $_GET['file'] ?? '';
if (!$file) {
    http_response_code(400);
    echo 'ParÃ¢metro "file" Ã© obrigatÃ³rio (ex.: relatorio-14-1.pdf).';
    exit;
}
$pdfPath = $baseDir . DIRECTORY_SEPARATOR . $file;
if (!file_exists($pdfPath)) {
    http_response_code(404);
    echo 'Arquivo nÃ£o encontrado em relatorios/: ' . htmlspecialchars($file);
    exit;
}

// Construir mapa de campos
$map = [];
if (!empty($_GET['map'])) {
    $json = base64_decode($_GET['map']);
    $data = json_decode($json, true);
    if (is_array($data)) {
        $map = $data;
    }
}

// Fallback: um Ãºnico campo via query (text,x,y,size,page)
if (empty($map) && isset($_GET['text'], $_GET['x'], $_GET['y'])) {
    $map[] = [
        'text' => (string)$_GET['text'],
        'x' => (float)$_GET['x'],
        'y' => (float)$_GET['y'],
        'size' => isset($_GET['size']) ? (float)$_GET['size'] : 10,
        'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
    ];
}

if (empty($map)) {
    http_response_code(400);
    echo 'Informe o texto e coordenadas: use ?text=...&x=...&y=... (e opcional size/page) ou passe "map" em base64.';
    exit;
}

// Abrir PDF e sobrepor textos
$pdf = new Fpdi('P', 'mm', 'A4');
$pageCount = $pdf->setSourceFile($pdfPath);

// Indexar mapa por pÃ¡gina
$porPagina = [];
foreach ($map as $item) {
    $p = max(1, min($pageCount, (int)($item['page'] ?? 1)));
    $porPagina[$p][] = $item;
}

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $pdf->AddPage();
    $tplId = $pdf->importPage($pageNo);
    $pdf->useTemplate($tplId, 0, 0, 210); // largura A4 em mm

    if (!empty($porPagina[$pageNo])) {
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
        foreach ($porPagina[$pageNo] as $it) {
            $size = isset($it['size']) ? (float)$it['size'] : 10;
            $pdf->SetFont('Helvetica', '', $size);
            $pdf->SetXY((float)$it['x'], (float)$it['y']);
            $pdf->Write(5, (string)$it['text']);
        }
    }
}

// SaÃ­da inline
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="overlay.pdf"');
$pdf->Output('I', 'overlay.pdf');


