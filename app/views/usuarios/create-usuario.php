<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
include __DIR__ . '/../../../CRUD/CREATE/usuario.php';

$pageTitle = 'Novo Usuário';
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

<form method="POST" id="formUsuario">
    <!-- Campo oculto: tipo de usuário -->
    <input type="hidden" name="tipo" value="Administrador/Acessor">
    
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
                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>" 
                           placeholder="000.000.000-00" required>
                </div>
                <div class="col-md-6">
                    <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="telefone" name="telefone" 
                           value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" 
                           placeholder="(00) 00000-0000" required>
                </div>
            </div>

            <div class="mb-3 mt-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="senha" class="form-label">Senha <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="senha" name="senha" 
                           minlength="6" required>
                    <small class="text-muted">Mínimo de 6 caracteres</small>
                </div>

                <div class="col-md-6">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                           minlength="6" required>
                </div>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo (isset($_POST['ativo']) || !isset($_POST['nome'])) ? 'checked' : ''; ?>>
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
                <div class="col-md-4">
                    <label for="cep" class="form-label">CEP</label>
                    <input type="text" class="form-control" id="cep" name="endereco_cep" 
                           value="<?php echo htmlspecialchars($_POST['endereco_cep'] ?? ''); ?>" 
                           placeholder="00000-000">
                    <small class="text-muted">Preencha para buscar automaticamente</small>
                </div>
                <div class="col-md-8">
                    <label for="logradouro" class="form-label">Logradouro</label>
                    <input type="text" class="form-control" id="logradouro" name="endereco_logradouro" 
                           value="<?php echo htmlspecialchars($_POST['endereco_logradouro'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label for="numero" class="form-label">Número</label>
                    <input type="text" class="form-control" id="numero" name="endereco_numero" 
                           value="<?php echo htmlspecialchars($_POST['endereco_numero'] ?? ''); ?>">
                </div>
                <div class="col-md-5">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="endereco_complemento" 
                           value="<?php echo htmlspecialchars($_POST['endereco_complemento'] ?? ''); ?>" 
                           placeholder="Apto, bloco, etc">
                </div>
                <div class="col-md-4">
                    <label for="bairro" class="form-label">Bairro</label>
                    <input type="text" class="form-control" id="bairro" name="endereco_bairro" 
                           value="<?php echo htmlspecialchars($_POST['endereco_bairro'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-8">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="endereco_cidade" 
                           value="<?php echo htmlspecialchars($_POST['endereco_cidade'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="endereco_estado">
                        <option value="">Selecione</option>
                        <option value="AC" <?php echo ($_POST['endereco_estado'] ?? '') === 'AC' ? 'selected' : ''; ?>>Acre</option>
                        <option value="AL" <?php echo ($_POST['endereco_estado'] ?? '') === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                        <option value="AP" <?php echo ($_POST['endereco_estado'] ?? '') === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                        <option value="AM" <?php echo ($_POST['endereco_estado'] ?? '') === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                        <option value="BA" <?php echo ($_POST['endereco_estado'] ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                        <option value="CE" <?php echo ($_POST['endereco_estado'] ?? '') === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                        <option value="DF" <?php echo ($_POST['endereco_estado'] ?? '') === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                        <option value="ES" <?php echo ($_POST['endereco_estado'] ?? '') === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                        <option value="GO" <?php echo ($_POST['endereco_estado'] ?? '') === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                        <option value="MA" <?php echo ($_POST['endereco_estado'] ?? '') === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                        <option value="MT" <?php echo ($_POST['endereco_estado'] ?? '') === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                        <option value="MS" <?php echo ($_POST['endereco_estado'] ?? '') === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                        <option value="MG" <?php echo ($_POST['endereco_estado'] ?? '') === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                        <option value="PA" <?php echo ($_POST['endereco_estado'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pará</option>
                        <option value="PB" <?php echo ($_POST['endereco_estado'] ?? '') === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                        <option value="PR" <?php echo ($_POST['endereco_estado'] ?? '') === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                        <option value="PE" <?php echo ($_POST['endereco_estado'] ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                        <option value="PI" <?php echo ($_POST['endereco_estado'] ?? '') === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                        <option value="RJ" <?php echo ($_POST['endereco_estado'] ?? '') === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                        <option value="RN" <?php echo ($_POST['endereco_estado'] ?? '') === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                        <option value="RS" <?php echo ($_POST['endereco_estado'] ?? '') === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                        <option value="RO" <?php echo ($_POST['endereco_estado'] ?? '') === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                        <option value="RR" <?php echo ($_POST['endereco_estado'] ?? '') === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                        <option value="SC" <?php echo ($_POST['endereco_estado'] ?? '') === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                        <option value="SP" <?php echo ($_POST['endereco_estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                        <option value="SE" <?php echo ($_POST['endereco_estado'] ?? '') === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                        <option value="TO" <?php echo ($_POST['endereco_estado'] ?? '') === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Assinatura Digital -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-pen me-2"></i>
            Assinatura Digital
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Clique no botão abaixo para fazer sua assinatura digital.
            </p>
            
            <!-- Container de Preview da Assinatura -->
            <div class="signature-preview-container mb-3">
                <canvas id="canvas_usuario" width="800" height="160" class="signature-preview-canvas" style="border:1px solid #dee2e6; border-radius:0.375rem; width:100%; height:auto; background:#f8f9fa;"></canvas>
            </div>
            
            <!-- Botão para abrir modal -->
            <button type="button" class="btn btn-primary btn-lg w-100" onclick="abrirModalAssinatura('usuario')">
                <i class="bi bi-pen me-2"></i>
                Fazer Assinatura
            </button>
            
            <!-- Campo hidden para armazenar assinatura em base64 -->
            <input type="hidden" id="assinatura_usuario" name="assinatura">
        </div>
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            Cadastrar Usuário
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
    
    // Máscara Telefone: (00) 00000-0000 ou (00) 0000-0000
    Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone');
    
    // Máscara CEP: 00000-000
    Inputmask('99999-999').mask('#cep');
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

// Inicializar preview canvas na carga
document.addEventListener('DOMContentLoaded', function() {
    initPreviewCanvas('canvas_usuario');
});

// ========== VALIDAÇÃO E ENVIO DO FORMULÁRIO ==========
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha').value;
    const confirmar = document.getElementById('confirmar_senha').value;
    
    // Validar senhas
    if (senha !== confirmar) {
        e.preventDefault();
        alert('As senhas não conferem!');
        return false;
    }
    
    // Assinatura já está salva no campo hidden assinatura_usuario
    // O backend receberá via $_POST['assinatura']
});
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_create_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
