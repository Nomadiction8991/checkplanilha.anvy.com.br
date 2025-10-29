<?php
require_once __DIR__ . '/../../../CRUD/READ/relatorio-14-1.php';

// Carregar bloco do template A4 com inputs sequenciais
$templatePath = __DIR__ . '/../../../relatorios/14-1.html';
$a4Block = '';
if (file_exists($templatePath)) {
    $tpl = file_get_contents($templatePath);
    $start = strpos($tpl, '<!-- A4-START -->');
    $end   = strpos($tpl, '<!-- A4-END -->');
    if ($start !== false && $end !== false && $end > $start) {
        $a4Block = trim(substr($tpl, $start + strlen('<!-- A4-START -->'), $end - ($start + strlen('<!-- A4-START -->'))));
    }
}

$pageTitle = 'Relatório 14.1';
$backUrl = '../shared/menu.php?id=' . urlencode($id_planilha);
$headerActions = '<button id="btnPrint" class="btn-header-action" title="Imprimir" onclick="validarEImprimir()"><i class="bi bi-printer"></i></button>';

// CSS customizado
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
    overflow: hidden;
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
.a4 input.editado,
.a4 textarea.editado {
    color: #dc3545 !important;
}

.a4 label:has(input[type="checkbox"].marcado) {
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
    .a4 input.editado,
    .a4 textarea.editado,
    .a4 label:has(input[type="checkbox"].marcado) {
        color: #000 !important;
    }

    /* Fundo com opacidade total na impressão */
    .page-bg { opacity: 1 !important; }
}

/* Evitar cortes de conteúdo em campos e células */
.a4 input[type="text"], .a4 textarea { height: auto; line-height: 1.2; padding: 2px 4px; }
.a4 table td { overflow: visible; }
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
            </div>
            
            <div class="a4-viewport">
                <div class="a4-scaled">
                    <link rel="stylesheet" href="/relatorios/14-1.scoped.css">
                    <div class="r141-root">
                    <?php
                        // Repetir o bloco A4 do template (inputs sequenciais)
                        echo $a4Block ?: '<div class="a4"><p style="padding:10mm;color:#900">Template 14-1 não encontrado.</p></div>';
                    ?>
                    </div>
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
});

// Detectar edição manual em inputs e textareas
function inicializarDeteccaoEdicao() {
    document.querySelectorAll('.a4 input[type="text"], .a4 textarea').forEach(campo => {
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
    document.querySelectorAll('.a4 input[type="checkbox"]').forEach(checkbox => {
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
    let selector;
    switch(tipo) {
        case 'admin': selector = '[id^="administracao_"]'; break;
        case 'cidade': selector = '[id^="cidade_"]'; break;
        case 'setor': selector = '[id^="setor_"]'; break;
        case 'admin_acessor': selector = '[id^="admin_acessor_"]'; break;
        default: selector = '[id^="' + tipo + '_"]';
    }
    const inputs = document.querySelectorAll(selector);
    inputs.forEach(input => {
        if (!input.id.includes('geral')) {
            input.value = valor;
            if (valor !== '') {
                input.classList.add('editado');
            }
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

// Validar e imprimir
function validarEImprimir() {
    const totalPaginas = document.querySelectorAll('.pagina-card').length;
    
    for (let i = 0; i < totalPaginas; i++) {
        const checks = document.querySelectorAll(`.opcao-checkbox[data-page="${i}"]`);
        const marcados = Array.from(checks).filter(c => c.checked).length;
        
        if (marcados !== 1) {
            alert(`Selecione exatamente 1 opção na página ${i + 1} antes de imprimir.`);
            // Rolar até a página com erro
            document.querySelectorAll('.pagina-card')[i].scrollIntoView({ behavior: 'smooth', block: 'start' });
            return false;
        }
    }
    
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
        const a4 = view.querySelector('.a4');
        if (!scaled || !a4) return;

        // Reseta para medir tamanho real
        scaled.style.transform = 'none';
        scaled.style.width = '';
        // não ajusta altura aqui; aspect-ratio já define

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

        // Aplica escala e ajusta altura do viewport para evitar corte
        scaled.style.transform = `scale(${scale})`;
        scaled.style.transformOrigin = 'top center';
        // compensar largura para não cortar nas laterais
        scaled.style.width = `${(1/scale)*100}%`;
        // altura do viewport mantida pela aspect-ratio
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
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_relatorio_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
