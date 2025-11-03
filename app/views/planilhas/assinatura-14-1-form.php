<?php
require_once __DIR__ . '/../../../CRUD/conexao.php';

$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id_planilha'] ?? null;
$token = $_GET['token'] ?? null;

// Se tem token, busca por token (acesso público)
if ($token) {
    $sql = "SELECT a.*, pc.descricao_completa, p.comum
            FROM assinaturas_14_1 a
            JOIN produtos_cadastro pc ON a.id_produto = pc.id
            JOIN planilhas p ON a.id_planilha = p.id
            WHERE a.token = :token";
    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':token', $token);
    $stmt->execute();
    $assinatura = $stmt->fetch();
    
    if (!$assinatura) {
        die('Link inválido ou expirado.');
    }
    
    $id_produto = $assinatura['id_produto'];
    $id_planilha = $assinatura['id_planilha'];
    $acesso_publico = true;
} else {
    // Acesso interno (admin)
    if (!$id_produto || !$id_planilha) {
        header('Location: assinatura-14-1.php?id=' . ($id_planilha ?? ''));
        exit;
    }
    $acesso_publico = false;
}

// Buscar ou criar registro de assinatura
if (!$token) {
    $sql = "SELECT * FROM assinaturas_14_1 WHERE id_produto = :id_produto";
    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':id_produto', $id_produto);
    $stmt->execute();
    $assinatura = $stmt->fetch();
    
    // Se não existe, criar
    if (!$assinatura) {
        $token_novo = bin2hex(random_bytes(32));
        $sql = "INSERT INTO assinaturas_14_1 (id_produto, id_planilha, token, status) 
                VALUES (:id_produto, :id_planilha, :token, 'pendente')";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id_produto', $id_produto);
        $stmt->bindValue(':id_planilha', $id_planilha);
        $stmt->bindValue(':token', $token_novo);
        $stmt->execute();
        
        $assinatura = [
            'id' => $conexao->lastInsertId(),
            'token' => $token_novo,
            'status' => 'pendente'
        ];
    }
}

// Buscar informações do produto
$sql = "SELECT pc.*, p.comum 
        FROM produtos_cadastro pc
        JOIN planilhas p ON pc.id_planilha = p.id
        WHERE pc.id = :id_produto";
$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_produto', $id_produto);
$stmt->execute();
$produto = $stmt->fetch();

// Processar envio do formulário
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "UPDATE assinaturas_14_1 SET 
                nome_administrador = :nome_administrador,
                assinatura_administrador = :assinatura_administrador,
                nome_doador = :nome_doador,
                endereco_doador = :endereco_doador,
                cpf_doador = :cpf_doador,
                rg_doador = :rg_doador,
                assinatura_doador = :assinatura_doador,
                nome_conjuge = :nome_conjuge,
                endereco_conjuge = :endereco_conjuge,
                cpf_conjuge = :cpf_conjuge,
                rg_conjuge = :rg_conjuge,
                assinatura_conjuge = :assinatura_conjuge,
                status = 'assinado',
                ip_assinatura = :ip
                WHERE id = :id";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id', $assinatura['id']);
        $stmt->bindValue(':nome_administrador', $_POST['nome_administrador'] ?? '');
        $stmt->bindValue(':assinatura_administrador', $_POST['assinatura_administrador'] ?? '');
        $stmt->bindValue(':nome_doador', $_POST['nome_doador'] ?? '');
        $stmt->bindValue(':endereco_doador', $_POST['endereco_doador'] ?? '');
        $stmt->bindValue(':cpf_doador', $_POST['cpf_doador'] ?? '');
        $stmt->bindValue(':rg_doador', $_POST['rg_doador'] ?? '');
        $stmt->bindValue(':assinatura_doador', $_POST['assinatura_doador'] ?? '');
        $stmt->bindValue(':nome_conjuge', $_POST['nome_conjuge'] ?? '');
        $stmt->bindValue(':endereco_conjuge', $_POST['endereco_conjuge'] ?? '');
        $stmt->bindValue(':cpf_conjuge', $_POST['cpf_conjuge'] ?? '');
        $stmt->bindValue(':rg_conjuge', $_POST['rg_conjuge'] ?? '');
        $stmt->bindValue(':assinatura_conjuge', $_POST['assinatura_conjuge'] ?? '');
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
        
        $mensagem = 'Assinaturas salvas com sucesso!';
        $tipo_mensagem = 'success';
        
        // Recarregar dados
        $sql = "SELECT * FROM assinaturas_14_1 WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id', $assinatura['id']);
        $stmt->execute();
        $assinatura = $stmt->fetch();
        
    } catch (Exception $e) {
        $mensagem = 'Erro ao salvar: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Gerar URL pública
$protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$url_base = $protocolo . '://' . $host;
$caminho_arquivo = str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__);
$url_publica = $url_base . $caminho_arquivo . '?token=' . urlencode($assinatura['token']);

