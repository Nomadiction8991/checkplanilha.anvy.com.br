<?php
require_once __DIR__ . '/../../../CRUD/CREATE/importar-planilha.php';

// Configurações da página
$pageTitle = "Importar Planilha";
$backUrl = '../../../index.php';

// Iniciar buffer
ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- Arquivo CSV -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>
            Arquivo
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="arquivo" class="form-label">Arquivo CSV *</label>
                <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".csv" required>
                <div class="form-text">Selecione o arquivo CSV para importação</div>
            </div>
        </div>
    </div>

    <!-- Configurações Básicas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            Configurações Básicas
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="linhas_pular" class="form-label">Linhas iniciais a pular</label>
                <input type="number" class="form-control" id="linhas_pular" name="linhas_pular" 
                       value="<?php echo $_POST['linhas_pular'] ?? 25; ?>" min="0" required>
                <div class="form-text">Número de linhas do cabeçalho que devem ser ignoradas</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="localizacao_comum" class="form-label">Célula Comum</label>
                    <input type="text" class="form-control" id="localizacao_comum" name="localizacao_comum" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_comum'] ?? 'D16'); ?>" 
                           required placeholder="Ex: D16">
                    <div class="form-text">Ex: D16</div>
                </div>

                <div class="col-md-6">
                    <label for="localizacao_data_posicao" class="form-label">Célula Data Posição</label>
                    <input type="text" class="form-control" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_data_posicao'] ?? 'D13'); ?>" 
                           required placeholder="Ex: D13">
                    <div class="form-text">Ex: D13</div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="localizacao_endereco" class="form-label">Célula Endereço</label>
                    <input type="text" class="form-control" id="localizacao_endereco" name="localizacao_endereco" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_endereco'] ?? 'A4'); ?>" 
                           required placeholder="Ex: A4">
                    <div class="form-text">Ex: A4</div>
                </div>

                <div class="col-md-6">
                    <label for="localizacao_cnpj" class="form-label">Célula CNPJ</label>
                    <input type="text" class="form-control" id="localizacao_cnpj" name="localizacao_cnpj" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_cnpj'] ?? 'U5'); ?>" 
                           required placeholder="Ex: U5">
                    <div class="form-text">Ex: U5</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapeamento de Colunas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-columns-gap me-2"></i>
            Mapeamento de Colunas
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Defina a letra da coluna para cada campo</p>
            
            <div class="row g-3">
                <div class="col-4">
                    <label for="codigo" class="form-label">Código</label>
                    <input type="text" class="form-control text-center fw-bold" name="codigo" 
                           value="<?php echo $_POST['codigo'] ?? 'A'; ?>" maxlength="2" required>
                </div>

                <div class="col-4">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control text-center fw-bold" name="nome" 
                           value="<?php echo $_POST['nome'] ?? 'D'; ?>" maxlength="2" required>
                </div>

                <div class="col-4">
                    <label for="dependencia" class="form-label">Dependência</label>
                    <input type="text" class="form-control text-center fw-bold" name="dependencia" 
                           value="<?php echo $_POST['dependencia'] ?? 'P'; ?>" maxlength="2" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Outros Dados -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-lines-fill me-2"></i>
            Outros Dados
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="administracao" class="form-label">Administração <span class="text-danger">*</span></label>
                    <select id="administracao" name="administracao" class="form-select" required>
                        <option value="">Carregando...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                    <select id="cidade" name="cidade" class="form-select" required disabled>
                        <option value="">Carregando...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="nome_responsavel" class="form-label">Nome do Administrador/Acessor <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_responsavel" name="nome_responsavel" 
                           value="<?php echo htmlspecialchars($_POST['nome_responsavel'] ?? ''); ?>" maxlength="255" required>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label class="form-label">Assinatura do Administrador/Acessor</label>
                    <div class="border p-2 mb-2" style="overflow:hidden;">
                        <canvas id="canvas_responsavel" width="800" height="160" style="touch-action: none; background:#fff; border:1px solid #ddd; width:100%; height:auto;"></canvas>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary btn-lg w-100 mt-2" onclick="openSignatureModal()">Fazer Assinatura</button>
                    </div>
                    <input type="hidden" name="assinatura_responsavel" id="assinatura_responsavel">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal fullscreen para assinatura -->
    <div id="signatureModal" class="modal" tabindex="-1" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1050;">
        <div style="position:relative; width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
            <div style="background:#fff; width:100%; height:100%; padding:12px; box-sizing:border-box; position:relative;">
                <div class="d-flex justify-content-between mb-2">
                        <div>
                            <button type="button" class="btn btn-warning btn-sm" onclick="clearModalSignature()">Limpar</button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-success btn-sm" onclick="applyModalSignature()">Salvar</button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="closeSignatureModal()">Fechar</button>
                        </div>
                </div>
                <div style="width:100%; height:calc(100% - 48px); display:flex; align-items:center; justify-content:center;">
                    <canvas id="modal_canvas" style="background:#fff; border:1px solid #ddd; max-width:100%; max-height:100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-upload me-2"></i>
        Importar Planilha
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
// Injetar script para captura de assinaturas (desenho em canvas -> hidden input)
$pre_administracao = json_encode($_POST['administracao'] ?? '');
$pre_cidade = json_encode($_POST['cidade'] ?? '');

