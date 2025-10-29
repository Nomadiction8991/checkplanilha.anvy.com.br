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
    background: #f8f9fa;
    border-radius: 4px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 0;
}

.a4-scaled {
    /* Escala aplicada dinamicamente via JS */
    transform-origin: top center;
}

/* Fundo da página (imagem do PDF) */
.a4 { position: relative; }
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
    background: rgba(0,0,0,0.7);
    z-index: 2000;
    display: flex;
    flex-direction: column;
}
.viewer-toolbar {
    display: flex;
    gap: 8px;
    align-items: center;
    justify-content: center;
    padding: 10px;
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
}
.viewer-btn {
    padding: 8px 12px;
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
    padding: 16px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}
.viewer-canvas {
    transform-origin: top left;
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
                    <!-- CSS inline extraído do template -->
                    <?php if (!empty($styleContent)): ?>
                    <style><?php echo $styleContent; ?></style>
                    <?php endif; ?>
                    
                    <?php
                        // Preencher dados do produto no template
                        $htmlPreenchido = $a4Block;
                        
                        if (!empty($htmlPreenchido)) {
                            // Data de emissão atual
                            $dataEmissao = date('d/m/Y');
                            
                            // Descrição do bem
                            $descricaoBem = '';
                            if (!empty($row['tipo_descricao'])) {
                                $descricaoBem .= $row['tipo_descricao'];
                            }
                            if (!empty($row['complemento'])) {
                                $descricaoBem .= ' - ' . $row['complemento'];
                            }
                            if (!empty($row['descricao_completa'])) {
                                $descricaoBem .= "\n" . $row['descricao_completa'];
                            }
                            
                            // Substituir valores nos inputs
                            $htmlPreenchido = str_replace('id="input1"', 'id="input1" value="' . htmlspecialchars($dataEmissao) . '"', $htmlPreenchido);
                            
                            // Seção A - Localidade (vazios por padrão, preenchidos pelos valores comuns via JS)
                            $htmlPreenchido = str_replace('id="input2"', 'id="input2" class="campo-admin"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input3"', 'id="input3" class="campo-cidade"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input4"', 'id="input4" class="campo-setor"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input5"', 'id="input5" value="' . htmlspecialchars($cnpj_planilha ?? '') . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input6"', 'id="input6" value="' . htmlspecialchars($numero_relatorio_auto ?? '') . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input7"', 'id="input7" value="' . htmlspecialchars($casa_oracao_auto ?? '') . '"', $htmlPreenchido);
                            
                            // Seção B - Descrição do bem
                            $htmlPreenchido = str_replace('id="input8"', 'id="input8">' . htmlspecialchars($descricaoBem), $htmlPreenchido);
                            
                            // Campos vazios para nota fiscal (inputs 9-12)
                            // Checkboxes (inputs 13-15) - dependem dos valores comuns
                            $htmlPreenchido = str_replace('id="input13"', 'id="input13" class="opcao-checkbox" data-page="' . $index . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input14"', 'id="input14" class="opcao-checkbox" data-page="' . $index . '"', $htmlPreenchido);
                            $htmlPreenchido = str_replace('id="input15"', 'id="input15" class="opcao-checkbox" data-page="' . $index . '"', $htmlPreenchido);
                            
                            // Seção C - Doador (inputs 17-26) - vazios
                            
                            // Seção D - Termo de aceite (inputs 27-30)
                            $htmlPreenchido = str_replace('id="input27"', 'id="input27" class="campo-admin-assessor"', $htmlPreenchido);
                            
                            echo $htmlPreenchido;
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
<div id="viewerOverlay" class="viewer-overlay" hidden>
    <div class="viewer-toolbar">
        <button type="button" class="viewer-btn" id="viewerClose"><i class="bi bi-x-lg"></i> Fechar</button>
        <button type="button" class="viewer-btn" id="viewerZoomOut"><i class="bi bi-zoom-out"></i></button>
        <button type="button" class="viewer-btn" id="viewerZoomIn"><i class="bi bi-zoom-in"></i></button>
        <button type="button" class="viewer-btn" id="viewerFit"><i class="bi bi-arrows-angle-contract"></i> Ajustar</button>
        <button type="button" class="viewer-btn" id="viewer100"><i class="bi bi-aspect-ratio"></i> 100%</button>
        <span id="viewerScaleLabel" style="margin-left:8px;color:#fff;background:#334155;padding:4px 8px;border-radius:6px;">100%</span>
    </div>
    <div class="viewer-body">
        <div id="viewerCanvas" class="viewer-canvas"></div>
    </div>
  
</div>

<script>
// Armazenar valores iniciais dos campos
const valoresOriginais = new Map();

document.addEventListener('DOMContentLoaded', () => {
    inicializarDeteccaoEdicao();
    configurarNavegacaoPaginas();
    ajustarEscalaPaginas();
    window.addEventListener('load', ajustarEscalaPaginas);
    window.addEventListener('resize', ajustarEscalaPaginas);
    configurarOpcoesComuns();
    configurarToggleValores();
    configurarViewer();
});

// Detectar edição manual em inputs e textareas
function inicializarDeteccaoEdicao() {
    document.querySelectorAll('.r141-root .a4 input[type="text"], .r141-root .a4 textarea').forEach(campo => {
        valoresOriginais.set(campo.id, campo.value);
        
        campo.addEventListener('input', function() {
            const valorOriginal = valoresOriginais.get(this.id);
            if (this.value !== valorOriginal && this.value !== '') {
                this.classList.add('editado');
            } else {
                this.classList.remove('editado');
            }
        });
    });
    
    // Detectar checkboxes marcados
    document.querySelectorAll('.r141-root .a4 input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                this.classList.add('marcado');
            } else {
                this.classList.remove('marcado');
            }
        });
    });
}