$pageTitle = $acesso_publico ? 'Assinatura Digital - 14.1' : 'Gerenciar Assinatura - 14.1';
$backUrl = $acesso_publico ? null : 'assinatura-14-1.php?id=' . urlencode($id_planilha);
$headerActions = '';

if (!$acesso_publico) {
    $headerActions = '
        <a href="../shared/menu-unificado.php?id=' . urlencode($id_planilha) . '&contexto=relatorio" class="btn-header-action" title="Menu">
            <i class="bi bi-list fs-5"></i>
        </a>
    ';
}

ob_start();
?>

<style>
.signature-canvas-container {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    background: #f8f9fa;
    padding: 1rem;
    margin-bottom: 1rem;
}

.signature-canvas {
    border: 1px solid #dee2e6;
    background: white;
    cursor: crosshair;
    touch-action: none;
    width: 100%;
    height: 200px;
    border-radius: 0.25rem;
}

.link-compartilhar {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 0.375rem;
    padding: 1rem;
}

.link-input {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    background: white;
}
</style>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$acesso_publico): ?>
<!-- Card com Link de Compartilhamento -->
<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-share me-2"></i>
        Link para Compartilhamento
    </div>
    <div class="card-body">
        <p class="mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Compartilhe este link com a pessoa responsável pelas assinaturas:
        </p>
        <div class="link-compartilhar">
            <div class="input-group">
                <input type="text" class="form-control link-input" id="linkCompartilhar" 
                       value="<?php echo htmlspecialchars($url_publica); ?>" readonly>
                <button class="btn btn-primary" type="button" onclick="copiarLink()">
                    <i class="bi bi-clipboard me-1"></i>
                    Copiar
                </button>
            </div>
        </div>
        <small class="text-muted d-block mt-2">
            <i class="bi bi-shield-check me-1"></i>
            Este link é único e seguro para este produto específico.
        </small>
    </div>
</div>
<?php endif; ?>

<!-- Informações do Produto -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-box-seam me-2"></i>
        Produto para Assinatura
    </div>
    <div class="card-body">
        <p class="mb-1"><strong>Planilha:</strong> <?php echo htmlspecialchars($produto['comum']); ?></p>
        <p class="mb-0"><strong>Descrição:</strong> <?php echo htmlspecialchars(substr($produto['descricao_completa'], 0, 200)); ?></p>
    </div>
</div>

