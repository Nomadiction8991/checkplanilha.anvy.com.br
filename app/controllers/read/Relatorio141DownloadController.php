<?php
 // AutenticaÃ§Ã£o
/**
 * Gera o RelatÃ³rio 14.1 em PDF 100% via Composer (mPDF), sem depender de pdftk
 * 
 * Uso:
 * - download-pdf-14-1.php?id_planilha=123   -> Gera com dados da planilha
 * - download-pdf-14-1.php?em_branco=5       -> Gera 5 pÃ¡ginas em branco
 */

// Autoload de bibliotecas (composer)
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/../../services/Relatorio141Generator.php';

// VerificaÃ§Ã£o de mPDF
if (!class_exists('\\Mpdf\\Mpdf')) {
    http_response_code(500);
    echo "mPDF nÃ£o encontrado. Instale com: <pre>composer require mpdf/mpdf</pre>";
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
        include __DIR__ . '/../../app/views/planilhas/relatorio141_template.php';
        $html = ob_get_clean();
    }

    // Carregar CSS do relatÃ³rio (preferir CSS inline do template HTML unificado)
    $css = '';
    $templateHtmlPath = __DIR__ . '/../../relatorios/14-1.html';
    if (file_exists($templateHtmlPath)) {
        $tpl = file_get_contents($templateHtmlPath);
        if (preg_match('/<style>(.*?)<\/style>/s', $tpl, $m)) {
            $css = $m[1];
        }
    }
    // Fallback: CSS antigo, se existir
    if ($css === '') {
        $cssPath = __DIR__ . '/../../public/assets/css/relatorio-14-1.css';
        if (file_exists($cssPath)) {
            $css = file_get_contents($cssPath);
        }
    }

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

    // SaÃ­da inline
    $nome = isset($id_planilha) ? "relatorio-14-1-planilha-{$id_planilha}.pdf" : 'relatorio-14-1-em-branco.pdf';
    $mpdf->Output($nome, 'I');

} catch (Exception $e) {
    http_response_code(500);
    echo 'Erro ao gerar PDF: ' . htmlspecialchars($e->getMessage());
}