// Atualizar todos os campos
function atualizarTodos(tipo) {
    const valor = document.getElementById(tipo + '_geral').value;
    let selectorClass;
    
    switch(tipo) {
        case 'admin': 
            selectorClass = '.campo-admin';
            break;
        case 'cidade': 
            selectorClass = '.campo-cidade';
            break;
        case 'setor': 
            selectorClass = '.campo-setor';
            break;
        case 'admin_acessor': 
            selectorClass = '.campo-admin-assessor';
            break;
        default: 
            selectorClass = '.campo-' + tipo;
    }
    
    const inputs = document.querySelectorAll(selectorClass);
    inputs.forEach(input => {
        input.value = valor;
        if (valor !== '') {
            input.classList.add('editado');
        }
    });
}

// Apenas 1 checkbox por página
document.querySelectorAll('.opcao-checkbox').forEach(chk => {
    chk.addEventListener('change', () => {
        if (chk.checked) {
            const pageIndex = chk.dataset.page;
            document.querySelectorAll(`.opcao-checkbox[data-page="${pageIndex}"]`).forEach(other => {
                if (other !== chk) other.checked = false;
            });
        }
    });
});

// Imprimir sem validação obrigatória
function validarEImprimir() {
    // Removida validação obrigatória de checkboxes
    // Permite impressão mesmo sem campos preenchidos
    window.print();
}

// ---------- Navegação por páginas (scroll e contador) ----------
let paginaAtual = 0;

function configurarNavegacaoPaginas() {
    const paginas = document.querySelectorAll('.pagina-card');
    const btnFirst = document.getElementById('btnFirstPage');
    const btnPrev = document.getElementById('btnPrevPage');
    const btnNext = document.getElementById('btnNextPage');
    const btnLast = document.getElementById('btnLastPage');
    const lblAtual = document.getElementById('contadorPaginaAtual');
    const total = paginas.length;

    function atualizarUI() {
        lblAtual.textContent = paginaAtual + 1;
        btnPrev.disabled = paginaAtual === 0;
        btnNext.disabled = paginaAtual >= total - 1;
        btnFirst.disabled = paginaAtual === 0;
        btnLast.disabled = paginaAtual >= total - 1;
    }

    function irParaPagina(index, smooth = true) {
        paginaAtual = Math.max(0, Math.min(index, total - 1));
        paginas[paginaAtual].scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
        atualizarUI();
    }

    btnFirst.addEventListener('click', () => irParaPagina(0));
    btnPrev.addEventListener('click', () => irParaPagina(paginaAtual - 1));
    btnNext.addEventListener('click', () => irParaPagina(paginaAtual + 1));
    btnLast.addEventListener('click', () => irParaPagina(total - 1));

    // Observa o scroll para atualizar o indicador com base na página visível
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const index = Array.from(paginas).indexOf(entry.target);
                if (index !== -1) {
                    paginaAtual = index;
                    atualizarUI();
                }
            }
        });
    }, { root: null, threshold: 0.5 });

    paginas.forEach(p => observer.observe(p));
    atualizarUI();
}

