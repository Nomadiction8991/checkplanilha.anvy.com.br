<?php
require_once __DIR__ . '/../../../CRUD/READ/relatorio-14-1.php';

// Carregar template completo com CSS inline
$templatePath = __DIR__ . '/../../../relatorios/14-1.html';
$templateCompleto = '';
if (file_exists($templatePath)) {
    $templateCompleto = file_get_contents($templatePath);
    // Extrair apenas o conteúdo entre <!-- A4-START --> e <!-- A4-END -->
    $start = strpos($templateCompleto, '<!-- A4-START -->');
    $end   = strpos($templateCompleto, '<!-- A4-END -->');
    if ($start !== false && $end !== false && $end > $start) {
        $a4Block = trim(substr($templateCompleto, $start + strlen('<!-- A4-START -->'), $end - ($start + strlen('<!-- A4-START -->'))));
    } else {
        $a4Block = '';
    }
    
    // Extrair o <style> do template
    preg_match('/<style>(.*?)<\/style>/s', $templateCompleto, $matchesStyle);
    $styleContent = isset($matchesStyle[1]) ? $matchesStyle[1] : '';
} else {
    $a4Block = '';
    $styleContent = '';
}

$pageTitle = 'Relatório 14.1';
$backUrl = '../shared/menu.php?id=' . urlencode($id_planilha);
$headerActions = '<button id="btnPrint" class="btn-header-action" title="Imprimir" onclick="validarEImprimir()"><i class="bi bi-printer"></i></button>';

// CSS customizado para a interface da aplicação (não do formulário)
$customCss = '
/* Formulário valores comuns */
.valores-comuns { 
    background: #f8f9fa; 
    padding: 15px; 
    border-radius: 8px; 
    margin-bottom: 15px;
    margin-top: 56px; /* espaço para toolbar fixa */
}
.valores-comuns .valores-header {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; margin-bottom: 8px;
}
.valores-comuns .valores-title { font-weight: 700; font-size: 0.95rem; color: #334155; }
.valores-comuns .toggle-btn {
    border: none; background: #667eea; color: #fff; border-radius: 8px; padding: 6px 10px; cursor: pointer; font-weight: 600;
}
.valores-comuns.collapsed .valores-content { display: none; }
.valores-comuns h6 { margin: 0 0 10px 0; font-size: 0.9rem; font-weight: 600; }
.form-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
.form-grid label { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; display: block; }
.form-grid input { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.875rem; }

/* Opções de doação (comuns) */
.opcoes-comuns { margin-top: 10px; display: grid; gap: 6px; }
.opcoes-comuns label { display: flex; align-items: flex-start; gap: 8px; font-size: 0.9rem; }
.valores-comuns label:has(input[type="checkbox"].marcado) { color: #dc3545; font-weight: 600; }

/* Container de páginas */
.paginas-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-bottom: 20px;
}

.pagina-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 15px;
    position: relative;
}

.pagina-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 2px solid #667eea;
}

.pagina-numero {
    font-weight: 600;
    color: #667eea;
    font-size: 1rem;
}

.pagina-info {
    font-size: 0.85rem;
    color: #666;
}

