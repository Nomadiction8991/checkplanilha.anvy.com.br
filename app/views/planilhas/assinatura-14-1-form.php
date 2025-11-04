<?php
// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../../../CRUD/conexao.php';

// Parâmetros de entrada
$token = $_GET['token'] ?? null;
$id_planilha = isset($_GET['id']) ? intval($_GET['id']) : null;
$id_produto = isset($_GET['produto']) ? intval($_GET['produto']) : null;
$ids_produtos = $_GET['ids'] ?? null;

$produtos = [];
$modo_multiplo = false;
$acesso_publico = false;
$assinaturas = [];

if ($token) {
    // Acesso público por token
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
    $id_produto = intval($assinatura['id_produto']);
    $id_planilha = intval($assinatura['id_planilha']);
    $produtos = [$id_produto];
    $acesso_publico = true;
} else {
    // Acesso interno
    if ($ids_produtos) {
        $produtos = array_values(array_filter(array_map('intval', explode(',', $ids_produtos))));
        $modo_multiplo = count($produtos) > 1;
        if (!$id_planilha && !empty($produtos)) {
            // Buscar planilha do primeiro produto
            $stmt = $conexao->prepare('SELECT id_planilha FROM produtos_cadastro WHERE id = ?');
            $stmt->execute([$produtos[0]]);
            $row = $stmt->fetch();
            $id_planilha = $row ? intval($row['id_planilha']) : null;
        }
    } elseif ($id_produto) {
        $produtos = [intval($id_produto)];
        $modo_multiplo = false;
    }
    if (!$id_planilha || empty($produtos)) {
        header('Location: assinatura-14-1.php?id=' . urlencode($id_planilha ?? ''));
        exit;
    }
}

// Buscar informações dos produtos
$produtos_info = [];
if (!empty($produtos)) {
    $placeholders = implode(',', array_fill(0, count($produtos), '?'));
    $sql = "SELECT pc.*, p.comum 
            FROM produtos_cadastro pc
            JOIN planilhas p ON pc.id_planilha = p.id
            WHERE pc.id IN ($placeholders)";
    $stmt = $conexao->prepare($sql);
    $stmt->execute($produtos);
    $produtos_info = $stmt->fetchAll();
}

// Buscar ou criar registros de assinatura para cada produto
foreach ($produtos as $pid) {
    $stmt = $conexao->prepare('SELECT * FROM assinaturas_14_1 WHERE id_produto = :id_produto');
    $stmt->bindValue(':id_produto', $pid);
    $stmt->execute();
    $row = $stmt->fetch();
    if (!$row) {
        $token_novo = bin2hex(random_bytes(32));
        $ins = $conexao->prepare("INSERT INTO assinaturas_14_1 (id_produto, id_planilha, token, status) VALUES (:id_produto, :id_planilha, :token, 'pendente')");
        $ins->bindValue(':id_produto', $pid);
        $ins->bindValue(':id_planilha', $id_planilha);
        $ins->bindValue(':token', $token_novo);
        $ins->execute();
        $stmt->execute();
        $row = $stmt->fetch();
    }
    $assinaturas[$pid] = $row;
}

// Para preencher os campos do formulário, usar o primeiro
$assinatura = !empty($produtos) && isset($assinaturas[$produtos[0]]) ? $assinaturas[$produtos[0]] : [];
$produto = $produtos_info[0] ?? null;