<form method="POST" id="formAssinatura">
    <!-- Administrador/Acessor -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-badge me-2"></i>
            Administrador/Acessor
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nome_administrador" class="form-label">Nome <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nome_administrador" name="nome_administrador" 
                       value="<?php echo htmlspecialchars($assinatura['nome_administrador'] ?? ''); ?>" required>
            </div>
            
            <div>
                <label class="form-label">Assinatura <span class="text-danger">*</span></label>
                <div class="signature-canvas-container">
                    <canvas class="signature-canvas" id="canvas_administrador" width="800" height="200"></canvas>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-warning" onclick="limparAssinatura('administrador')">
                            <i class="bi bi-eraser me-1"></i> Limpar
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalAssinatura('administrador')">
                            <i class="bi bi-pencil me-1"></i> Assinar em Tela Cheia
                        </button>
                    </div>
                </div>
                <input type="hidden" name="assinatura_administrador" id="assinatura_administrador" 
                       value="<?php echo htmlspecialchars($assinatura['assinatura_administrador'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <!-- Doador -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-heart me-2"></i>
            Dados do Doador
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome_doador" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_doador" name="nome_doador" 
                           value="<?php echo htmlspecialchars($assinatura['nome_doador'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="cpf_doador" class="form-label">CPF</label>
                    <input type="text" class="form-control" id="cpf_doador" name="cpf_doador" 
                           value="<?php echo htmlspecialchars($assinatura['cpf_doador'] ?? ''); ?>" 
                           placeholder="000.000.000-00">
                </div>
                <div class="col-md-6">
                    <label for="rg_doador" class="form-label">RG</label>
                    <input type="text" class="form-control" id="rg_doador" name="rg_doador" 
                           value="<?php echo htmlspecialchars($assinatura['rg_doador'] ?? ''); ?>">
                </div>
                <div class="col-md-12">
                    <label for="endereco_doador" class="form-label">Endereço</label>
                    <textarea class="form-control" id="endereco_doador" name="endereco_doador" 
                              rows="2"><?php echo htmlspecialchars($assinatura['endereco_doador'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Assinatura <span class="text-danger">*</span></label>
                <div class="signature-canvas-container">
                    <canvas class="signature-canvas" id="canvas_doador" width="800" height="200"></canvas>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-warning" onclick="limparAssinatura('doador')">
                            <i class="bi bi-eraser me-1"></i> Limpar
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalAssinatura('doador')">
                            <i class="bi bi-pencil me-1"></i> Assinar em Tela Cheia
                        </button>
                    </div>
                </div>
                <input type="hidden" name="assinatura_doador" id="assinatura_doador" 
                       value="<?php echo htmlspecialchars($assinatura['assinatura_doador'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <!-- Cônjuge -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person me-2"></i>
            Dados do Cônjuge (Opcional)
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome_conjuge" class="form-label">Nome</label>
                    <input type="text" class="form-control" id="nome_conjuge" name="nome_conjuge" 
                           value="<?php echo htmlspecialchars($assinatura['nome_conjuge'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="cpf_conjuge" class="form-label">CPF</label>
                    <input type="text" class="form-control" id="cpf_conjuge" name="cpf_conjuge" 
                           value="<?php echo htmlspecialchars($assinatura['cpf_conjuge'] ?? ''); ?>" 
                           placeholder="000.000.000-00">
                </div>
                <div class="col-md-6">
                    <label for="rg_conjuge" class="form-label">RG</label>
                    <input type="text" class="form-control" id="rg_conjuge" name="rg_conjuge" 
                           value="<?php echo htmlspecialchars($assinatura['rg_conjuge'] ?? ''); ?>">
                </div>
                <div class="col-md-12">
                    <label for="endereco_conjuge" class="form-label">Endereço</label>
                    <textarea class="form-control" id="endereco_conjuge" name="endereco_conjuge" 
                              rows="2"><?php echo htmlspecialchars($assinatura['endereco_conjuge'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Assinatura</label>
                <div class="signature-canvas-container">
                    <canvas class="signature-canvas" id="canvas_conjuge" width="800" height="200"></canvas>
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-warning" onclick="limparAssinatura('conjuge')">
                            <i class="bi bi-eraser me-1"></i> Limpar
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="abrirModalAssinatura('conjuge')">
                            <i class="bi bi-pencil me-1"></i> Assinar em Tela Cheia
                        </button>
                    </div>
                </div>
                <input type="hidden" name="assinatura_conjuge" id="assinatura_conjuge" 
                       value="<?php echo htmlspecialchars($assinatura['assinatura_conjuge'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success btn-lg w-100">
        <i class="bi bi-check-circle me-2"></i>
        Salvar Assinaturas
    </button>
</form>

<!-- Modal para assinatura em tela cheia -->
<div id="modalAssinatura" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:9999;">
    <div style="position:relative; width:100%; height:100%; display:flex; flex-direction:column; padding:12px;">
        <div class="d-flex justify-content-between mb-2">
            <button type="button" class="btn btn-warning" onclick="limparModalAssinatura()">
                <i class="bi bi-eraser me-1"></i> Limpar
            </button>
            <div>
                <button type="button" class="btn btn-success" onclick="salvarModalAssinatura()">
                    <i class="bi bi-check me-1"></i> Salvar
                </button>
                <button type="button" class="btn btn-danger" onclick="fecharModalAssinatura()">
                    <i class="bi bi-x me-1"></i> Fechar
                </button>
            </div>
        </div>
        <div style="flex:1; display:flex; align-items:center; justify-content:center; overflow:auto;">
            <canvas id="modal_canvas" style="background:#fff; border:1px solid #ddd; max-width:95%; max-height:95%;"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
// Inicializar signature pads
const signaturePads = {};
let currentModal = null;
let modalSignaturePad = null;

function initCanvas(id) {
    const canvas = document.getElementById('canvas_' + id);
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 0)'
    });
    signaturePads[id] = signaturePad;
    
    // Carregar assinatura existente
    const hidden = document.getElementById('assinatura_' + id);
    if (hidden.value) {
        const img = new Image();
        img.onload = function() {
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        };
        img.src = hidden.value;
    }
    
    // Redimensionar canvas
    resizeCanvas(canvas);
}

function resizeCanvas(canvas) {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
}

function limparAssinatura(id) {
    signaturePads[id].clear();
    document.getElementById('assinatura_' + id).value = '';
}

function abrirModalAssinatura(id) {
    currentModal = id;
    const modal = document.getElementById('modalAssinatura');
    modal.style.display = 'flex';
    
    const canvas = document.getElementById('modal_canvas');
    canvas.width = window.innerWidth * 0.9;
    canvas.height = window.innerHeight * 0.7;
    
    modalSignaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: 'rgb(0, 0, 0)'
    });
    
    // Carregar assinatura atual
    const hidden = document.getElementById('assinatura_' + id);
    if (hidden.value) {
        const img = new Image();
        img.onload = function() {
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        };
        img.src = hidden.value;
    }
}

