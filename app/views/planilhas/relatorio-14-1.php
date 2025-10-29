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
    /* Escala aplicada dinamicamente via JS */
    transform-origin: top center;
}

/* Fundo da página (imagem do PDF) */
.a4 { position: relative; }
.a4 { background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.a4-frame { 
border: 0; background: transparent; display: block; 
}
.page-bg {
    position: absolute;
    top: 0; left: 0;
    width: 214mm; height: 295mm;
    object-fit: cover;
    z-index: 0; pointer-events: none; opacity: 0.25; /* leve para não poluir durante edição */
}
.a4 .a4-fore { position: relative; z-index: 1; }

/* Campos editados ficam vermelhos */
.r141-root .a4 input.editado,
.r141-root .a4 textarea.editado {
    color: #dc3545 !important;
}

.r141-root .a4 label:has(input[type="checkbox"].marcado) {
    color: #dc3545 !important;
}

@media print {
    @page { size: A4; margin: 0; }
    html, body { background: #ffffff !important; margin: 0 !important; padding: 0 !important; }
    /* Remover completamente header e barra de controles na impressão */
    .app-header, .page-toolbar, .valores-comuns, .pagina-header { display: none !important; }
    /* Ignorar layout mobile (largura fixa/centralização/sombras) */
    .app-container, .mobile-wrapper, .app-content {
        width: auto !important;
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #ffffff !important;
        box-shadow: none !important;
        overflow: visible !important;
        border-radius: 0 !important;
    }
    
    .paginas-container {
        display: block;
        gap: 0;
    }
    
    .pagina-card {
        box-shadow: none;
        padding: 0;
        margin: 0;
        border-radius: 0;
        page-break-after: always;
    }
    
    .pagina-card:last-child {
        page-break-after: auto;
    }
    
    .a4-viewport {
        background: transparent;
        padding: 0;
        overflow: visible;
        height: auto !important;
        width: auto !important;
    }
    
    .a4-scaled {
        transform: none !important;
        width: 100% !important;
    }
    
    /* Cores voltam para preto */
    .r141-root .a4 input.editado,
    .r141-root .a4 textarea.editado,
    .r141-root .a4 label:has(input[type="checkbox"].marcado) {
        color: #000 !important;
    }

    /* Fundo com opacidade total na impressão */
    .page-bg { opacity: 1 !important; }
}

/* Evitar cortes de conteúdo em campos e células */
.r141-root .a4 input[type="text"], 
.r141-root .a4 textarea { 
    height: auto; 
    line-height: 1.2; 
    padding: 2px 4px; 
}
.r141-root .a4 table td { 
    overflow: visible; 
}

/* ========= Viewer em tela cheia ========= */
.viewer-overlay[hidden] { display: none !important; }
.viewer-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.85);
    z-index: 2000;
    display: flex;
    flex-direction: column;
    max-width: 100vw;
    max-height: 100vh;
    overflow: hidden;
}
.viewer-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    justify-content: center;
    padding: 10px 12px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
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
                    <button class="btn-expand" type="button" data-page-index="<?php echo $index; ?>" title="Expandir visualização">
                        <i class="bi bi-arrows-fullscreen"></i> Visualizar
                    </button>
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
                            $htmlPreenchido = str_replace('id="input8"', 'id="input8">' . htmlspecialchars($descricaoBem), $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input13"', 'id="input13" class="opcao-checkbox" data-page="' . $index . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input14"', 'id="input14" class="opcao-checkbox" data-page="' . $index . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input15"', 'id="input15" class="opcao-checkbox" data-page="' . $index . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input27"', 'id="input27" class="campo-admin-assessor"', $htmlPreenchido);

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
                            echo '<iframe class="a4-frame" data-page-index="' . $index . '" srcdoc="' . htmlspecialchars($srcdoc, ENT_QUOTES) . '"></iframe>';
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

