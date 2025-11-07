<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
include __DIR__ . '/../../../CRUD/UPDATE/usuario.php';

$pageTitle = 'Editar Usuário';
$backUrl = './read-usuario.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- jQuery e InputMask -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<!-- SignaturePad -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<style>
.signature-preview-canvas {
    pointer-events: none;
}
</style>

<?php if (isset($usuario)): ?>
<form method="POST" id="formUsuario">
    <!-- Campo oculto: tipo de usuário -->
    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($usuario['tipo'] ?? 'Administrador/Acessor'); ?>">
    
    <!-- Card 1: Dados Básicos -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-plus me-2"></i>
            Dados Básicos
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nome" name="nome" 
                       value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" 
                           placeholder="000.000.000-00" required>
                </div>
                <div class="col-12">
                    <label for="rg" class="form-label">RG <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="rg" name="rg" 
                           value="<?php echo htmlspecialchars($usuario['rg'] ?? ''); ?>" 
                           placeholder="00000000-0" required <?php echo !empty($usuario['rg_igual_cpf']) ? 'disabled' : ''; ?>>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" id="rg_igual_cpf" name="rg_igual_cpf" value="1" <?php echo !empty($usuario['rg_igual_cpf']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="rg_igual_cpf">RG igual ao CPF</label>
                    </div>
                </div>
                <div class="col-12">
                    <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="telefone" name="telefone" 
                           value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" 
                           placeholder="(00) 00000-0000" required>
                </div>
            </div>

            <div class="mb-3 mt-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Deixe os campos de senha em branco para manter a senha atual
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label for="senha" class="form-label">Nova Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" minlength="6">
                    <small class="text-muted">Mínimo de 6 caracteres</small>
                </div>

                <div class="col-12">
                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" minlength="6">
                </div>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo $usuario['ativo'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ativo">
                    Usuário Ativo
                </label>
            </div>
        </div>
    </div>

    <!-- Card 2: Endereço -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-geo-alt me-2"></i>
            Endereço
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="cep" class="form-label">CEP</label>
                    <input type="text" class="form-control" id="cep" name="endereco_cep" 
                           value="<?php echo htmlspecialchars($usuario['endereco_cep'] ?? ''); ?>" 
                           placeholder="00000-000">
                    <small class="text-muted">Preencha para buscar automaticamente</small>
                </div>
                <div class="col-12">
                    <label for="logradouro" class="form-label">Logradouro</label>
                    <input type="text" class="form-control" id="logradouro" name="endereco_logradouro" 
                           value="<?php echo htmlspecialchars($usuario['endereco_logradouro'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label for="numero" class="form-label">Número</label>
                    <input type="text" class="form-control" id="numero" name="endereco_numero" 
                           value="<?php echo htmlspecialchars($usuario['endereco_numero'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="endereco_complemento" 
                           value="<?php echo htmlspecialchars($usuario['endereco_complemento'] ?? ''); ?>" 
                           placeholder="Apto, bloco, etc">
                </div>
                <div class="col-12">
                    <label for="bairro" class="form-label">Bairro</label>
                    <input type="text" class="form-control" id="bairro" name="endereco_bairro" 
                           value="<?php echo htmlspecialchars($usuario['endereco_bairro'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="endereco_cidade" 
                           value="<?php echo htmlspecialchars($usuario['endereco_cidade'] ?? ''); ?>">
                </div>
                <div class="col-12">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="endereco_estado">
                        <option value="">Selecione</option>
                        <?php
                        $estados = ['AC'=>'Acre','AL'=>'Alagoas','AP'=>'Amapá','AM'=>'Amazonas','BA'=>'Bahia','CE'=>'Ceará','DF'=>'Distrito Federal','ES'=>'Espírito Santo','GO'=>'Goiás','MA'=>'Maranhão','MT'=>'Mato Grosso','MS'=>'Mato Grosso do Sul','MG'=>'Minas Gerais','PA'=>'Pará','PB'=>'Paraíba','PR'=>'Paraná','PE'=>'Pernambuco','PI'=>'Piauí','RJ'=>'Rio de Janeiro','RN'=>'Rio Grande do Norte','RS'=>'Rio Grande do Sul','RO'=>'Rondônia','RR'=>'Roraima','SC'=>'Santa Catarina','SP'=>'São Paulo','SE'=>'Sergipe','TO'=>'Tocantins'];
                        foreach($estados as $sigla => $nome):
                            $selected = ($usuario['endereco_estado'] ?? '') === $sigla ? 'selected' : '';
                        ?>
                        <option value="<?php echo $sigla; ?>" <?php echo $selected; ?>><?php echo $nome; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2.1: Estado civil -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-hearts me-2"></i>
            Estado civil
        </div>
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="casado" name="casado" value="1" <?php echo !empty($usuario['casado']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="casado">Sou casado(a)</label>
            </div>
        </div>
    </div>

    <!-- Card 3: Dados do Cônjuge (condicional) -->
    <div id="cardConjuge" class="card mb-3" style="display: <?php echo !empty($usuario['casado']) ? '' : 'none'; ?>;">
        <div class="card-header">
            <i class="bi bi-people-fill me-2"></i>
            Dados do Cônjuge
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nome_conjuge" class="form-label">Nome Completo do Cônjuge</label>
                <input type="text" class="form-control" id="nome_conjuge" name="nome_conjuge" value="<?php echo htmlspecialchars($usuario['nome_conjuge'] ?? ''); ?>">
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="cpf_conjuge" class="form-label">CPF do Cônjuge</label>
                    <input type="text" class="form-control" id="cpf_conjuge" name="cpf_conjuge" value="<?php echo htmlspecialchars($usuario['cpf_conjuge'] ?? ''); ?>" placeholder="000.000.000-00">
                </div>
                <div class="col-12">
                    <label for="rg_conjuge" class="form-label">RG do Cônjuge</label>
                    <input type="text" class="form-control" id="rg_conjuge" name="rg_conjuge" value="<?php echo htmlspecialchars($usuario['rg_conjuge'] ?? ''); ?>" placeholder="00000000-0">
                </div>
                <div class="col-12">
                    <label for="telefone_conjuge" class="form-label">Telefone do Cônjuge</label>
                    <input type="text" class="form-control" id="telefone_conjuge" name="telefone_conjuge" value="<?php echo htmlspecialchars($usuario['telefone_conjuge'] ?? ''); ?>" placeholder="(00) 00000-0000">
                </div>
            </div>

            <hr>
            <div class="mb-2 fw-semibold">Assinatura Digital do Cônjuge</div>
            <div class="signature-preview-container mb-3">
                <canvas id="canvas_conjuge" width="800" height="160" class="signature-preview-canvas" style="border:1px solid #dee2e6; border-radius:0.375rem; width:100%; height:auto; background:#f8f9fa;"></canvas>
            </div>
            <button type="button" class="btn btn-primary w-100" onclick="abrirModalAssinatura('conjuge')">
                <i class="bi bi-pen me-2"></i>
                Fazer Assinatura do Cônjuge
            </button>
            <input type="hidden" id="assinatura_conjuge" name="assinatura_conjuge" value="<?php echo htmlspecialchars($usuario['assinatura_conjuge'] ?? ''); ?>">
        </div>
    </div>

    <!-- Card 4: Assinatura Digital -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-pen me-2"></i>
            Assinatura Digital
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Clique no botão abaixo para atualizar sua assinatura digital.
            </p>
            
            <!-- Container de Preview da Assinatura -->
            <div class="signature-preview-container mb-3">
                <canvas id="canvas_usuario" width="800" height="160" class="signature-preview-canvas" style="border:1px solid #dee2e6; border-radius:0.375rem; width:100%; height:auto; background:#f8f9fa;"></canvas>
            </div>
            
            <!-- Botão para abrir modal -->
            <button type="button" class="btn btn-primary w-100" onclick="abrirModalAssinatura('usuario')">
                <i class="bi bi-pen me-2"></i>
                Fazer Assinatura
            </button>
            
            <!-- Campo hidden para armazenar assinatura em base64 -->
            <input type="hidden" id="assinatura_usuario" name="assinatura" value="<?php echo htmlspecialchars($usuario['assinatura'] ?? ''); ?>">
        </div>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i>
            Atualizar
        </button>
    </div>
</form>

<!-- Modal fullscreen para assinatura -->
<div id="signatureModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1050;">
    <div style="position:relative; width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
        <div style="background:#fff; width:100%; height:100%; padding:12px; box-sizing:border-box; position:relative;">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <button type="button" class="btn btn-warning btn-sm" onclick="limparModalAssinatura()">Limpar</button>
                </div>
                <div>
                    <button type="button" class="btn btn-success btn-sm" onclick="salvarModalAssinatura()">Salvar</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="fecharModalAssinatura()">Fechar</button>
                </div>
            </div>
            <div style="width:100%; height:calc(100% - 48px); overflow:auto; -webkit-overflow-scrolling:touch; display:flex; align-items:center; justify-content:center;">
                <canvas id="modal_canvas" style="background:#fff; border:1px solid #ddd; width:auto; height:auto; display:block;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// ========== MÁSCARAS COM INPUTMASK ==========
$(document).ready(function() {
    // Máscara CPF: 000.000.000-00
    Inputmask('999.999.999-99').mask('#cpf');
    Inputmask('999.999.999-99').mask('#cpf_conjuge');
    
    // Máscara Telefone: (00) 00000-0000 ou (00) 0000-0000
    Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone');
    Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone_conjuge');
    
    // Máscara CEP: 00000-000
    Inputmask('99999-999').mask('#cep');

    // Máscara RG
    Inputmask({ regex: "\\d{1,8}-[\\dXx]" }).mask('#rg');
    Inputmask({ regex: "\\d{1,8}-[\\dXx]" }).mask('#rg_conjuge');

    function aplicarRgIgualCpf(aplicar) {
        const rgInput = document.getElementById('rg');
        if (aplicar) {
            rgInput.value = document.getElementById('cpf').value;
            rgInput.setAttribute('disabled', 'disabled');
            Inputmask('999.999.999-99').mask('#rg');
        } else {
            rgInput.removeAttribute('disabled');
            Inputmask({ regex: "\\d{1,8}-[\\dXx]" }).mask('#rg');
        }
    }
    aplicarRgIgualCpf(document.getElementById('rg_igual_cpf').checked);
    document.getElementById('rg_igual_cpf').addEventListener('change', function(){
        aplicarRgIgualCpf(this.checked);
        if (!this.checked) {
            document.getElementById('rg').value = '<?php echo htmlspecialchars($usuario['rg'] ?? ''); ?>';
        }
    });
    document.getElementById('cpf').addEventListener('input', function(){
        if (document.getElementById('rg_igual_cpf').checked) {
            document.getElementById('rg').value = this.value;
        }
    });

    const casadoCb = document.getElementById('casado');
    casadoCb.addEventListener('change', function(){
        document.getElementById('cardConjuge').style.display = this.checked ? '' : 'none';
    });
});

// ========== VIACEP: BUSCA AUTOMÁTICA DE ENDEREÇO ==========
document.getElementById('cep').addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');
    
    if (cep.length !== 8) return;
    
    // Limpar campos antes de buscar
    document.getElementById('logradouro').value = 'Buscando...';
    document.getElementById('bairro').value = '';
    document.getElementById('cidade').value = '';
    document.getElementById('estado').value = '';
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                alert('CEP não encontrado!');
                document.getElementById('logradouro').value = '';
                return;
            }
            
            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';
            
            // Focar no número após preencher
            document.getElementById('numero').focus();
        })
        .catch(error => {
            console.error('Erro ao buscar CEP:', error);
            alert('Erro ao buscar CEP. Tente novamente.');
            document.getElementById('logradouro').value = '';
        });
});

