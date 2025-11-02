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
            <div class="row g-3">
                <div class="col-4">
                    <label for="codigo" class="form-label">Código</label>
                    <input type="text" class="form-control text-center fw-bold" name="codigo" 
                           value="<?php echo $mapeamento_array['codigo'] ?? 'A'; ?>" maxlength="3" required>
                </div>
                <div class="col-4">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control text-center fw-bold" name="nome" 
                           value="<?php echo $mapeamento_array['nome'] ?? 'D'; ?>" maxlength="3" required>
                </div>
                <div class="col-4">
                    <label for="dependencia" class="form-label">Dependência</label>
                    <input type="text" class="form-control text-center fw-bold" name="dependencia" 
                           value="<?php echo $mapeamento_array['dependencia'] ?? 'P'; ?>" maxlength="3" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Atualizar Dados -->
    <div class="card mb-3">
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
                            <div class="btn-group-vertical">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearCanvas('canvas_responsavel')">Limpar</button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openSignatureModal()">Fazer Assinatura</button>
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
    function initModalCanvas(){ modalCanvas = document.getElementById('modal_canvas'); modalCtx = modalCanvas.getContext('2d'); resizeModalCanvas(); modalCanvas.addEventListener('mousedown', modalStart); modalCanvas.addEventListener('touchstart', modalStart, { passive: false }); modalCanvas.addEventListener('mousemove', modalMove); modalCanvas.addEventListener('touchmove', modalMove, { passive: false }); modalCanvas.addEventListener('mouseup', modalEnd); modalCanvas.addEventListener('mouseout', modalEnd); modalCanvas.addEventListener('touchend', modalEnd); modalCtx.lineWidth = 2; modalCtx.lineCap = 'round'; }
    function getModalCoords(e){ const rect = modalCanvas.getBoundingClientRect(); const clientX = (e.touches?e.touches[0].clientX:e.clientX); const clientY = (e.touches?e.touches[0].clientY:e.clientY); const scaleX = modalCanvas.width / rect.width; const scaleY = modalCanvas.height / rect.height; return { x: (clientX - rect.left) * scaleX, y: (clientY - rect.top) * scaleY }; }
    function modalStart(e){ modalDrawing=true; const p=getModalCoords(e); modalLastX=p.x; modalLastY=p.y; }
    function modalMove(e){ if(!modalDrawing) return; e.preventDefault(); const p=getModalCoords(e); modalCtx.beginPath(); modalCtx.moveTo(modalLastX, modalLastY); modalCtx.lineTo(p.x, p.y); modalCtx.stroke(); modalLastX=p.x; modalLastY=p.y; }
    function modalEnd(){ modalDrawing=false; }

    // abrir modal em branco já girado, pronto para assinar
    window.openSignatureModal = function(){
        document.getElementById('signatureModal').style.display='block';
        if(!modalCanvas) initModalCanvas();
        const w = Math.max(800, window.innerWidth*0.92);
        const h = Math.max(360, window.innerHeight*0.72);
        modalCanvas.width = h;
        modalCanvas.height = w;
        modalCtx = modalCanvas.getContext('2d');
        modalCtx.lineWidth = 2; modalCtx.lineCap = 'round';
        modalCtx.fillStyle = '#ffffff';
        modalCtx.fillRect(0,0,modalCanvas.width, modalCanvas.height);
        modalCtx.strokeStyle = '#000000';
    };
    window.closeSignatureModal = function(){ document.getElementById('signatureModal').style.display='none'; };
    window.applyModalSignature = function(){ const data = modalCanvas.toDataURL('image/png'); const preview=document.getElementById('canvas_responsavel'); const pCtx=preview.getContext('2d'); const img=new Image(); img.onload=function(){ pCtx.clearRect(0,0,preview.width,preview.height); const scale=Math.min(preview.width/img.width, preview.height/img.height); const w=img.width*scale, h=img.height*scale, x=(preview.width-w)/2, y=(preview.height-h)/2; pCtx.drawImage(img,x,y,w,h); document.getElementById('assinatura_responsavel').value=data; closeSignatureModal(); }; img.src=data; };
    window.toggleRotateModal = function(){ if(!modalCanvas) return; const temp = modalCanvas.width; modalCanvas.width = modalCanvas.height; modalCanvas.height = temp; };

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
            sel.innerHTML = '<option value="">Selecione o estado</option>';
            const opt = document.createElement('option'); opt.value = mt.sigla+'|'+mt.id; opt.text = mt.nome + ' ('+mt.sigla+')'; sel.appendChild(opt);
            // se havia valor pré-existente que contenha MT - Cidade, tentaremos selecionar a cidade depois
            if(preAdministracao){
                // nada a fazer aqui, a seleção real será feita após carregar cidades
            }
        }catch(err){ sel.innerHTML='<option value="">Erro ao carregar estados</option>'; console.error(err); }
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
            // extrair sigla do estado selecionado
            const adSel = document.getElementById('administracao');
            const sigla = (adSel.value && adSel.value.indexOf('|')>-1) ? adSel.value.split('|')[0] : 'MT';
            cidades.forEach(ct=>{
                const opt = document.createElement('option');
                opt.value = sigla + ' - ' + ct.nome;
                opt.text = sigla + ' - ' + ct.nome;
                cidadeSel.appendChild(opt);
            });
            cidadeSel.disabled = false;
            if(preCidade){
                for(const o of cidadeSel.options) if(o.value===preCidade){ o.selected=true; break; }
            }
            // se foi pré informada administracao no formato "MT - Cidade", selecionar a cidade correspondente
            if(preAdministracao && preAdministracao.includes(' - ')){
                for(const o of cidadeSel.options) if(o.value===preAdministracao){ o.selected=true; break; }
            }
        }catch(err){ cidadeSel.innerHTML='<option value="">Erro ao carregar cidades</option>'; console.error(err); }
    }

    document.getElementById('administracao').addEventListener('change', function(){ const val=this.value; if(!val){ document.getElementById('cidade').innerHTML='<option value="">Selecione o estado primeiro</option>'; document.getElementById('cidade').disabled=true; return; } const parts = val.split('|'); const estadoId = parts[1]; loadCidades(estadoId); });
    // quando a cidade muda, também ajustamos o valor do campo administracao para o mesmo formato "MT - Cidade"
    document.getElementById('cidade').addEventListener('change', function(){ const cv = this.value; if(cv){ const admin = document.getElementById('administracao'); admin.value = admin.value; /* keep selection */ } });

    // Antes de submeter, garantir que administracao e cidade estejam no formato "MT - Cidade"
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){ const nome = document.getElementById('nome_responsavel').value.trim(); const estado = document.getElementById('administracao').value; const cidadeVal = document.getElementById('cidade').value; if(!nome || !estado || !cidadeVal){ e.preventDefault(); alert('Por favor preencha Nome do Responsável, Estado e Cidade (campos obrigatórios).'); return false; }
        // garantir que administracao seja gravada como "MT - Cidade"
        const adminField = document.getElementById('administracao');
        if(cidadeVal && adminField.value.indexOf(' - ')===-1){ adminField.value = cidadeVal; }
        // assinatura
        function isCanvasBlank(c){ const blank=document.createElement('canvas'); blank.width=c.width; blank.height=c.height; return c.toDataURL()===blank.toDataURL(); }
        if(!isCanvasBlank(document.getElementById('canvas_responsavel'))){ document.getElementById('assinatura_responsavel').value = document.getElementById('canvas_responsavel').toDataURL('image/png'); }
    });

    // inicialização: carregar estados, depois cidades e aplicar seleção pré-existente se houver
    (async function(){
        await loadEstados();
        const adminSel = document.getElementById('administracao');
        // encontrar opção MT
        if(adminSel.options.length>1){
            const parts = adminSel.options[1].value.split('|');
            const mtId = parts[1];
            await loadCidades(mtId);
            // tentar selecionar valores pré-carregados
            if(preAdministracao){
                const citySel = document.getElementById('cidade');
                for(const o of citySel.options) if(o.value===preAdministracao){ o.selected=true; break; }
            }
            if(preCidade){
                const citySel = document.getElementById('cidade');
                for(const o of citySel.options) if(o.value===preCidade){ o.selected=true; break; }
            }
        }
    })();
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
