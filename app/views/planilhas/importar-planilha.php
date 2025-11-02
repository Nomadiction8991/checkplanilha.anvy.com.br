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
                           value="<?php echo htmlspecialchars($_POST['nome_responsavel'] ?? ''); ?>" maxlength="255" required>
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
                            <div class="btn-group-vertical">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearCanvas('canvas_responsavel')">Limpar</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadCanvas('canvas_responsavel')">Baixar</button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openSignatureModal()">Expandir</button>
                            </div>
                        </div>
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
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleRotateModal()" id="btnRotate">Girar</button>
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
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    let drawing = false;
    let lastX = 0, lastY = 0;

    // Map client coordinates to canvas coordinates (handles CSS scaling)
    function getCoords(e, element) {
        const rect = element.getBoundingClientRect();
        const clientX = (e.touches ? e.touches[0].clientX : e.clientX);
        const clientY = (e.touches ? e.touches[0].clientY : e.clientY);
        const scaleX = element.width / rect.width;
        const scaleY = element.height / rect.height;
        const x = (clientX - rect.left) * scaleX;
        const y = (clientY - rect.top) * scaleY;
        return { x, y };
    }

    function start(e) {
        drawing = true;
        const p = getCoords(e, canvas);
        lastX = p.x; lastY = p.y;
    }

    function move(e) {
        if (!drawing) return;
        e.preventDefault();
        const p = getCoords(e, canvas);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        lastX = p.x; lastY = p.y;
    }

    function end() { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('mouseup', end);
    canvas.addEventListener('mouseout', end);
    canvas.addEventListener('touchend', end);

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
        resizeModalCanvas();
        modalCanvas.addEventListener('mousedown', modalStart);
        modalCanvas.addEventListener('touchstart', modalStart, { passive: false });
        modalCanvas.addEventListener('mousemove', modalMove);
        modalCanvas.addEventListener('touchmove', modalMove, { passive: false });
        modalCanvas.addEventListener('mouseup', modalEnd);
        modalCanvas.addEventListener('mouseout', modalEnd);
        modalCanvas.addEventListener('touchend', modalEnd);
        modalCtx.lineWidth = 2;
        modalCtx.lineCap = 'round';
    }
    function resizeModalCanvas() {
        if (!modalCanvas) return;
        const w = Math.max(800, window.innerWidth * 0.92);
        const h = Math.max(360, window.innerHeight * 0.72);
        modalCanvas.width = w;
        modalCanvas.height = h;
    }
    function getModalCoords(e) {
        const rect = modalCanvas.getBoundingClientRect();
        const clientX = (e.touches ? e.touches[0].clientX : e.clientX);
        const clientY = (e.touches ? e.touches[0].clientY : e.clientY);
        const scaleX = modalCanvas.width / rect.width;
        const scaleY = modalCanvas.height / rect.height;
        return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY };
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

    // Abrir modal, copiar preview para modal
    window.openSignatureModal = function(){
        document.getElementById('signatureModal').style.display = 'block';
        if (!modalCanvas) initModalCanvas();
        resizeModalCanvas();
        const preview = document.getElementById('canvas_responsavel');
        const data = preview.toDataURL('image/png');
        const img = new Image();
        img.onload = function(){
            modalCtx.clearRect(0,0,modalCanvas.width, modalCanvas.height);
            const scale = Math.min(modalCanvas.width / img.width, modalCanvas.height / img.height);
            const w = img.width * scale; const h = img.height * scale;
            const x = (modalCanvas.width - w)/2; const y = (modalCanvas.height - h)/2;
            modalCtx.drawImage(img, x, y, w, h);
        };
        img.src = data;
    };

    window.closeSignatureModal = function(){
        document.getElementById('signatureModal').style.display = 'none';
    };

    window.applyModalSignature = function(){
        const data = modalCanvas.toDataURL('image/png');
        const preview = document.getElementById('canvas_responsavel');
        const pCtx = preview.getContext('2d');
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
    };

    window.toggleRotateModal = function(){
        if (!modalCanvas) return;
        // trocar w/h para simular giro
        const temp = modalCanvas.width;
        modalCanvas.width = modalCanvas.height;
        modalCanvas.height = temp;
    };

    // Antes do submit, serializar o preview canvas para o hidden input e validar campos obrigatórios
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){
        const nome = document.getElementById('nome_responsavel').value.trim();
        const estado = document.getElementById('administracao').value;
        const cidade = document.getElementById('cidade').value;
        if (!nome || !estado || !cidade) {
            e.preventDefault();
            alert('Por favor preencha Nome do Responsável, Estado e Cidade (campos obrigatórios).');
            return false;
        }
        // garantir que administracao seja gravada como "MT - Cidade"
        const adminField = document.getElementById('administracao');
        if (cidade && adminField.value.indexOf(' - ') === -1) { adminField.value = cidade; }
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
            sel.innerHTML = '<option value="">Selecione o estado</option>';
            const opt = document.createElement('option'); opt.value = mt.sigla + '|' + mt.id; opt.text = mt.nome + ' ('+mt.sigla+')'; sel.appendChild(opt);
            const pre = {$pre_administracao};
            // seleção de cidade ocorrerá após carregamento de cidades
        } catch(err){
            sel.innerHTML = '<option value="">Erro ao carregar estados</option>';
            console.error(err);
        }
    }
    async function loadCidades(estadoId){
        const cidadeSel = document.getElementById('cidade');
        cidadeSel.innerHTML = '<option value="">Carregando cidades...</option>';
        cidadeSel.disabled = true;
        try{
            const res = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/'+estadoId+'/municipios');
            const cidades = await res.json();
            cidades.sort((a,b)=>a.nome.localeCompare(b.nome));
            cidadeSel.innerHTML = '<option value="">Selecione a cidade</option>';
            // criar opções no formato 'MT - Cidade'
            const adSel = document.getElementById('administracao');
            const sigla = (adSel.value && adSel.value.indexOf('|')>-1) ? adSel.value.split('|')[0] : 'MT';
            cidades.forEach(ct => {
                const opt = document.createElement('option');
                opt.value = sigla + ' - ' + ct.nome;
                opt.text = sigla + ' - ' + ct.nome;
                cidadeSel.appendChild(opt);
            });
            cidadeSel.disabled = false;
            const pre = {$pre_cidade};
            if (pre) {
                for(const o of cidadeSel.options) if (o.value===pre) { o.selected=true; break; }
            }
        } catch(err){
            cidadeSel.innerHTML = '<option value="">Erro ao carregar cidades</option>';
            console.error(err);
        }
    }

    document.getElementById('administracao').addEventListener('change', function(){
        const val = this.value;
        if (!val) {
            document.getElementById('cidade').innerHTML = '<option value="">Selecione o estado primeiro</option>';
            document.getElementById('cidade').disabled = true;
            return;
        }
        const parts = val.split('|');
        const estadoId = parts[1];
        loadCidades(estadoId);
    });

    // inicialização com pré-seleção (se necessário)
    (async function(){
        await loadEstados();
        const adminSel = document.getElementById('administracao');
        if(adminSel.options.length>1){
            const parts = adminSel.options[1].value.split('|');
            const mtId = parts[1];
            await loadCidades(mtId);
            const preC = {$pre_cidade};
            const preA = {$pre_administracao};
            const citySel = document.getElementById('cidade');
            if(preA){ for(const o of citySel.options) if(o.value===preA){ o.selected=true; break; } }
            if(preC){ for(const o of citySel.options) if(o.value===preC){ o.selected=true; break; } }
        }
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
