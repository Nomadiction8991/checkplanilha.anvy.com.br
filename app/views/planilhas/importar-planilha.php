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
                <div class="col-md-12">
                    <label for="nome_responsavel" class="form-label">Nome do Responsável</label>
                    <input type="text" class="form-control" id="nome_responsavel" name="nome_responsavel" 
                           value="<?php echo htmlspecialchars($_POST['nome_responsavel'] ?? ''); ?>" maxlength="255">
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label class="form-label">Assinatura do Responsável</label>
                    <div class="border p-2 mb-2">
                        <canvas id="canvas_responsavel" width="600" height="150" style="touch-action: none; background:#fff; border:1px solid #ddd;"></canvas>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearCanvas('canvas_responsavel')">Limpar</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadCanvas('canvas_responsavel')">Baixar</button>
                    </div>
                    <input type="hidden" name="assinatura_responsavel" id="assinatura_responsavel">
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
$script = <<<HTML
<script>
function initSignature(canvasId) {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    let drawing = false;
    let lastX = 0, lastY = 0;

    function start(e) {
        drawing = true;
        const rect = canvas.getBoundingClientRect();
        const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
        const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
        lastX = x; lastY = y;
    }

    function move(e) {
        if (!drawing) return;
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
        const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top;
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(x, y);
        ctx.stroke();
        lastX = x; lastY = y;
    }

    function end() { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('touchstart', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('touchmove', move);
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
    if (id === 'canvas_admin') document.getElementById('assinatura_administrador').value = '';
    if (id === 'canvas_acessor') document.getElementById('assinatura_acessor').value = '';
}

function downloadCanvas(id) {
    const c = document.getElementById(id);
    const a = document.createElement('a');
    a.href = c.toDataURL('image/png');
    a.download = id + '.png';
    a.click();
}

// Antes do submit, serializar as assinaturas para os inputs hidden
document.addEventListener('DOMContentLoaded', function(){
    const cResp = initSignature('canvas_responsavel');

    const form = document.querySelector('form');
    form.addEventListener('submit', function(){
        const dataResp = document.getElementById('canvas_responsavel').toDataURL('image/png');
        // Se canvas estiver vazio (toda área transparente), salvar vazio
        function isCanvasBlank(c) {
            const blank = document.createElement('canvas');
            blank.width = c.width; blank.height = c.height;
            return c.toDataURL() === blank.toDataURL();
        }
        if (!isCanvasBlank(document.getElementById('canvas_responsavel'))) {
            document.getElementById('assinatura_responsavel').value = dataResp;
        }
    });
});
</script>
HTML;

$contentHtml = $contentHtml . $script;
$tempFile = __DIR__ . '/../../../temp_importar_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
