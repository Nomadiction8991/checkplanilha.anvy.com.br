<?php
require_once __DIR__ . '/../../../CRUD/UPDATE/editar-planilha.php';

$pageTitle = "Editar Planilha";
$backUrl = '../../../index.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- Info Atual -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-info-circle me-2"></i>
            Informações Atuais
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">CNPJ</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['cnpj'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Comum</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['comum'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['endereco'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data Posição</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['data_posicao'] ?? ''); ?>" disabled>
                </div>
            </div>
            
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo ($planilha['ativo'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ativo">
                    Planilha Ativa
                </label>
            </div>
        </div>
    </div>

    <!-- Configurações -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            Configurações de Importação
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="linhas_pular" class="form-label">Linhas Iniciais a Pular</label>
                <input type="number" class="form-control" id="linhas_pular" name="linhas_pular" 
                       value="<?php echo $config['pulo_linhas'] ?? 25; ?>" min="0" required>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="localizacao_cnpj" class="form-label">CNPJ</label>
                    <input type="text" class="form-control" id="localizacao_cnpj" name="localizacao_cnpj" 
                           value="<?php echo htmlspecialchars($config['cnpj'] ?? 'U5'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_comum" class="form-label">Comum</label>
                    <input type="text" class="form-control" id="localizacao_comum" name="localizacao_comum" 
                           value="<?php echo htmlspecialchars($config['comum'] ?? 'D16'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_endereco" class="form-label">Endereço</label>
                    <input type="text" class="form-control" id="localizacao_endereco" name="localizacao_endereco" 
                           value="<?php echo htmlspecialchars($config['endereco'] ?? 'A4'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_data_posicao" class="form-label">Data Posição</label>
                    <input type="text" class="form-control" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                           value="<?php echo htmlspecialchars($config['data_posicao'] ?? 'D13'); ?>" required>
                </div>
            </div>

            <h6 class="mt-4 mb-3">Mapeamento de Colunas</h6>
            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label class="form-label">Assinatura do Responsável</label>
                    <div class="border p-2 mb-2" style="overflow:hidden;">
                        <canvas id="canvas_responsavel" width="800" height="160" style="touch-action: none; background:#fff; border:1px solid #ddd; width:100%; height:auto;"></canvas>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary btn-lg w-100 mt-2" onclick="openSignatureModal()">Fazer Assinatura</button>
                    </div>
                    <input type="hidden" name="assinatura_responsavel" id="assinatura_responsavel" value="<?php echo htmlspecialchars($planilha['assinatura_responsavel'] ?? ''); ?>">
                    <?php if (!empty($planilha['assinatura_responsavel'])): ?>
                        <div class="mt-2 small text-muted">Assinatura existente (pode redesenhar para substituir)</div>
                    <?php endif; ?>
                </div>
            </div>
        <div class="card-header">
            <i class="bi bi-arrow-repeat me-2"></i>
            Atualizar Dados
        </div>
        <div class="card-body">
            <label for="arquivo" class="form-label">Novo Arquivo CSV (opcional)</label>
            <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".csv">
            <div class="form-text">Selecione apenas se desejar substituir os dados atuais</div>
        </div>
    </div>

    <!-- Informações do Responsável (Administrador/Acessor) -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-lines-fill me-2"></i>
            Responsável (Administrador / Acessor)
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome_responsavel" class="form-label">Nome do Responsável <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_responsavel" name="nome_responsavel" 
                           value="<?php echo htmlspecialchars($planilha['nome_responsavel'] ?? ''); ?>" maxlength="255" required>
                </div>
                <div class="col-md-3">
                    <label for="administracao" class="form-label">Estado (Administração) <span class="text-danger">*</span></label>
                    <select id="administracao" name="administracao" class="form-select" required>
                        <option value="">Carregando estados...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                    <select id="cidade" name="cidade" class="form-select" required disabled>
                        <option value="">Selecione o estado primeiro</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label class="form-label">Assinatura do Responsável</label>
                    <div class="d-flex align-items-start gap-3">
                        <div style="flex:1; min-width:0;">
                            <div class="border p-2 mb-2" style="overflow:hidden;">
                                <canvas id="canvas_responsavel" width="400" height="120" style="touch-action: none; background:#fff; border:1px solid #ddd; width:100%; height:auto;"></canvas>
                            </div>
                        </div>
                        <div style="white-space:nowrap;">
                                <div style="margin-top:8px;">
                                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="openSignatureModal()">Fazer Assinatura</button>
                                </div>
                        </div>
                    </div>
                    <input type="hidden" name="assinatura_responsavel" id="assinatura_responsavel" value="<?php echo htmlspecialchars($planilha['assinatura_responsavel'] ?? ''); ?>">
                    <?php if (!empty($planilha['assinatura_responsavel'])): ?>
                        <div class="mt-2 small text-muted">Assinatura existente abaixo (pode redesenhar para substituir)</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Modal fullscreen para assinatura (reaproveitado) -->
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
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        Atualizar Planilha
    </button>
</form>

<?php
$contentHtml = ob_get_clean();

// Script para captura de assinaturas e carregar assinaturas existentes
// Reutiliza a mesma lógica do importar-planilha para modal, estados e cidades
// Pre-encode any server values used by the script to avoid parsing issues
$pre_administracao = json_encode($planilha['administracao'] ?? '');
$pre_cidade = json_encode($planilha['cidade'] ?? '');

$script = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Signature canvas (small preview)
    function initSignature(canvasId) {
        // Preview canvas should be non-editable: disable pointer events and return element
        const canvas = document.getElementById(canvasId);
        canvas.style.pointerEvents = 'none';
        return canvas;
    }

    function clearCanvas(id){ const c=document.getElementById(id); const ctx=c.getContext('2d'); ctx.clearRect(0,0,c.width,c.height); if(id==='canvas_responsavel') document.getElementById('assinatura_responsavel').value=''; }
    function downloadCanvas(id){ const c=document.getElementById(id); const a=document.createElement('a'); a.href=c.toDataURL('image/png'); a.download=id+'.png'; a.click(); }
    function drawImageOnCanvas(canvasId,dataUrl){ if(!dataUrl) return; const c=document.getElementById(canvasId); const ctx=c.getContext('2d'); const img=new Image(); img.onload=function(){ ctx.clearRect(0,0,c.width,c.height); const scale=Math.min(c.width/img.width, c.height/img.height); const w=img.width*scale, h=img.height*scale, x=(c.width-w)/2, y=(c.height-h)/2; ctx.drawImage(img,x,y,w,h); }; img.src=dataUrl; }

    // Initialize small canvas preview
    const cResp = initSignature('canvas_responsavel');
    const existingResp = document.getElementById('assinatura_responsavel').value;
    if(existingResp) drawImageOnCanvas('canvas_responsavel', existingResp);

    // Modal full-screen canvas
    let modalCanvas, modalCtx, modalDrawing=false, modalLastX=0, modalLastY=0;
    function resizeModalCanvas(){ if(!modalCanvas) return; const w=Math.max(800, window.innerWidth*0.92); const h=Math.max(360, window.innerHeight*0.72); modalCanvas.width=w; modalCanvas.height=h; }
    function initModalCanvas(){ modalCanvas = document.getElementById('modal_canvas'); modalCtx = modalCanvas.getContext('2d'); resizeModalCanvas(); /* do not attach manual listeners here */ modalCtx.lineWidth = 2; modalCtx.lineCap = 'round'; }
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
    function getModalCoords(e){ const rect = modalCanvas.getBoundingClientRect(); const clientX = (e.touches?e.touches[0].clientX:e.clientX); const clientY = (e.touches?e.touches[0].clientY:e.clientY); const scaleX = modalCanvas.width / rect.width; const scaleY = modalCanvas.height / rect.height; return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY }; }
    function modalStart(e){ modalDrawing=true; const p=getModalCoords(e); modalLastX=p.x; modalLastY=p.y; }
    function modalMove(e){ if(!modalDrawing) return; e.preventDefault(); const p=getModalCoords(e); modalCtx.beginPath(); modalCtx.moveTo(modalLastX, modalLastY); modalCtx.lineTo(p.x, p.y); modalCtx.stroke(); modalLastX=p.x; modalLastY=p.y; }
    function modalEnd(){ modalDrawing=false; }

    function setModalLandscape() {
        if (!modalCanvas) initModalCanvas();
        const vw = window.innerWidth, vh = window.innerHeight;
        let width = Math.max(800, Math.max(vw, vh) * 0.92);
        let height = Math.max(360, Math.min(vw, vh) * 0.6);
        if (width <= height) { width = height + 200; }
        modalCanvas.width = Math.floor(width);
        modalCanvas.height = Math.floor(height);
        modalCtx = modalCanvas.getContext('2d');
        modalCtx.lineWidth = 2; modalCtx.lineCap = 'round';
        modalCtx.fillStyle = '#ffffff';
        modalCtx.fillRect(0,0,modalCanvas.width, modalCanvas.height);
        modalCtx.strokeStyle = '#000000';
    }

    function setModalPortrait() {
        if (!modalCanvas) initModalCanvas();
        const vw = window.innerWidth, vh = window.innerHeight;
        let width = Math.max(360, Math.min(vw, vh) * 0.6);
        let height = Math.max(800, Math.max(vw, vh) * 0.92);
        if (width >= height) { height = width + 200; }
        modalCanvas.width = Math.floor(width);
        modalCanvas.height = Math.floor(height);
        modalCtx = modalCanvas.getContext('2d');
        modalCtx.lineWidth = 2; modalCtx.lineCap = 'round';
        modalCtx.fillStyle = '#ffffff';
        modalCtx.fillRect(0,0,modalCanvas.width, modalCanvas.height);
        modalCtx.strokeStyle = '#000000';
    }

    // SignaturePad integration for edit view
    let signaturePad = null;

    function startSignaturePad(keepExisting=false){
        if (!modalCanvas) initModalCanvas();
        if (!keepExisting) { try { modalCtx.clearRect(0,0,modalCanvas.width, modalCanvas.height); } catch(e){} }
        if (typeof SignaturePad !== 'undefined'){
            disableManualDrawing();
            signaturePad = new SignaturePad(modalCanvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'black' });
            if (!keepExisting) signaturePad.clear();
        } else { signaturePad = null; enableManualDrawing(); }
    }

    async function enterFullscreenAndLock(){
        const modalEl = document.getElementById('signatureModal');
        try{
            if (modalEl.requestFullscreen) await modalEl.requestFullscreen();
            if (screen && screen.orientation && screen.orientation.lock) {
                try{ await screen.orientation.lock('landscape'); } catch(e){}
            }
        }catch(err){ console.warn('fullscreen/orientation failed', err); }
    }

    window.openSignatureModal = function(){
        document.getElementById('signatureModal').style.display='block';
        setModalLandscape();
        const existing = document.getElementById('assinatura_responsavel').value;
        if (typeof SignaturePad === 'undefined'){
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js';
            s.onload = function(){
                startSignaturePad(!!existing);
                if (existing) { const img=new Image(); img.onload=function(){ try{ modalCtx.drawImage(img, 0, 0, modalCanvas.width, modalCanvas.height); }catch(e){} }; img.src=existing; }
                enterFullscreenAndLock();
            };
            s.onerror = function(){ startSignaturePad(!!existing); enterFullscreenAndLock(); };
            document.head.appendChild(s);
        } else { startSignaturePad(!!existing); if (existing) { const img=new Image(); img.onload=function(){ try{ modalCtx.drawImage(img, 0, 0, modalCanvas.width, modalCanvas.height); }catch(e){} }; img.src=existing; } enterFullscreenAndLock(); }
    };

    window.closeSignatureModal = async function(){
        document.getElementById('signatureModal').style.display='none';
        try{ if (document.fullscreenElement && document.exitFullscreen) await document.exitFullscreen(); if (screen && screen.orientation && screen.orientation.unlock) { try{ screen.orientation.unlock(); } catch(e){} } }catch(err){ console.warn('exit fullscreen failed', err); }
        if (signaturePad) { try{ signaturePad.off && signaturePad.off(); } catch(e){} signaturePad = null; }
    };

    window.applyModalSignature = function(){
        let data = null;
        if (signaturePad) { if (signaturePad.isEmpty()) data = null; else data = signaturePad.toDataURL('image/png'); }
        else { data = modalCanvas.toDataURL('image/png'); }
        const preview = document.getElementById('canvas_responsavel');
        const pCtx = preview.getContext('2d');
        if (data){ const img=new Image(); img.onload=function(){ pCtx.clearRect(0,0,preview.width,preview.height); const scale=Math.min(preview.width/img.width, preview.height/img.height); const w=img.width*scale, h=img.height*scale, x=(preview.width-w)/2, y=(preview.height-h)/2; pCtx.drawImage(img,x,y,w,h); document.getElementById('assinatura_responsavel').value=data; closeSignatureModal(); }; img.src=data; }
        else { pCtx.clearRect(0,0,preview.width,preview.height); document.getElementById('assinatura_responsavel').value=''; closeSignatureModal(); }
    };

    // Clear modal signature (button inside modal)
    window.clearModalSignature = function(){ if (!modalCanvas) return; if (signaturePad) { signaturePad.clear(); } try { modalCtx.clearRect(0,0,modalCanvas.width, modalCanvas.height); } catch(e){} };

    // Estados / Cidades (popula cidades de MT e armazena valores no formato "MT - Cidade")
    const preAdministracao = $pre_administracao;
    const preCidade = $pre_cidade;

    async function loadEstados(){
        const sel = document.getElementById('administracao');
        sel.innerHTML = '<option value="">Carregando estados...</option>';
        try{
            const res = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados');
            const estados = await res.json();
            // encontrar MT
            const mt = estados.find(s => s.sigla === 'MT');
            if(!mt){ sel.innerHTML='<option value="">MT não encontrado</option>'; return; }
            sel.innerHTML = '<option value="">Carregando cidades de MT...</option>';
            await loadCidades(mt.id);
        }catch(err){ sel.innerHTML='<option value="">Erro ao carregar estados</option>'; console.error(err); }
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
            cidades.forEach(ct=>{
                const val = sigla + ' - ' + ct.nome;
                const opt = document.createElement('option'); opt.value = val; opt.text = val; cidadeSel.appendChild(opt);
                const opt2 = document.createElement('option'); opt2.value = val; opt2.text = val; adminSel.appendChild(opt2);
            });
            cidadeSel.disabled = false; adminSel.disabled = false;
            if(preCidade){ for(const o of cidadeSel.options) if(o.value===preCidade){ o.selected=true; break; } }
            if(preAdministracao){ for(const o of adminSel.options) if(o.value===preAdministracao){ o.selected=true; break; } }
        }catch(err){ cidadeSel.innerHTML='<option value="">Erro ao carregar cidades</option>'; console.error(err); }
    }

    // Os selects `administracao` e `cidade` foram preenchidos com a mesma lista de
    // cidades de MT, mas são independentes — não há escuta de sincronização entre eles.

    // Antes de submeter, garantir que administracao e cidade estejam no formato "MT - Cidade"
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){ const nome = document.getElementById('nome_responsavel').value.trim(); const estado = document.getElementById('administracao').value; const cidadeVal = document.getElementById('cidade').value; if(!nome || !estado || !cidadeVal){ e.preventDefault(); alert('Por favor preencha Nome do Responsável, Estado e Cidade (campos obrigatórios).'); return false; }
    // Os selects são independentes — não sobrescrevemos automaticamente o campo 'administracao'.
        // assinatura
        function isCanvasBlank(c){ const blank=document.createElement('canvas'); blank.width=c.width; blank.height=c.height; return c.toDataURL()===blank.toDataURL(); }
        if(!isCanvasBlank(document.getElementById('canvas_responsavel'))){ document.getElementById('assinatura_responsavel').value = document.getElementById('canvas_responsavel').toDataURL('image/png'); }
    });

    // inicialização: carregar estados, depois cidades e aplicar seleção pré-existente se houver
    (async function(){ await loadEstados(); })();
});
</script>
HTML;

$contentHtml = $contentHtml . $script;
$tempFile = __DIR__ . '/../../../temp_editar_planilha_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