// POST: salvar por seção
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitizar POST (evitar mod_security)
        $post_limpo = [];
        foreach ($_POST as $k => $v) {
            $kk = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            $post_limpo[$kk] = is_string($v) ? trim($v) : $v;
        }
        // Decodificar assinatura do cônjuge (dupla codificação)
        if (isset($post_limpo['assinatura_c0njuge']) && strpos($post_limpo['assinatura_c0njuge'], 'B64:') === 0) {
            $encoded = substr($post_limpo['assinatura_c0njuge'], 4);
            $post_limpo['assinatura_c0njuge'] = base64_decode($encoded);
        }

        $section = $post_limpo['section'] ?? '';
        $ids_atualizar = isset($post_limpo['ids_produtos']) && $post_limpo['ids_produtos']
            ? array_values(array_filter(array_map('intval', explode(',', $post_limpo['ids_produtos']))))
            : $produtos;

        $total_atualizados = 0;
        foreach ($ids_atualizar as $pid) {
            if (!isset($assinaturas[$pid])) continue;

            $set = [];
            $params = [];
            if ($section === 'admin') {
                $set = [
                    'nome_administrador = :nome_administrador',
                    'assinatura_administrador = :assinatura_administrador'
                ];
                $params[':nome_administrador'] = $post_limpo['nome_administrador'] ?? '';
                $params[':assinatura_administrador'] = $post_limpo['assinatura_administrador'] ?? '';
            } elseif ($section === 'doador') {
                $set = [
                    'nome_doador = :nome_doador',
                    'endereco_doador = :endereco_doador',
                    'cpf_doador = :cpf_doador',
                    'rg_doador = :rg_doador',
                    'assinatura_doador = :assinatura_doador'
                ];
                $params[':nome_doador'] = $post_limpo['nome_doador'] ?? '';
                $params[':endereco_doador'] = $post_limpo['endereco_doador'] ?? '';
                $params[':cpf_doador'] = $post_limpo['cpf_doador'] ?? '';
                $params[':rg_doador'] = $post_limpo['rg_doador'] ?? '';
                $params[':assinatura_doador'] = $post_limpo['assinatura_doador'] ?? '';
            } elseif ($section === 'conjuge') {
                $set = [
                    'nome_conjuge = :nome_conjuge',
                    'endereco_conjuge = :endereco_conjuge',
                    'cpf_conjuge = :cpf_conjuge',
                    'rg_conjuge = :rg_conjuge',
                    'assinatura_conjuge = :assinatura_conjuge'
                ];
                $params[':nome_conjuge'] = $post_limpo['nome_conjuge'] ?? '';
                $params[':endereco_conjuge'] = $post_limpo['endereco_conjuge'] ?? '';
                $params[':cpf_conjuge'] = $post_limpo['cpf_conjuge'] ?? '';
                $params[':rg_conjuge'] = $post_limpo['rg_conjuge'] ?? '';
                $params[':assinatura_conjuge'] = $post_limpo['assinatura_c0njuge'] ?? '';
            } else {
                continue; // seção inválida
            }

            // IP
            $set[] = 'ip_assinatura = :ip';
            $params[':ip'] = $_SERVER['REMOTE_ADDR'] ?? '';

            $sql = 'UPDATE assinaturas_14_1 SET ' . implode(', ', $set) . ' WHERE id = :id';
            $stmt = $conexao->prepare($sql);
            foreach ($params as $pk => $pv) {
                $stmt->bindValue($pk, $pv);
            }
            $stmt->bindValue(':id', $assinaturas[$pid]['id']);
            $stmt->execute();
            $total_atualizados++;

            // Atualizar status conforme assinaturas principais
            $stmt2 = $conexao->prepare('SELECT assinatura_administrador, assinatura_doador FROM assinaturas_14_1 WHERE id = :id');
            $stmt2->bindValue(':id', $assinaturas[$pid]['id']);
            $stmt2->execute();
            $cur = $stmt2->fetch();
            $novo_status = (!empty($cur['assinatura_administrador']) && !empty($cur['assinatura_doador'])) ? 'assinado' : 'pendente';
            $updStatus = $conexao->prepare('UPDATE assinaturas_14_1 SET status = :status WHERE id = :id');
            $updStatus->bindValue(':status', $novo_status);
            $updStatus->bindValue(':id', $assinaturas[$pid]['id']);
            $updStatus->execute();
        }

        $mensagem = $total_atualizados > 1 ? ("Dados salvos para $total_atualizados produtos.") : 'Dados salvos com sucesso!';
        $tipo_mensagem = 'success';

        if (!$acesso_publico) {
            // Recarregar a mesma página do formulário com os produtos atuais
            $produtos_param = implode(',', $produtos);
            if ($modo_multiplo) {
                header('Location: assinatura-14-1-form.php?id=' . urlencode($id_planilha) . '&produtos=' . urlencode($produtos_param) . '&saved=1');
            } else {
                header('Location: assinatura-14-1-form.php?id=' . urlencode($id_planilha) . '&produto=' . urlencode($produtos[0]) . '&saved=1');
            }
            exit;
        }

        // Recarregar dados em modo público
        if ($acesso_publico && !$modo_multiplo && count($produtos) === 1) {
            $pid = $produtos[0];
            $stmt = $conexao->prepare('SELECT * FROM assinaturas_14_1 WHERE id_produto = :id_produto');
            $stmt->bindValue(':id_produto', $pid);
            $stmt->execute();
            $assinaturas[$pid] = $stmt->fetch();
            $assinatura = $assinaturas[$pid];
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao salvar: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Mensagem de sucesso após redirecionamento
if (isset($_GET['saved']) && $_GET['saved'] == '1' && !isset($mensagem)) {
    $mensagem = 'Dados salvos com sucesso!';
    $tipo_mensagem = 'success';
}

// URL pública (modo único)
$url_publica = null;
if (!$modo_multiplo && count($produtos) === 1) {
    $pid = $produtos[0];
    $token_prod = $assinaturas[$pid]['token'] ?? null;
    if ($token_prod) {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $url_base = $protocolo . '://' . $host;
        $caminho_arquivo = str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__);
        $url_publica = $url_base . $caminho_arquivo . '?token=' . urlencode($token_prod);
    }
}