// ========== ASSINATURA DIGITAL (PADRÃO MODAL) ==========
// Variáveis globais
let currentField = 'usuario'; // Campo único neste formulário
let modalCanvas, modalCtx, modalDrawing=false, modalLastX=0, modalLastY=0;
let signaturePad = null;
let pointerListenersEnabled = false;

// Inicializar canvas de preview (somente leitura)
function initPreviewCanvas(canvasId) {
    const canvas = document.getElementById(canvasId);
    canvas.style.pointerEvents = 'none';
    return canvas;
}

// Desenhar imagem dataURL em canvas de preview
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
        const x = (c.width - w)/2;
        const y = (c.height - h)/2;
        ctx.drawImage(img, x, y, w, h);
    };
    img.src = dataUrl;
}

// Limpar canvas de preview
function clearPreviewCanvas(id) {
    const c = document.getElementById('canvas_' + id);
    const ctx = c.getContext('2d');
    ctx.clearRect(0,0,c.width,c.height);
    document.getElementById('assinatura_' + id).value = '';
}

// Inicializar modal canvas
function initModalCanvas() {
    modalCanvas = document.getElementById('modal_canvas');
    modalCtx = modalCanvas.getContext('2d');
    try {
        modalCanvas.style.touchAction = 'none';
        modalCanvas.style.webkitUserSelect = 'none';
        modalCanvas.style.userSelect = 'none';
    } catch(e){}
}