// ---------- Escala dinâmica das páginas para caber no container ----------
function ajustarEscalaPaginas() {
    // Para cada viewport, calcula a escala da .a4 para caber na largura disponível
    document.querySelectorAll('.a4-viewport').forEach(view => {
        const scaled = view.querySelector('.a4-scaled');
        const a4 = view.querySelector('.r141-root .a4');
        if (!scaled || !a4) return;

        // Reseta para medir tamanho real
        scaled.style.transform = 'none';
        scaled.style.width = '';

        const viewportWidth = view.clientWidth;
        const viewportHeight = view.clientHeight;
        const rect = a4.getBoundingClientRect();
        const a4Width = rect.width; // px
        const a4Height = rect.height; // px

        if (a4Width === 0 || !isFinite(a4Width)) return;
        let scaleX = viewportWidth / a4Width;
        let scaleY = viewportHeight && a4Height ? (viewportHeight / a4Height) : scaleX;
        let scale = Math.min(scaleX, scaleY);
        if (scale > 1) scale = 1; // não amplia além de 100%

        // Aplica escala e ajusta largura
        scaled.style.transform = `scale(${scale})`;
        scaled.style.transformOrigin = 'top center';
        scaled.style.width = `${(1/scale)*100}%`;
    });
}

// ---------- Opções comuns (checkbox) ----------
function configurarOpcoesComuns() {
    const comuns = document.querySelectorAll('.chk-comum');
    if (!comuns.length) return;

    function marcarComum(elem) {
        // Exclusividade no grupo comum
        comuns.forEach(c => {
            if (c !== elem) { c.checked = false; c.classList.remove('marcado'); }
        });
        if (elem.checked) elem.classList.add('marcado'); else elem.classList.remove('marcado');

        // Aplica em todas as páginas
        const opc = elem.dataset.opcao; // '1' | '2' | '3'
        const totalPaginas = document.querySelectorAll('.pagina-card').length;
        for (let i = 0; i < totalPaginas; i++) {
            const seletores = [
                `.opcao-checkbox[name="opcao_1_${getIdByIndex(i)}"]`,
                `.opcao-checkbox[name="opcao_2_${getIdByIndex(i)}"]`,
                `.opcao-checkbox[name="opcao_3_${getIdByIndex(i)}"]`
            ];

            // Nem sempre temos forma simples de mapear id pelo índice; como alternativa, usamos data-page
            const checksPagina = document.querySelectorAll(`.opcao-checkbox[data-page="${i}"]`);
            checksPagina.forEach(chk => {
                const isOpcaoSelecionada = chk.id.includes(`opcao_${opc}_`);
                chk.checked = isOpcaoSelecionada;
                if (isOpcaoSelecionada) chk.classList.add('marcado'); else chk.classList.remove('marcado');
            });
        }
    }

    comuns.forEach(chk => {
        chk.addEventListener('change', () => marcarComum(chk));
    });
}

// Helper opcional caso precisemos mapear pelo index (mantido para futura extensão)
function getIdByIndex(index) { return ''; }

// ---------- Retrátil: Valores Comuns ----------
function configurarToggleValores() {
    const container = document.getElementById('valoresComuns');
    const btn = document.getElementById('toggleValores');
    if (!container || !btn) return;
    const iconDown = '<i class="bi bi-chevron-down"></i>';
    const iconUp = '<i class="bi bi-chevron-up"></i>';

    function atualizarRotulo() {
        if (container.classList.contains('collapsed')) {
            btn.innerHTML = iconDown + ' Mostrar';
        } else {
            btn.innerHTML = iconUp + ' Ocultar';
        }
    }

    btn.addEventListener('click', () => {
        container.classList.toggle('collapsed');
        atualizarRotulo();
        // Recalcula escala porque a altura do viewport pode mudar
        setTimeout(ajustarEscalaPaginas, 50);
    });

    atualizarRotulo();
}