$script = <<<HTML
<script>
function initSignature(canvasId) {
    // Preview canvas should be non-editable. We only return the element and disable pointer events.
    const canvas = document.getElementById(canvasId);
    canvas.style.pointerEvents = 'none';
    return canvas;
}

function clearCanvas(id) {
    const c = document.getElementById(id);
    const ctx = c.getContext('2d');
    ctx.clearRect(0,0,c.width,c.height);
    // limpar campo hidden relacionado
    if (id === 'canvas_responsavel') document.getElementById('assinatura_responsavel').value = '';
}

function downloadCanvas(id) {
    const c = document.getElementById(id);
    const a = document.createElement('a');
    a.href = c.toDataURL('image/png');
    a.download = id + '.png';
    a.click();
}

// Antes do submit, inicializações, modal e IBGE
document.addEventListener('DOMContentLoaded', function(){
    // Inicializa canvas de preview
    const cResp = initSignature('canvas_responsavel');

    // Carregar assinatura existente, se houver (apenas no editar)
    const existingResp = document.getElementById('assinatura_responsavel').value;
    if (existingResp) drawImageOnCanvas('canvas_responsavel', existingResp);

    // Funções para modal fullscreen
    let modalCanvas, modalCtx, modalDrawing=false, modalLastX=0, modalLastY=0;
    function initModalCanvas() {
        modalCanvas = document.getElementById('modal_canvas');
        modalCtx = modalCanvas.getContext('2d');
        // Do not attach manual pointer listeners here. We will enable them only
        // when SignaturePad is not available to avoid duplicate drawing handlers.
        // Actual pixel buffer is set in resizeModalCanvas() which takes devicePixelRatio into account.
    }
    // Manual drawing handlers (fallback when SignaturePad is not present)
    let manualListenersEnabled = false;
    function enableManualDrawing(){
        if(!modalCanvas || manualListenersEnabled) return;
        modalCanvas.addEventListener('mousedown', modalStart);
        modalCanvas.addEventListener('touchstart', modalStart, { passive: false });
        modalCanvas.addEventListener('mousemove', modalMove);
        modalCanvas.addEventListener('touchmove', modalMove, { passive: false });
        modalCanvas.addEventListener('mouseup', modalEnd);
        modalCanvas.addEventListener('mouseout', modalEnd);
        modalCanvas.addEventListener('touchend', modalEnd);
        manualListenersEnabled = true;
    }
    function disableManualDrawing(){
        if(!modalCanvas || !manualListenersEnabled) return;
        modalCanvas.removeEventListener('mousedown', modalStart);
        modalCanvas.removeEventListener('touchstart', modalStart);
        modalCanvas.removeEventListener('mousemove', modalMove);
        modalCanvas.removeEventListener('touchmove', modalMove);
        modalCanvas.removeEventListener('mouseup', modalEnd);
        modalCanvas.removeEventListener('mouseout', modalEnd);
        modalCanvas.removeEventListener('touchend', modalEnd);
        manualListenersEnabled = false;
    }
    function resizeModalCanvas() {
        if (!modalCanvas) return;
        // compute current display size (client rect) and scale internal buffer by devicePixelRatio
        const rect = modalCanvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        const cssW = Math.max(300, Math.floor(rect.width));
        const cssH = Math.max(150, Math.floor(rect.height));
        modalCanvas.style.width = cssW + 'px';
        modalCanvas.style.height = cssH + 'px';
        modalCanvas.width = Math.floor(cssW * dpr);
        modalCanvas.height = Math.floor(cssH * dpr);
        modalCtx = modalCanvas.getContext('2d');
        // reset any transform and scale so drawings map to CSS pixels
        try { modalCtx.setTransform(1,0,0,1,0,0); } catch(e){}
        modalCtx.scale(dpr, dpr);
        modalCtx.lineWidth = 2;
        modalCtx.lineCap = 'round';
        // clear with white background
        try{ modalCtx.fillStyle = '#ffffff'; modalCtx.fillRect(0,0,cssW,cssH); }catch(e){}
    }
    function getModalCoords(e) {
        const rect = modalCanvas.getBoundingClientRect();
        const clientX = (e.touches ? e.touches[0].clientX : e.clientX);
        const clientY = (e.touches ? e.touches[0].clientY : e.clientY);
        // map client coords to CSS pixels, SignaturePad / canvas drawing uses CSS space after scaling
        return { x: clientX - rect.left, y: clientY - rect.top };
    }

    function modalStart(e){
        modalDrawing = true;
        const p = getModalCoords(e);
        modalLastX = p.x; modalLastY = p.y;
    }
    function modalMove(e){
        if (!modalDrawing) return;
        e.preventDefault();
        const p = getModalCoords(e);
        modalCtx.beginPath();
        modalCtx.moveTo(modalLastX, modalLastY);
        modalCtx.lineTo(p.x, p.y);
        modalCtx.stroke();
        modalLastX = p.x; modalLastY = p.y;
    }
    function modalEnd(){ modalDrawing=false; }

    // Abrir modal: abrir em branco já girado (pronto para assinar)
    function setModalLandscape() {
        if (!modalCanvas) initModalCanvas();
        const vw = window.innerWidth, vh = window.innerHeight;
        let cssW = Math.max(800, Math.max(vw, vh) * 0.92);
        let cssH = Math.max(360, Math.min(vw, vh) * 0.6);
        if (cssW <= cssH) cssW = cssH + 200;
        modalCanvas.style.width = Math.floor(cssW) + 'px';
        modalCanvas.style.height = Math.floor(cssH) + 'px';
        resizeModalCanvas();
        modalCtx.strokeStyle = '#000000';
    }

    function setModalPortrait() {
        if (!modalCanvas) initModalCanvas();
        const vw = window.innerWidth, vh = window.innerHeight;
        let cssW = Math.max(360, Math.min(vw, vh) * 0.6);
        let cssH = Math.max(800, Math.max(vw, vh) * 0.92);
        if (cssW >= cssH) cssH = cssW + 200;
        modalCanvas.style.width = Math.floor(cssW) + 'px';
        modalCanvas.style.height = Math.floor(cssH) + 'px';
        resizeModalCanvas();
        modalCtx.strokeStyle = '#000000';
    }

    // SignaturePad integration
    let signaturePad = null;

    function startSignaturePad(keepExisting=false){
        if (!modalCanvas) initModalCanvas();
        // clear canvas only when we're starting fresh
        if (!keepExisting) {
            try { modalCtx.clearRect(0,0,modalCanvas.width, modalCanvas.height); } catch(e){}
        }
        if (typeof SignaturePad !== 'undefined') {
            // disable manual handlers to avoid double-draw
            disableManualDrawing();
            signaturePad = new SignaturePad(modalCanvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'black' });
            // do not clear here if caller wants to keep existing drawing
            if (!keepExisting) signaturePad.clear();
        } else {
            signaturePad = null;
            // enable manual fallback listeners
            enableManualDrawing();
        }
    }

    async function enterFullscreenAndLock(){
        const modalEl = document.getElementById('signatureModal');
        try{
            if (modalEl.requestFullscreen) await modalEl.requestFullscreen();
            if (screen && screen.orientation && screen.orientation.lock) {
                try{ await screen.orientation.lock('landscape'); } catch(e){ /* ignore */ }
            }
        }catch(err){ console.warn('fullscreen/orientation failed', err); }
    }

    // Abrir modal: em branco e em landscape; carregar SignaturePad via CDN se necessário
    window.openSignatureModal = function(){
        document.getElementById('signatureModal').style.display = 'block';
        setModalLandscape();
        // carregar SignaturePad se necessário
        const existing = document.getElementById('assinatura_responsavel').value;
        if (typeof SignaturePad === 'undefined'){
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js';
            s.onload = function(){
                // start and keep existing image if any
                startSignaturePad(!!existing);
                // if there is existing signature, draw it into modal so user can edit/overwrite
                    if (existing) {
                    const img = new Image();
                    img.onload = function(){
                        try { 
                            const dpr = window.devicePixelRatio || 1;
                            // draw using CSS pixel dimensions (internal buffer is scaled)
                            modalCtx.drawImage(img, 0, 0, modalCanvas.width / dpr, modalCanvas.height / dpr);
                        } catch(e){}
                    };
                    img.src = existing;
                }
                enterFullscreenAndLock();
            };
            s.onerror = function(){ startSignaturePad(!!existing); enterFullscreenAndLock(); };
            document.head.appendChild(s);
        } else {
            startSignaturePad(!!existing);
            if (existing) {
                const img = new Image();
                img.onload = function(){ try { const dpr = window.devicePixelRatio || 1; modalCtx.drawImage(img, 0, 0, modalCanvas.width / dpr, modalCanvas.height / dpr); } catch(e){} };
                img.src = existing;
            }
            enterFullscreenAndLock();
        }
    };

    // fechar modal: sair do fullscreen e liberar lock se aplicável
    window.closeSignatureModal = async function(){
        document.getElementById('signatureModal').style.display = 'none';
        try{
            if (document.fullscreenElement && document.exitFullscreen) await document.exitFullscreen();
            if (screen && screen.orientation && screen.orientation.unlock) {
                try{ screen.orientation.unlock(); } catch(e){}
            }
        }catch(err){ console.warn('exit fullscreen/orientation unlock failed', err); }
        // optionally destroy signaturePad
        if (signaturePad) { try{ signaturePad.off && signaturePad.off(); } catch(e){} signaturePad = null; }
    };

    window.applyModalSignature = function(){
        let data = null;
        if (signaturePad) {
            if (signaturePad.isEmpty()) data = null; else data = signaturePad.toDataURL('image/png');
        } else {
            data = modalCanvas.toDataURL('image/png');
        }
        const preview = document.getElementById('canvas_responsavel');
        const pCtx = preview.getContext('2d');
        if (data) {
            const img = new Image();
            img.onload = function(){
                pCtx.clearRect(0,0,preview.width, preview.height);
                const scale = Math.min(preview.width / img.width, preview.height / img.height);
                const w = img.width * scale; const h = img.height * scale;
                const x = (preview.width - w)/2; const y = (preview.height - h)/2;
                pCtx.drawImage(img, x, y, w, h);
                document.getElementById('assinatura_responsavel').value = data;
                closeSignatureModal();
            };
            img.src = data;
        } else {
            pCtx.clearRect(0,0,preview.width, preview.height);
            document.getElementById('assinatura_responsavel').value = '';
            closeSignatureModal();
        }
    };
    // Clear modal signature (button inside modal)
    window.clearModalSignature = function(){
        if (!modalCanvas) return;
        if (signaturePad) {
            signaturePad.clear();
        }
        try { modalCtx.clearRect(0,0,modalCanvas.width, modalCanvas.height); } catch(e){}
    };

    // Antes do submit, serializar o preview canvas para o hidden input e validar campos obrigatórios
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){
        const nome = document.getElementById('nome_responsavel').value.trim();
        const estado = document.getElementById('administracao').value;
        const cidade = document.getElementById('cidade').value;
        if (!nome || !estado || !cidade) {
            e.preventDefault();
            alert('Por favor preencha Administração, Cidade e Nome do Administrador/Acessor (campos obrigatórios).');
            return false;
        }
    // Os selects são independentes — não sobrescrevemos automaticamente o campo 'administracao'.
        const dataResp = document.getElementById('canvas_responsavel').toDataURL('image/png');
        function isCanvasBlank(c) {
            const blank = document.createElement('canvas');
            blank.width = c.width; blank.height = c.height;
            return c.toDataURL() === blank.toDataURL();
        }
        if (!isCanvasBlank(document.getElementById('canvas_responsavel'))) {
            document.getElementById('assinatura_responsavel').value = dataResp;
        }
    });

    // Popula selects de estados e cidades via IBGE
    async function loadEstados(){
        const sel = document.getElementById('administracao');
        sel.innerHTML = '<option value="">Carregando estados...</option>';
        try{
            const res = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados');
            const estados = await res.json();
            // encontrar MT apenas
            const mt = estados.find(s => s.sigla === 'MT');
            if(!mt){ sel.innerHTML = '<option value="">MT não encontrado</option>'; return; }
            // Em vez de popular administracao com o estado, vamos carregar as cidades de MT
            // e popular tanto `administracao` quanto `cidade` com a lista no formato "MT - Cidade".
            sel.innerHTML = '<option value="">Carregando cidades de MT...</option>';
            await loadCidades(mt.id);
        } catch(err){
            sel.innerHTML = '<option value="">Erro ao carregar estados</option>';
            console.error(err);
        }
    }
    async function loadCidades(estadoId){
        const cidadeSel = document.getElementById('cidade');
        const adminSel = document.getElementById('administracao');
        cidadeSel.innerHTML = '<option value="">Carregando cidades...</option>';
        cidadeSel.disabled = true;
        adminSel.innerHTML = '<option value="">Carregando cidades...</option>';
        adminSel.disabled = true;
        try{
            const res = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/'+estadoId+'/municipios');
            const cidades = await res.json();
            cidades.sort((a,b)=>a.nome.localeCompare(b.nome));
            cidadeSel.innerHTML = '<option value="">Selecione a cidade</option>';
            adminSel.innerHTML = '<option value="">Selecione a cidade</option>';
            const sigla = 'MT';
            cidades.forEach(ct => {
                const val = sigla + ' - ' + ct.nome;
                const opt = document.createElement('option'); opt.value = val; opt.text = val; cidadeSel.appendChild(opt);
                const opt2 = document.createElement('option'); opt2.value = val; opt2.text = val; adminSel.appendChild(opt2);
            });
            cidadeSel.disabled = false;
            adminSel.disabled = false;
            const pre = {$pre_cidade};
            const preA = {$pre_administracao};
            if (pre) { for(const o of cidadeSel.options) if (o.value===pre) { o.selected=true; break; } }
            if (preA) { for(const o of adminSel.options) if (o.value===preA) { o.selected=true; break; } }
        } catch(err){
            cidadeSel.innerHTML = '<option value="">Erro ao carregar cidades</option>';
            console.error(err);
        }
    }
    // Os selects `administracao` e `cidade` foram preenchidos com a mesma lista de
    // cidades de MT, mas são independentes — não há escuta de sincronização entre eles.

    // inicialização com pré-seleção (se necessário)
    (async function(){
        await loadEstados();
    })();
});

// helper to draw dataURL into canvas (used earlier)
function drawImageOnCanvas(canvasId, dataUrl) {
    if (!dataUrl) return;
    const c = document.getElementById(canvasId);
    const ctx = c.getContext('2d');
    const img = new Image();
    img.onload = function() {
        ctx.clearRect(0,0,c.width,c.height);
        const scale = Math.min(c.width / img.width, c.height / img.height);
        const w = img.width * scale;
        const h = img.height * scale;
        const x = (c.width - w) / 2;
        const y = (c.height - h) / 2;
        ctx.drawImage(img, x, y, w, h);
    };
    img.src = dataUrl;
}
</script>
HTML;

$contentHtml = $contentHtml . $script;
$tempFile = __DIR__ . '/../../../temp_importar_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