// Redimensionar modal canvas com devicePixelRatio
function resizeModalCanvas() {
    if (!modalCanvas) return;
    const rect = modalCanvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    modalCanvas.width = Math.floor(rect.width * dpr);
    modalCanvas.height = Math.floor(rect.height * dpr);
    modalCtx.setTransform(1,0,0,1,0,0);
    modalCtx.scale(dpr, dpr);
    modalCtx.lineWidth = 2;
    modalCtx.lineCap = 'round';
    modalCtx.strokeStyle = '#000000';
}

// Configurar modal para landscape (95% width, 40% height)
function setModalLandscape() {
    if (!modalCanvas) initModalCanvas();
    const cssW = Math.floor(window.innerWidth * 0.95);
    const cssH = Math.floor(window.innerHeight * 0.40);
    modalCanvas.style.width = cssW + 'px';
    modalCanvas.style.height = cssH + 'px';
    resizeModalCanvas();
    try { modalCtx.strokeStyle = '#000000'; } catch(e){}
}

// Iniciar SignaturePad
function startSignaturePad(keepExisting=false){
    if (!modalCanvas) initModalCanvas();
    if (!keepExisting) {
        try { modalCtx.clearRect(0,0,modalCanvas.width, modalCanvas.height); } catch(e){}
    }
    if (typeof SignaturePad !== 'undefined') {
        disablePointerDrawing();
        signaturePad = new SignaturePad(modalCanvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'black' });
        if (!keepExisting) signaturePad.clear();
    } else {
        signaturePad = null;
        enablePointerDrawing();
    }
}