// ========== Viewer em tela cheia ==========
const viewer = {
    overlay: null, canvas: null,
    scale: 1,
    originalPage: null,
    clonePage: null
};

function configurarViewer() {
    viewer.overlay = document.getElementById('viewerOverlay');
    viewer.canvas = document.getElementById('viewerCanvas');
    const btnClose = document.getElementById('viewerClose');
    const btnIn = document.getElementById('viewerZoomIn');
    const btnOut = document.getElementById('viewerZoomOut');
    const btnFit = document.getElementById('viewerFit');
    const btn100 = document.getElementById('viewer100');

    document.querySelectorAll('.btn-expand').forEach(btn => {
        btn.addEventListener('click', () => {
            const pageIndex = parseInt(btn.getAttribute('data-page-index')) || 0;
            abrirViewer(pageIndex);
        });
    });

    btnClose.addEventListener('click', fecharViewer);
    btnIn.addEventListener('click', () => setViewerScale(viewer.scale + 0.1));
    btnOut.addEventListener('click', () => setViewerScale(viewer.scale - 0.1));
    btnFit.addEventListener('click', ajustarViewerParaLargura);
    btn100.addEventListener('click', () => setViewerScale(1));
}

function abrirViewer(pageIndex) {
    const paginas = document.querySelectorAll('.pagina-card');
    if (!paginas.length || pageIndex < 0 || pageIndex >= paginas.length) return;
    const pagina = paginas[pageIndex];
    const conteudo = pagina.querySelector('.r141-root');
    if (!conteudo) return;

    // Limpa canvas e clona conteúdo
    viewer.canvas.innerHTML = '';
    viewer.clonePage = conteudo.cloneNode(true);
    viewer.originalPage = conteudo;
    viewer.canvas.appendChild(viewer.clonePage);
    viewer.overlay.hidden = false;

    // Sincronizar edições do clone para o original (por id, escopado na página)
    const origScope = viewer.originalPage; // escopo para evitar conflito de ids
    viewer.clonePage.querySelectorAll('input, textarea').forEach(el => {
        el.addEventListener('input', () => {
            const id = el.getAttribute('id');
            if (!id) return;
            const target = origScope.querySelector('#' + CSS.escape(id));
            if (target) target.value = el.value;
        });
        el.addEventListener('change', () => {
            const id = el.getAttribute('id');
            if (!id) return;
            const target = origScope.querySelector('#' + CSS.escape(id));
            if (!target) return;
            if (el.type === 'checkbox') {
                target.checked = el.checked;
            } else {
                target.value = el.value;
            }
        });
    });

    // Define escala para ajustar na largura disponível
    setTimeout(ajustarViewerParaLargura, 20);
}

function fecharViewer() {
    viewer.overlay.hidden = true;
    viewer.canvas.innerHTML = '';
    viewer.clonePage = null;
    viewer.originalPage = null;
}

function setViewerScale(s) {
    const label = document.getElementById('viewerScaleLabel');
    viewer.scale = Math.max(0.3, Math.min(3, s));
    if (viewer.canvas) {
        viewer.canvas.style.transform = `scale(${viewer.scale})`;
    }
    label.textContent = Math.round(viewer.scale * 100) + '%';
}

function ajustarViewerParaLargura() {
    if (!viewer.canvas) return;
    // Temporariamente sem escala para medir
    viewer.canvas.style.transform = 'none';
    const body = viewer.canvas.parentElement; // .viewer-body
    const a4 = viewer.canvas.querySelector('.a4');
    if (!a4) return;
    const bodyRect = body.getBoundingClientRect();
    const a4Rect = a4.getBoundingClientRect();
    if (a4Rect.width === 0) return;
    const padding = 32; // margens laterais
    const scale = (bodyRect.width - padding) / a4Rect.width;
    setViewerScale(scale);
}

</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_relatorio_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