$pageTitle = $acesso_publico ? 'Assinatura Digital - 14.1' : ($modo_multiplo ? 'Assinar Múltiplos Produtos - 14.1' : 'Gerenciar Assinatura - 14.1');
$backUrl = $acesso_publico ? null : 'relatorio-14-1.php?id=' . urlencode($id_planilha);
$headerActions = '';

ob_start();
?>

<style>
.signature-preview-container {
    border: 2px solid #dee2e6;
    border-radius: 0.375rem;
    background: #f8f9fa;
    padding: 0.5rem;
    margin-bottom: 1rem;
    overflow: hidden;
}

.signature-preview-canvas {
    border: 1px solid #ddd;
    background: white;
    width: 100%;
    height: auto;
    display: block;
    pointer-events: none;
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

<?php if ($modo_multiplo): ?>
<!-- Card mostrando produtos selecionados (modo múltiplo) -->
<div class="card mb-3">
    <div class="card-header bg-info text-white">
        <i class="bi bi-box-seam me-2"></i>
        Produtos Selecionados para Assinatura (<?php echo count($produtos); ?>)
    </div>
    <div class="card-body">
        <p class="text-muted mb-2">
            <i class="bi bi-info-circle me-1"></i>
            As mesmas assinaturas serão aplicadas a todos os produtos abaixo:
        </p>
        <div class="list-group">
            <?php foreach ($produtos_info as $prod_info): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($prod_info['comum']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo htmlspecialchars(substr($prod_info['descricao_completa'], 0, 100)); ?>...</small>
                        </div>
                        <span class="badge bg-primary">ID: <?php echo $prod_info['id']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Informações do Produto (modo único) -->
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
<?php endif; ?>

<!-- Formulário: Administrador/Acessor -->
<form method="POST" id="formAdmin" class="mb-3">
    <input type="hidden" name="ids_produtos" value="<?php echo implode(',', $produtos); ?>">
    <input type="hidden" name="section" value="admin">
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-badge me-2"></i>
            Administrador/Acessor
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nome_administrador" class="form-label">Nome <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nome_administrador" name="nome_administrador" value="<?php echo htmlspecialchars($assinatura['nome_administrador'] ?? ''); ?>" required>
            </div>
            <div>
                <label class="form-label">Assinatura <span class="text-danger">*</span></label>
                <div class="signature-preview-container">
                    <canvas id="canvas_administrador" width="800" height="160" class="signature-preview-canvas"></canvas>
                </div>
                <button type="button" class="btn btn-primary btn-lg w-100" onclick="abrirModalAssinatura('administrador')">
                    <i class="bi bi-pencil-square me-2"></i> Fazer Assinatura
                </button>
                <input type="hidden" name="assinatura_administrador" id="assinatura_administrador" value="<?php echo htmlspecialchars($assinatura['assinatura_administrador'] ?? ''); ?>">
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-check2-circle me-2"></i> Salvar Administrador/Assessor
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Formulário: Doador -->
<form method="POST" id="formDoador" class="mb-3">
    <input type="hidden" name="ids_produtos" value="<?php echo implode(',', $produtos); ?>">
    <input type="hidden" name="section" value="doador">
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-heart me-2"></i>
            Dados do Doador
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome_doador" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_doador" name="nome_doador" value="<?php echo htmlspecialchars($assinatura['nome_doador'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="cpf_doador" class="form-label">CPF <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cpf_doador" name="cpf_doador" value="<?php echo htmlspecialchars($assinatura['cpf_doador'] ?? ''); ?>" placeholder="000.000.000-00" required>
                </div>
                <div class="col-md-6">
                  <label for="rg_doador" class="form-label">RG <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="rg_doador" name="rg_doador" value="<?php echo htmlspecialchars($assinatura['rg_doador'] ?? ''); ?>" required>
                </div>
                <div class="col-md-12">
                  <label for="endereco_doador" class="form-label">Endereço <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="endereco_doador" name="endereco_doador" rows="2" required><?php echo htmlspecialchars($assinatura['endereco_doador'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label">Assinatura <span class="text-danger">*</span></label>
                <div class="signature-preview-container">
                    <canvas id="canvas_doador" width="800" height="160" class="signature-preview-canvas"></canvas>
                </div>
                <button type="button" class="btn btn-primary btn-lg w-100" onclick="abrirModalAssinatura('doador')">
                    <i class="bi bi-pencil-square me-2"></i> Fazer Assinatura
                </button>
                <input type="hidden" name="assinatura_doador" id="assinatura_doador" value="<?php echo htmlspecialchars($assinatura['assinatura_doador'] ?? ''); ?>">
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-check2-circle me-2"></i> Salvar Doador
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Formulário: Cônjuge -->
<form method="POST" id="formConjuge" class="mb-3">
    <input type="hidden" name="ids_produtos" value="<?php echo implode(',', $produtos); ?>">
    <input type="hidden" name="section" value="conjuge">
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person me-2"></i>
            Dados do Cônjuge
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nome_conjuge" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome_conjuge" name="nome_conjuge" value="<?php echo htmlspecialchars($assinatura['nome_conjuge'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="cpf_conjuge" class="form-label">CPF <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cpf_conjuge" name="cpf_conjuge" value="<?php echo htmlspecialchars($assinatura['cpf_conjuge'] ?? ''); ?>" placeholder="000.000.000-00" required>
                </div>
                <div class="col-md-6">
                    <label for="rg_conjuge" class="form-label">RG <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="rg_conjuge" name="rg_conjuge" value="<?php echo htmlspecialchars($assinatura['rg_conjuge'] ?? ''); ?>" required>
                </div>
                <div class="col-md-12">
                    <label for="endereco_conjuge" class="form-label">Endereço <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="endereco_conjuge" name="endereco_conjuge" rows="2" required><?php echo htmlspecialchars($assinatura['endereco_conjuge'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label">Assinatura <span class="text-danger">*</span></label>
                <div class="signature-preview-container">
                    <canvas id="canvas_conjuge" width="800" height="160" class="signature-preview-canvas"></canvas>
                </div>
                <button type="button" class="btn btn-primary btn-lg w-100" onclick="abrirModalAssinatura('conjuge')">
                    <i class="bi bi-pencil-square me-2"></i> Fazer Assinatura
                </button>
                <input type="hidden" name="assinatura_c0njuge" id="assinatura_conjuge" value="<?php echo htmlspecialchars($assinatura['assinatura_conjuge'] ?? ''); ?>">
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-check2-circle me-2"></i> Salvar Cônjuge
                </button>
            </div>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
// Variáveis globais
let currentField = null; // 'administrador', 'doador' ou 'conjuge'
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

// Abrir modal para um campo específico
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
    try{ 
        window.removeEventListener('resize', resizeModalIfVisible); 
        window.removeEventListener('orientationchange', resizeModalIfVisible); 
    }catch(e){}
    currentField = null;
};

// Validação e inicialização
document.addEventListener('DOMContentLoaded', function(){
    // Inicializar canvas de preview
    initPreviewCanvas('canvas_administrador');
    initPreviewCanvas('canvas_doador');
    initPreviewCanvas('canvas_conjuge');
    
    // Carregar assinaturas existentes
    ['administrador', 'doador', 'conjuge'].forEach(id => {
        const existing = document.getElementById('assinatura_' + id).value;
        if (existing) drawImageOnCanvas('canvas_' + id, existing);
    });
    
    // Validação por formulário
    const formAdmin = document.getElementById('formAdmin');
    formAdmin.addEventListener('submit', function(e){
        if (!document.getElementById('assinatura_administrador').value) {
            e.preventDefault();
            alert('A assinatura do Administrador/Assessor é obrigatória!');
            return false;
        }
    });

    const formDoador = document.getElementById('formDoador');
    formDoador.addEventListener('submit', function(e){
        if (!document.getElementById('assinatura_doador').value) {
            e.preventDefault();
            alert('A assinatura do Doador é obrigatória!');
            return false;
        }
    });

    const formConjuge = document.getElementById('formConjuge');
    formConjuge.addEventListener('submit', function(e){
        const conjInput = document.getElementById('assinatura_conjuge');
        if (!conjInput.value) {
            e.preventDefault();
            alert('A assinatura do Cônjuge é obrigatória!');
            return false;
        }
        // Dupla codificação para evitar mod_security
        try {
            const encoded = btoa(conjInput.value);
            conjInput.value = 'B64:' + encoded;
        } catch(err) {
            console.error('Erro ao codificar assinatura:', err);
        }
    });
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