// Handlers para desenho manual (fallback)
function getModalCoords(e){
    if (!modalCanvas) return {x:0,y:0};
    const rect = modalCanvas.getBoundingClientRect();
    const clientX = (e.clientX !== undefined ? e.clientX : (e.touches ? e.touches[0].clientX : 0));
    const clientY = (e.clientY !== undefined ? e.clientY : (e.touches ? e.touches[0].clientY : 0));
    return { x: clientX - rect.left, y: clientY - rect.top };
}

function modalPointerDown(e){
    try { e.preventDefault(); } catch(err){}
    if (!modalCanvas) return;
    try { modalCanvas.setPointerCapture(e.pointerId); } catch(err){}
    modalDrawing = true;
    const p = getModalCoords(e);
    modalLastX = p.x; modalLastY = p.y;
    try { modalCtx.beginPath(); modalCtx.moveTo(modalLastX, modalLastY); } catch(err){}
}

function modalPointerMove(e){
    if (!modalDrawing) return;
    try { e.preventDefault(); } catch(err){}
    const p = getModalCoords(e);
    try {
        modalCtx.beginPath();
        modalCtx.moveTo(modalLastX, modalLastY);
        modalCtx.lineTo(p.x, p.y);
        modalCtx.stroke();
    } catch(err){}
    modalLastX = p.x; modalLastY = p.y;
}

function modalPointerUp(e){
    try { if (modalCanvas && e && e.pointerId) modalCanvas.releasePointerCapture(e.pointerId); } catch(err){}
    modalDrawing = false;
}

function enablePointerDrawing(){
    if (!modalCanvas || pointerListenersEnabled) return;
    modalCanvas.addEventListener('pointerdown', modalPointerDown);
    modalCanvas.addEventListener('pointermove', modalPointerMove);
    modalCanvas.addEventListener('pointerup', modalPointerUp);
    modalCanvas.addEventListener('pointercancel', modalPointerUp);
    pointerListenersEnabled = true;
}

function disablePointerDrawing(){
    if (!modalCanvas || !pointerListenersEnabled) return;
    modalCanvas.removeEventListener('pointerdown', modalPointerDown);
    modalCanvas.removeEventListener('pointermove', modalPointerMove);
    modalCanvas.removeEventListener('pointerup', modalPointerUp);
    modalCanvas.removeEventListener('pointercancel', modalPointerUp);
    pointerListenersEnabled = false;
}

function resizeModalIfVisible(){ 
    try{ 
        const m=document.getElementById('signatureModal'); 
        if (m && m.style.display !== 'none') {
            if (!modalCanvas) initModalCanvas();
            modalCanvas.style.width = Math.floor(window.innerWidth * 0.95) + 'px';
            modalCanvas.style.height = Math.floor(window.innerHeight * 0.40) + 'px';
            resizeModalCanvas();
        } 
    }catch(e){} 
}