<!-- Overlay de visualização em tela cheia -->
<div id="viewerOverlay" class="viewer-overlay" hidden data-report-id="<?php echo htmlspecialchars($id_planilha ?? '', ENT_QUOTES); ?>">
  <div class="viewer-toolbar">
    <div style="display:flex;gap:8px;align-items:center;">
      <button id="viewerClose" class="viewer-btn primary" title="Fechar"><i class="bi bi-x-lg"></i></button>
      <button id="viewerZoomOut" class="viewer-btn" title="Diminuir"><i class="bi bi-zoom-out"></i></button>
      <button id="viewerZoomIn" class="viewer-btn" title="Aumentar"><i class="bi bi-zoom-in"></i></button>
            <button id="viewerCenter" class="viewer-btn" title="Centralizar"><i class="bi bi-camera-fill"></i></button>
      <button id="viewerFit" class="viewer-btn" title="Ajustar"><i class="bi bi-arrows-angle-contract"></i></button>
      <button id="viewer100" class="viewer-btn" title="100%">100%</button>
    </div>
    <div style="margin-left:12px;flex:1;text-align:right;color:#fff; background:#334155;padding:6px 10px;border-radius:6px;">Escala: <span id="viewerScaleLabel">40%</span></div>
  </div>
  <div class="viewer-body">
    <div id="viewerCanvas" class="viewer-canvas"></div>
  </div>
</div>

