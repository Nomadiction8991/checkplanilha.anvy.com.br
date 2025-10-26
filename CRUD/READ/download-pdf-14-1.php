<?php
/**
 * Gera o Relatório 14.1 em PDF 100% via Composer (mPDF), sem depender de pdftk
 * 
 * Uso:
 * - download-pdf-14-1.php?id_planilha=123   -> Gera com dados da planilha
 * - download-pdf-14-1.php?em_branco=5       -> Gera 5 páginas em branco
 */

// Autoload de bibliotecas (composer)
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/Relatorio141Generator.php';

// Verificação de mPDF
if (!class_exists('\\Mpdf\\Mpdf')) {
    http_response_code(500);
    echo "mPDF não encontrado. Instale com: <pre>composer require mpdf/mpdf</pre>";
    exit;
}

$gerador = new Relatorio141Generator($pdo);

try {
    if (isset($_GET['id_planilha'])) {
        $id_planilha = (int)$_GET['id_planilha'];
        $html = $gerador->renderizar($id_planilha);
    } else {
        $num = isset($_GET['em_branco']) ? max(1, (int)$_GET['em_branco']) : 1;
        $dados = $gerador->gerarEmBranco($num);
        extract($dados);
        ob_start();
        include __DIR__ . '/../../app/views/planilhas/relatorio-14-1-template.php';
        $html = ob_get_clean();
    }

    // Carregar CSS do relatório
    $cssPath = __DIR__ . '/../../public/assets/css/relatorio-14-1.css';
    $css = file_exists($cssPath) ? file_get_contents($cssPath) : '';

    // Instanciar mPDF (A4 Portrait)
        $mpdfClass = '\\Mpdf\\Mpdf';
        $mpdf = new $mpdfClass([
        'format' => 'A4',
        'mode' => 'utf-8',
        'margin_top' => 0,
        'margin_right' => 0,
        'margin_bottom' => 0,
        'margin_left' => 0,
    ]);

    // CSS primeiro, depois HTML
    if ($css) {
           $mpdf->WriteHTML($css, 1);
    }
        $mpdf->WriteHTML($html, 2);

    // Saída inline
    $nome = isset($id_planilha) ? "relatorio-14-1-planilha-{$id_planilha}.pdf" : 'relatorio-14-1-em-branco.pdf';
    $mpdf->Output($nome, 'I');

} catch (Exception $e) {
    http_response_code(500);
    echo 'Erro ao gerar PDF: ' . htmlspecialchars($e->getMessage());
}