// Abrir modal para assinatura
window.abrirModalAssinatura = async function(fieldId){
    currentField = fieldId;
    const modal = document.getElementById('signatureModal');
    modal.style.display = 'block';
    
    // Tentar fullscreen e landscape
    try{
        if (modal.requestFullscreen) await modal.requestFullscreen();
        else if (document.documentElement.requestFullscreen) await document.documentElement.requestFullscreen();
        if (screen && screen.orientation && screen.orientation.lock) {
            try{ await screen.orientation.lock('landscape'); } catch(e){}
        }
    }catch(err){ /* ignore */ }
    
    // Inicializar canvas
    if (!modalCanvas) initModalCanvas();
    setModalLandscape();
    
    // Carregar assinatura existente se houver
    const existing = document.getElementById('assinatura_' + fieldId).value;
    startSignaturePad(true);
    if (existing) {
        const img = new Image();
        img.onload = function(){
            try{
                const rect = modalCanvas.getBoundingClientRect();
                const cssW = rect.width; const cssH = rect.height;
                modalCtx.clearRect(0,0,cssW,cssH);
                const scale = Math.min(cssW / img.width, cssH / img.height);
                const w = img.width * scale; const h = img.height * scale;
                modalCtx.drawImage(img, (cssW - w)/2, (cssH - h)/2, w, h);
            }catch(e){}
        };
        img.src = existing;
    }
    
    // Event listeners para resize
    window.addEventListener('resize', resizeModalIfVisible);
    window.addEventListener('orientationchange', resizeModalIfVisible);
};

// Limpar modal
window.limparModalAssinatura = function(){
    if (!modalCanvas) return;
    if (signaturePad) {
        signaturePad.clear();
    }
    try { modalCtx.clearRect(0,0,modalCanvas.width, modalCanvas.height); } catch(e){}
};

// Salvar assinatura do modal
window.salvarModalAssinatura = function(){
    let data = null;
    if (signaturePad) {
        if (signaturePad.isEmpty()) data = null; 
        else data = signaturePad.toDataURL('image/png', 0.8); // Compressão em 80%
    } else {
        data = modalCanvas.toDataURL('image/png', 0.8); // Compressão em 80%
    }
    
    const preview = document.getElementById('canvas_' + currentField);
    const pCtx = preview.getContext('2d');
    if (data) {
        const img = new Image();
        img.onload = function(){
            pCtx.clearRect(0,0,preview.width, preview.height);
            const scale = Math.min(preview.width / img.width, preview.height / img.height);
            const w = img.width * scale; 
            const h = img.height * scale;
            const x = (preview.width - w)/2; 
            const y = (preview.height - h)/2;
            pCtx.drawImage(img, x, y, w, h);
            document.getElementById('assinatura_' + currentField).value = data;
            fecharModalAssinatura();
        };
        img.src = data;
    } else {
        pCtx.clearRect(0,0,preview.width, preview.height);
        document.getElementById('assinatura_' + currentField).value = '';
        fecharModalAssinatura();
    }
};

// Fechar modal
window.fecharModalAssinatura = async function(){
    document.getElementById('signatureModal').style.display='none';
    try{ 
        if (document.fullscreenElement && document.exitFullscreen) await document.exitFullscreen(); 
        if (screen && screen.orientation && screen.orientation.unlock) { 
            try{ screen.orientation.unlock(); } catch(e){} 
        } 
    }catch(err){}
    if (signaturePad) { 
        try{ signaturePad.off && signaturePad.off(); } catch(e){} 
        signaturePad = null; 
    }
    disablePointerDrawing();
    window.removeEventListener('resize', resizeModalIfVisible);
    window.removeEventListener('orientationchange', resizeModalIfVisible);
};

// Inicializar preview canvas na carga e carregar assinatura existente
document.addEventListener('DOMContentLoaded', function() {
    initPreviewCanvas('canvas_usuario');
    initPreviewCanvas('canvas_conjuge');
    const existingSignature = document.getElementById('assinatura_usuario').value;
    if (existingSignature) {
        drawImageOnCanvas('canvas_usuario', existingSignature);
    }
    const existingConjuge = document.getElementById('assinatura_conjuge').value;
    if (existingConjuge) {
        drawImageOnCanvas('canvas_conjuge', existingConjuge);
    }
});

// ========== VALIDAÇÃO E ENVIO DO FORMULÁRIO ==========
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha').value;
    const confirmar = document.getElementById('confirmar_senha').value;
    
    // Validar senhas (somente se preenchidas)
    if (senha || confirmar) {
        if (senha !== confirmar) {
            e.preventDefault();
            alert('As senhas não conferem!');
            return false;
        }
    }
    
    // Validação simples de campos do cônjuge se marcado como casado
    if (document.getElementById('casado').checked) {
        const obrigatoriosConjuge = ['nome_conjuge','cpf_conjuge','telefone_conjuge'];
        for (let id of obrigatoriosConjuge) {
            const el = document.getElementById(id);
            if (el && !el.value.trim()) {
                e.preventDefault();
                alert('Preencha todos os dados obrigatórios do cônjuge.');
                return false;
            }
        }
    }
    // Assinaturas já estão salvas nos campos hidden
});
</script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