function limparModalAssinatura() {
    if (modalSignaturePad) modalSignaturePad.clear();
}

function salvarModalAssinatura() {
    if (!modalSignaturePad || modalSignaturePad.isEmpty()) {
        alert('Por favor, faça uma assinatura antes de salvar.');
        return;
    }
    
    const dataURL = modalSignaturePad.toDataURL();
    
    // Salvar no canvas pequeno
    const smallCanvas = document.getElementById('canvas_' + currentModal);
    const ctx = smallCanvas.getContext('2d');
    const img = new Image();
    img.onload = function() {
        ctx.clearRect(0, 0, smallCanvas.width, smallCanvas.height);
        ctx.drawImage(img, 0, 0, smallCanvas.width, smallCanvas.height);
    };
    img.src = dataURL;
    
    // Salvar no campo hidden
    document.getElementById('assinatura_' + currentModal).value = dataURL;
    
    fecharModalAssinatura();
}

function fecharModalAssinatura() {
    document.getElementById('modalAssinatura').style.display = 'none';
    if (modalSignaturePad) {
        modalSignaturePad.off();
        modalSignaturePad = null;
    }
    currentModal = null;
}

function copiarLink() {
    const input = document.getElementById('linkCompartilhar');
    input.select();
    input.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(input.value).then(() => {
        alert('Link copiado para a área de transferência!');
    }).catch(() => {
        document.execCommand('copy');
        alert('Link copiado!');
    });
}

// Validação do formulário
document.getElementById('formAssinatura').addEventListener('submit', function(e) {
    // Salvar assinaturas nos campos hidden
    ['administrador', 'doador', 'conjuge'].forEach(id => {
        if (!signaturePads[id].isEmpty()) {
            document.getElementById('assinatura_' + id).value = signaturePads[id].toDataURL();
        }
    });
    
    // Validar assinaturas obrigatórias
    if (!document.getElementById('assinatura_administrador').value) {
        e.preventDefault();
        alert('A assinatura do Administrador/Acessor é obrigatória!');
        return false;
    }
    
    if (!document.getElementById('assinatura_doador').value) {
        e.preventDefault();
        alert('A assinatura do Doador é obrigatória!');
        return false;
    }
});

// Inicializar ao carregar
document.addEventListener('DOMContentLoaded', function() {
    initCanvas('administrador');
    initCanvas('doador');
    initCanvas('conjuge');
});
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinatura_form_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