<script>
// Viewer com quadro (stage) branco, suporte a zoom/pan/drag e pinch/wheel zoom.
const Viewer = (function(){
    let scale = 0.4; // escala inicial
    let currentStage = null; // stage contém o iframe
    let innerFrame = null;
    let offsetX = 0, offsetY = 0; // translation em unidades pre-scale
    const overlay = document.getElementById('viewerOverlay');
    const canvas = document.getElementById('viewerCanvas');
    const lbl = document.getElementById('viewerScaleLabel');

    function updateLabel(){ lbl.textContent = Math.round(scale*100) + '%'; }

    function createStage(srcdoc, fullpage){
        const stage = document.createElement('div');
        stage.className = 'viewer-stage';
        stage.style.background = '#fff';
        stage.style.boxShadow = '0 6px 18px rgba(0,0,0,0.18)';
        stage.style.position = 'relative';
        stage.style.overflow = 'hidden';
        stage.dataset.fullpage = fullpage ? '1' : '0';

        const f = document.createElement('iframe');
        f.className = 'a4-frame';
        f.setAttribute('srcdoc', srcdoc);
        f.style.border = '0';
        f.style.display = 'block';
        f.style.background = '#fff';
            // dimensões padrão A4 (em mm) — garante que o iframe sempre represente uma folha A4
            f.style.width = '210mm';
            f.style.height = '297mm';
            // dimensões do stage serão ajustadas para a4
            stage.style.width = '210mm';
            stage.style.height = '297mm';
        stage.appendChild(f);
        return stage;
    }

    function applyTransform(){
        if(!currentStage) return;
        currentStage.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${scale})`;
        currentStage.style.transformOrigin = 'top left';
    }

    // persistência de estado (scale + offset) por relatório
    function storageKey(){
        const id = (document.getElementById('viewerOverlay') || {}).dataset.reportId || '';
        return id ? ('r141_viewer_state_' + id) : null;
    }
    function saveState(){
        try{
            const key = storageKey(); if(!key) return;
            const state = { scale: scale, offsetX: offsetX, offsetY: offsetY, ts: Date.now() };
            localStorage.setItem(key, JSON.stringify(state));
        }catch(e){}
    }
    function loadState(){
        try{
            const key = storageKey(); if(!key) return null;
            const raw = localStorage.getItem(key); if(!raw) return null;
            return JSON.parse(raw);
        }catch(e){ return null; }
    }

    // debounce helper
    function debounce(fn, wait){ let t; return function(){ clearTimeout(t); t=setTimeout(()=>fn.apply(this, arguments), wait); }; }

    function setScale(s, centerClientX, centerClientY){
        const prevScale = scale;
        scale = Math.max(0.05, Math.min(4, s));
        if(!currentStage) { updateLabel(); return; }

        // ajustar offsets para manter o ponto do cliente sob o cursor (se informado)
        if(typeof centerClientX === 'number'){
            const rect = currentStage.parentElement.getBoundingClientRect();
            const px = centerClientX - rect.left; const py = centerClientY - rect.top;
            // conteúdo coordinate before change
            const contentX = px / prevScale - offsetX;
            const contentY = py / prevScale - offsetY;
            // new offset so that (offset'+content)*scale = px
            offsetX = px/scale - contentX;
            offsetY = py/scale - contentY;
        }

        // Se innerFrame tem dimensão do conteúdo, ajusta stage tamanho para conteúdo
        try{
            if(innerFrame){
                const doc = innerFrame.contentDocument || innerFrame.contentWindow.document;
                const a4 = doc && doc.querySelector('.r141-root .a4');
                if(a4){
                    const a4Rect = a4.getBoundingClientRect();
                    innerFrame.style.width = Math.round(a4Rect.width) + 'px';
                    innerFrame.style.height = Math.round(a4Rect.height) + 'px';
                    currentStage.style.width = innerFrame.style.width;
                    currentStage.style.height = innerFrame.style.height;
                }
            }
        }catch(e){}

        applyTransform();
        updateLabel();
    }

    function enablePan(stage){
        let dragging = false; let startX=0, startY=0;
        stage.addEventListener('pointerdown', (e)=>{
            dragging = true; startX = e.clientX; startY = e.clientY; stage.setPointerCapture(e.pointerId);
        });
        stage.addEventListener('pointermove', (e)=>{
            if(!dragging) return;
            const dx = e.clientX - startX; const dy = e.clientY - startY;
            // delta in pre-scale units
            offsetX += dx / scale; offsetY += dy / scale;
            startX = e.clientX; startY = e.clientY;
            applyTransform();
            // salvar estado de forma debounced
            saveStateDebounced();
        });
        stage.addEventListener('pointerup', (e)=>{ dragging = false; try{ stage.releasePointerCapture(e.pointerId);}catch(_){} });
        stage.addEventListener('pointercancel', ()=>{ dragging = false; });

        // wheel zoom (ctrl+wheel or pinch) - if ctrl pressed, zoom centered
        stage.addEventListener('wheel', (e)=>{
            // permitir zoom por roda sem necessidade de Ctrl — comportamento mais amigável em desktop
            e.preventDefault();
            const delta = -e.deltaY * 0.0012; // sensibilidade
            const newScale = scale * (1 + delta);
            setScale(newScale, e.clientX, e.clientY);
            saveStateDebounced();
        }, { passive:false });
    }

    function syncInputs(frameView, frameOriginal){
        try{
            const dv = frameView.contentDocument || frameView.contentWindow.document;
            const dof = frameOriginal.contentDocument || frameOriginal.contentWindow.document;
            if(!dv || !dof) return;
            dv.querySelectorAll('input, textarea, select').forEach(el=>{
                el.addEventListener('input', ()=>{ if(!el.id) return; const t=dof.getElementById(el.id); if(!t) return; if(el.type==='checkbox'||el.type==='radio') t.checked=el.checked; else t.value=el.value; });
                el.addEventListener('change', ()=>{ if(!el.id) return; const t=dof.getElementById(el.id); if(!t) return; if(el.type==='checkbox'||el.type==='radio') t.checked=el.checked; else t.value=el.value; });
            });
        }catch(e){}
    }

    function openOverlay(previewFrame){
        const srcdoc = previewFrame.getAttribute('srcdoc');
        canvas.innerHTML = '';
        currentStage = createStage(srcdoc, true);
        innerFrame = currentStage.querySelector('iframe');
        canvas.appendChild(currentStage);
        overlay.hidden = false; document.body.style.overflow = 'hidden';

        innerFrame.addEventListener('load', ()=>{
            try{
                // forçar dimensões A4 (mm) — evita que estilos externos alterem o tamanho
                innerFrame.style.width = '210mm';
                innerFrame.style.height = '297mm';
                currentStage.style.width = '210mm';
                currentStage.style.height = '297mm';
            }catch(e){}
            // tentar restaurar estado salvo
            const st = loadState();
            if(st && typeof st.scale === 'number'){
                scale = st.scale; offsetX = st.offsetX || 0; offsetY = st.offsetY || 0;
                applyTransform();
            } else {
                // centralizar por padrão
                offsetX = (overlay.getBoundingClientRect().width - (currentStage.getBoundingClientRect().width * scale)) / (2 * scale);
                offsetY = 20/scale; // pequeno padding top
                applyTransform();
                setScale(0.4);
            }
            enablePan(currentStage);
            syncInputs(innerFrame, previewFrame);
            // salvar estado inicial (debounced)
            saveStateDebounced();
        });
    }

    function openInline(previewFrame, paginaCard){
        const srcdoc = previewFrame.getAttribute('srcdoc');
        document.querySelectorAll('.pagina-card.expanded').forEach(p=>p.classList.remove('expanded'));
        paginaCard.classList.add('expanded'); document.querySelectorAll('.inline-close-btn').forEach(b=>b.remove());
        const actions = paginaCard.querySelector('.pagina-actions'); const closeBtn = document.createElement('button'); closeBtn.type='button'; closeBtn.className='inline-close-btn'; closeBtn.innerHTML='<i class="bi bi-x-lg"></i> Fechar'; if(actions) actions.appendChild(closeBtn); closeBtn.addEventListener('click', ()=>{ paginaCard.classList.remove('expanded'); closeBtn.remove(); });
        const vp = paginaCard.querySelector('.a4-viewport'); if(!vp) return; vp.innerHTML='';
        currentStage = createStage(srcdoc, false);
        innerFrame = currentStage.querySelector('iframe');
        vp.appendChild(currentStage);

        innerFrame.addEventListener('load', ()=>{
            try{ 
                // forçar A4
                innerFrame.style.width = '210mm';
                innerFrame.style.height = '297mm';
                currentStage.style.width = '210mm';
                currentStage.style.height = '297mm';
            }catch(e){}
            // tentar restaurar estado salvo
            const st = loadState();
            if(st && typeof st.scale === 'number'){
                scale = st.scale; offsetX = st.offsetX || 0; offsetY = st.offsetY || 0;
                applyTransform();
            } else {
                // centralizar no viewport do card
                const parentRect = currentStage.parentElement.getBoundingClientRect();
                offsetX = (parentRect.width - (currentStage.getBoundingClientRect().width * scale)) / (2 * scale);
                offsetY = 10/scale;
                applyTransform();
                setScale(0.4);
            }
            enablePan(currentStage);
            syncInputs(innerFrame, previewFrame);
            saveStateDebounced();
        });
    }

    function close(){ overlay.hidden=true; canvas.innerHTML=''; currentStage=null; innerFrame=null; offsetX=0; offsetY=0; document.body.style.overflow=''; document.querySelectorAll('.pagina-card.expanded').forEach(p=>p.classList.remove('expanded')); document.querySelectorAll('.inline-close-btn').forEach(b=>b.remove()); }

    function mmToPx(mm){ const el = document.createElement('div'); el.style.position='absolute'; el.style.left='-9999px'; el.style.width = mm + 'mm'; document.body.appendChild(el); const px = el.getBoundingClientRect().width; document.body.removeChild(el); return px; }

    function init(){
        document.querySelectorAll('.btn-expand').forEach(btn=>{ btn.addEventListener('click', ()=>{ const pageIndex=parseInt(btn.getAttribute('data-page-index'))||0; const paginas = document.querySelectorAll('.pagina-card'); const pagina = paginas[pageIndex]; const preview = pagina.querySelector('iframe.a4-frame'); if(window.innerWidth>=768) openInline(preview,pagina); else openOverlay(preview); }); });
        document.getElementById('viewerClose').addEventListener('click', close);
        document.getElementById('viewerZoomIn').addEventListener('click', ()=>setScale(scale+0.1));
        document.getElementById('viewerZoomOut').addEventListener('click', ()=>setScale(scale-0.1));
        document.getElementById('viewer100').addEventListener('click', ()=>setScale(1));
        document.getElementById('viewerFit').addEventListener('click', ()=>{ if(!innerFrame) return; try{ const doc = innerFrame.contentDocument || innerFrame.contentWindow.document; const a4 = doc.querySelector('.r141-root .a4'); const parent = currentStage.parentElement.getBoundingClientRect(); let a4w=0,a4h=0; if(a4){ const a4Rect = a4.getBoundingClientRect(); a4w = a4Rect.width; a4h = a4Rect.height; } else { a4w = mmToPx(210); a4h = mmToPx(297); } const scaleFit = Math.min((parent.width*0.95) / a4w, (parent.height*0.95) / a4h); // centralizar após fit
            setScale(scaleFit);
            // centralizar
            offsetX = (parent.width - (a4w * scaleFit)) / (2 * scaleFit);
            offsetY = (parent.height - (a4h * scaleFit)) / (2 * scaleFit);
            applyTransform();
            saveStateDebounced();
        }catch(e){} });

        // centralizar botão
        const centerBtn = document.getElementById('viewerCenter'); if(centerBtn){ centerBtn.addEventListener('click', ()=>{
            if(!currentStage) return; const parent = currentStage.parentElement.getBoundingClientRect(); const stRect = currentStage.getBoundingClientRect(); offsetX = (parent.width - (stRect.width * scale)) / (2 * scale); offsetY = (parent.height - (stRect.height * scale)) / (2 * scale); applyTransform(); saveStateDebounced(); }); }
    }

    return { init, setScale, openOverlay, openInline, close };
})();

document.addEventListener('DOMContentLoaded', ()=>{ Viewer.init(); });
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_relatorio_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