/* Ações do cabeçalho da página */
.pagina-actions { display: flex; gap: 8px; }
.btn-expand {
    padding: 6px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f1f5f9;
    color: #334155;
    cursor: pointer;
}
.btn-expand:hover { background: #e2e8f0; }

/* Botões de zoom no preview */


/* Barra fixa de navegação por páginas */
.page-toolbar {
    position: fixed;
    top: 68px; /* pequeno espaçamento do header */
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-width: 360px;
    z-index: 990;
    background: #ffffff;
    border: 1px solid #e5e7eb;
        border-radius: 0 0 12px 12px; /* sem cantos arredondados no topo */
    padding: 6px 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.toolbar-btn {
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    background: #667eea;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}
.toolbar-btn:disabled { background: #cbd5e1; cursor: not-allowed; }
.toolbar-counter { font-weight: 700; color: #334155; }

/* Wrapper da página A4 escalada */
.a4-viewport {
    position: relative;
    width: 100%;
    min-width: 280px; /* evita preview muito fino em telas pequenas */
    aspect-ratio: 214 / 295; /* Proporção aproximada A4 (largura/altura) */
    overflow: hidden; /* preview mantém conteúdo contido */
    background: #f1f5f9;
    border-radius: 4px;
    display: flex;
    justify-content: center;
    align-items: center; /* centraliza a miniatura como folha A4 */
    padding: 0;
}

.a4-scaled {
    /* Exibir o iframe exatamente no tamanho A4 e aplicar zoom padrão de 50% (scale 0.5).
       Usamos transform no wrapper para fazer o "zoom" visual sem alterar as dimensões físicas A4 do iframe. */
    transform-origin: top left;
    transform: scale(0.5);
    width: 95%;
    height: 95%;
}

/* Forçar dimensões A4 reais para o iframe quando estiver dentro do wrapper .a4-scaled */
.a4-scaled iframe.a4-frame {
    width: 210mm !important;
    height: 297mm !important;
    display: block;
}

/* Fundo da página (imagem do PDF) */
/* Viewer removido: estilos relacionados ao overlay/inline foram eliminados */
    position: sticky;
    top: env(safe-area-inset-top, 0px);
    z-index: 10;
    flex-shrink: 0;
}
.viewer-btn {
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f8fafc;
    color: #334155;
    cursor: pointer;
}
.viewer-btn.primary { background: #667eea; color: #fff; border-color: #667eea; }
.viewer-body {
    flex: 1;
    overflow: auto;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    overscroll-behavior: contain;
    position: relative;
}
.viewer-canvas {
    transform-origin: top center;
    width: 100%;
    min-height: 100%;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 16px;
}
.viewer-canvas iframe.a4-frame {
    display: block;
    border: 0;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.viewer-body .viewer-canvas iframe.a4-frame {
    width: 100vw !important; /* ocupar 100% da largura da página HTML */
    height: 100vh !important; /* ocupar 100% da altura da página HTML */
    margin: 0; padding: 0; display: block;
}

/* Stage (quadro branco) que envolve o iframe e força o tamanho A4 por padrão */
.viewer-stage {
    display: inline-block;
    background: #fff;
    border-radius: 4px;
    padding: 0;
    box-sizing: content-box;
}
.viewer-stage iframe.a4-frame {
    /* Forçar tamanho de folha A4 (CSS mm) por padrão — navegador converte mm para px */
    width: 210mm !important;
    height: 297mm !important;
    max-width: 100%;
    max-height: 100%;
    display: block;
    min-width: 210mm; /* assegura dimensões reais A4 como mínimo */
    min-height: 297mm;
}

/* Inline expansion dentro do card (em vez de overlay full-screen) */
.pagina-card.expanded { z-index: 1500; position: relative; }
.pagina-card.expanded .a4-viewport {
    position: relative;
    max-width: calc(100% - 8px);
    margin: 4px; /* mantém 4px de espaçamento interno conforme solicitado */
    height: auto !important;
    max-height: 80vh; /* limita altura para não ocupar a tela inteira */
    overflow: auto; /* permitir scroll interno se necessário */
    background: transparent;
}
.pagina-card.expanded .a4-scaled { transform: none !important; }
.pagina-card.expanded iframe.a4-frame {
    transform: none !important;
    width: 90% !important; /* ocupar 90% da largura do widget/card */
    height: 80vh !important; /* altura alvo 80% da viewport */
    margin: 0 auto; /* centralizar */
    box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    border-radius: 6px;
}

/* Botão de fechar quando estiver expandido inline */
.pagina-card .inline-close-btn {
    margin-left: 8px;
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
}

/* Mobile refinements */
@media (max-width: 768px) {
  .viewer-btn { padding: 10px 12px; font-size: 0.95rem; }
  .viewer-toolbar { gap: 6px; }
  .btn-expand { padding: 6px 10px; font-size: 0.9rem; }
  .page-toolbar { top: 64px; max-width: 92%; }
}

/* Ajustes específicos para telas pequenas para evitar preview muito fino */
@media (max-width: 480px) {
    .a4-viewport {
        aspect-ratio: unset; /* deixa a altura fluir */
        height: auto !important;
        max-height: 80vh; /* permite que ocupe boa parte da tela sem ficar muito fino */
        padding: 6px;
    }
    .a4-scaled { width: 100%; }
    iframe.a4-frame { width: 100% !important; height: auto !important; }
    .pagina-card { padding: 10px; }
}
';

ob_start();
?>

<?php if (count($produtos) > 0): ?>
<?php
    // Descobrir imagem de fundo, se existir
    $bgCandidates = [
        '/relatorios/relatorio-14-1-bg.png',
        '/relatorios/relatorio-14-1-bg.jpg',
        '/relatorios/relatorio-14-1-bg.jpeg',
        '/relatorios/relatorio-14-1.png',
        '/relatorios/relatorio-14-1.jpg',
        '/relatorios/ralatorio14-1.png',
        '/relatorios/ralatorio14-1.jpg',
    ];
    $bgUrl = '';
    foreach ($bgCandidates as $rel) {
        $abs = $_SERVER['DOCUMENT_ROOT'] . $rel;
        if (file_exists($abs)) { $bgUrl = $rel; break; }
    }
?>

<!-- Formulário de valores comuns -->
<div class="valores-comuns collapsed" id="valoresComuns">
    <div class="valores-header">
        <span class="valores-title"><i class="bi bi-ui-checks me-1"></i> Valores Comuns (<?php echo count($produtos); ?> páginas)</span>
        <button id="toggleValores" class="toggle-btn" type="button"><i class="bi bi-chevron-down"></i> Mostrar</button>
    </div>
    <div class="valores-content">
    <div class="form-grid">
        <div>
            <label>Administração</label>
            <input type="text" id="admin_geral" onchange="atualizarTodos('admin')">
        </div>
        <div>
            <label>Cidade</label>
            <input type="text" id="cidade_geral" onchange="atualizarTodos('cidade')">
        </div>
        <div>
            <label>Setor</label>
            <input type="text" id="setor_geral" onchange="atualizarTodos('setor')">
        </div>
        <div>
            <label>Administrador/Acessor</label>
            <input type="text" id="admin_acessor_geral" onchange="atualizarTodos('admin_acessor')">
        </div>
    </div>
    <div class="opcoes-comuns">
        <strong>Opção de Doação (aplica em todas as páginas):</strong>
        <label>
            <input type="checkbox" class="chk-comum" id="chk_comum_1" data-opcao="1">
            O bem tem mais de cinco anos de uso e o documento fiscal de aquisição está anexo.
        </label>
        <label>
            <input type="checkbox" class="chk-comum" id="chk_comum_2" data-opcao="2">
            O bem tem mais de cinco anos de uso, porém o documento fiscal de aquisição foi extraviado.
        </label>
        <label>
            <input type="checkbox" class="chk-comum" id="chk_comum_3" data-opcao="3">
            O bem tem até cinco anos de uso e o documento fiscal de aquisição está anexo.
        </label>
    </div>
    </div>
</div>

<!-- Barra de navegação por páginas -->
<div class="page-toolbar">
    <button id="btnFirstPage" class="toolbar-btn" type="button" title="Primeira página"><i class="bi bi-skip-backward-fill"></i></button>
    <button id="btnPrevPage" class="toolbar-btn" type="button" title="Anterior"><i class="bi bi-chevron-left"></i></button>
    <span class="toolbar-counter"><span id="contadorPaginaAtual">1</span> / <span id="contadorTotalPaginas"><?php echo count($produtos); ?></span></span>
    <button id="btnNextPage" class="toolbar-btn" type="button" title="Próxima"><i class="bi bi-chevron-right"></i></button>
    <button id="btnLastPage" class="toolbar-btn" type="button" title="Última página"><i class="bi bi-skip-forward-fill"></i></button>
</div>

<!-- Container de páginas -->
<div class="paginas-container">
    <?php foreach($produtos as $index => $row): ?>
        <div class="pagina-card">
            <div class="pagina-header">
                <span class="pagina-numero">
                    <i class="bi bi-file-earmark-text"></i> Página <?php echo $index + 1; ?> de <?php echo count($produtos); ?>
                </span>
                <div class="pagina-actions">
                    <!-- Visualizar removido conforme solicitado -->
                </div>
            </div>
            
            <div class="a4-viewport">
                <div class="a4-scaled">
                    <?php
                        // Preencher dados do produto no template
                        $htmlPreenchido = $a4Block;
                        if (!empty($htmlPreenchido)) {
                            $dataEmissao = date('d/m/Y');
                            $descricaoBem = '';
                            if (!empty($row['tipo_descricao'])) { $descricaoBem .= $row['tipo_descricao']; }
                            if (!empty($row['complemento'])) { $descricaoBem .= ' - ' . $row['complemento']; }
                            if (!empty($row['descricao_completa'])) { $descricaoBem .= "\n" . $row['descricao_completa']; }

                            // Injetar valores
                            $htmlPreenchido = str_replace('id="input1"', 'id="input1" value="' . htmlspecialchars($dataEmissao) . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input2"', 'id="input2" class="campo-admin"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input3"', 'id="input3" class="campo-cidade"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input4"', 'id="input4" class="campo-setor"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input5"', 'id="input5" value="' . htmlspecialchars($cnpj_planilha ?? '') . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input6"', 'id="input6" value="' . htmlspecialchars($numero_relatorio_auto ?? '') . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input7"', 'id="input7" value="' . htmlspecialchars($casa_oracao_auto ?? '') . '"', $htmlPreenchido);
                    // Opcional: injetar imagem de fundo se detectada
                    $htmlIsolado = $htmlPreenchido;
                    if (!empty($bgUrl)) {
                    $htmlIsolado = preg_replace('/(<div\s+class="a4"[^>]*>)/', '$1'.'<img class="page-bg" src="'.htmlspecialchars($bgUrl, ENT_QUOTES).'" alt="">', $htmlIsolado, 1);
                    }
                    // Montar srcdoc isolado com CSS do template
                    $styleInline = !empty($styleContent) ? $styleContent : '';
                    $srcdoc = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
                        . '<style>html,body{margin:0;padding:0;background:#fff;} ' . $styleInline . '</style>'
                        . '</head><body>' . $htmlIsolado . '</body></html>';
                                // Gerar iframe de preview (Visualizar removido — iframe permanece como miniatura)
                                $title = 'Visualização da página ' . ($index + 1);
                                echo '<iframe class="a4-frame" data-page-index="' . $index . '" title="' . htmlspecialchars($title, ENT_QUOTES) . '" aria-label="' . htmlspecialchars($title, ENT_QUOTES) . '" tabindex="0" sandbox="allow-same-origin allow-scripts allow-forms" style="width:210mm;height:297mm;" srcdoc="' . htmlspecialchars($srcdoc, ENT_QUOTES) . '"></iframe>';
                            } else {
                                echo '<div class="r141-root"><div class="a4"><p style="padding:10mm;color:#900">Template 14-1 não encontrado.</p></div></div>';
                            }
                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Nenhum produto encontrado para impressão do relatório 14.1.
</div>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_relatorio_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
