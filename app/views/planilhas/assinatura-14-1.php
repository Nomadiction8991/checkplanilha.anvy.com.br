<?php
require_once __DIR__ . '/../../../CRUD/conexao.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../../../index.php');
    exit;
}

// Buscar produtos que podem ser assinados (imprimir_14_1 = 1)
$sql = "SELECT 
            pc.id,
            pc.descricao_completa,
            pc.tipo_ben,
            tb.descricao as tipo_descricao,
            COALESCE(a.status, 'pendente') as status_assinatura,
            a.token,
            a.id as id_assinatura
        FROM produtos_cadastro pc
        LEFT JOIN tipos_bens tb ON pc.id_tipo_ben = tb.id
        LEFT JOIN assinaturas_14_1 a ON a.id_produto = pc.id
        WHERE pc.id_planilha = :id_planilha 
        AND pc.imprimir_14_1 = 1
        ORDER BY pc.id ASC";

$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_planilha', $id_planilha);
$stmt->execute();
$produtos = $stmt->fetchAll();

$pageTitle = 'Assinar Documentos 14.1';
$backUrl = '../shared/menu-unificado.php?id=' . urlencode($id_planilha) . '&contexto=relatorio';
$headerActions = '
    <a href="../shared/menu-unificado.php?id=' . urlencode($id_planilha) . '&contexto=relatorio" class="btn-header-action" title="Menu">
        <i class="bi bi-list fs-5"></i>
    </a>
';

ob_start();
?>

<style>
.produto-card {
    transition: all 0.2s;
    border-left: 4px solid transparent;
    cursor: pointer;
}

.produto-card:hover {
    border-left-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.produto-card.status-pendente {
    border-left-color: #ffc107;
}

.produto-card.status-assinado {
    border-left-color: #28a745;
}

.produto-card.status-cancelado {
    border-left-color: #dc3545;
}

.produto-card.selected {
    background-color: #e7f3ff;
    border-left-color: #007bff !important;
    box-shadow: 0 0 0 2px #007bff;
}

.badge-status {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
}

.checkbox-produto {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
}

.selection-toolbar {
    position: sticky;
    top: 60px;
    z-index: 100;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    display: none;
}

.selection-toolbar.active {
    display: block;
}
</style>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-pen me-2"></i>
        Selecione os Produtos para Assinar
    </div>
    <div class="card-body">
        <p class="mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Clique no produto para selecioná-lo. Você pode selecionar vários produtos para assinar todos de uma vez.
        </p>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="selecionarTodos()">
                <i class="bi bi-check-square me-1"></i>
                Selecionar Todos
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limparSelecao()">
                <i class="bi bi-square me-1"></i>
                Limpar Seleção
            </button>
        </div>
    </div>
</div>

<!-- Barra de ferramentas flutuante -->
<div class="selection-toolbar" id="selectionToolbar">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><span id="countSelected">0</span> produto(s) selecionado(s)</strong>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" onclick="assinarSelecionados()">
                <i class="bi bi-pen-fill me-1"></i>
                Assinar Selecionados
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="limparSelecao()">
                <i class="bi bi-x me-1"></i>
                Cancelar
            </button>
        </div>
    </div>
</div>

<?php if (count($produtos) > 0): ?>
    <div class="row g-3">
        <?php foreach ($produtos as $produto): ?>
            <div class="col-12">
                <div class="card produto-card status-<?php echo $produto['status_assinatura']; ?>" 
                     data-produto-id="<?php echo $produto['id']; ?>"
                     onclick="toggleProduto(<?php echo $produto['id']; ?>)">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-start gap-3 flex-grow-1">
                                <div class="form-check">
                                    <input class="form-check-input checkbox-produto" 
                                           type="checkbox" 
                                           id="produto_<?php echo $produto['id']; ?>"
                                           value="<?php echo $produto['id']; ?>"
                                           onclick="event.stopPropagation();">
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-2">
                                        <i class="bi bi-box-seam me-1"></i>
                                        Produto #<?php echo $produto['id']; ?>
                                    </h6>
                                    <p class="card-text small mb-2">
                                        <strong>Tipo:</strong> 
                                        <?php echo htmlspecialchars($produto['tipo_descricao'] ?? 'N/A'); ?>
                                    </p>
                                    <p class="card-text small text-muted mb-0" style="max-height: 3em; overflow: hidden;">
                                        <?php echo htmlspecialchars(substr($produto['descricao_completa'], 0, 150)); ?>
                                        <?php if (strlen($produto['descricao_completa']) > 150): ?>...<?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="ms-3">
                                <?php
                                $statusConfig = [
                                    'pendente' => ['badge' => 'warning', 'icon' => 'clock', 'text' => 'Pendente'],
                                    'assinado' => ['badge' => 'success', 'icon' => 'check-circle-fill', 'text' => 'Assinado'],
                                    'cancelado' => ['badge' => 'danger', 'icon' => 'x-circle', 'text' => 'Cancelado']
                                ];
                                $config = $statusConfig[$produto['status_assinatura']] ?? $statusConfig['pendente'];
                                ?>
                                <span class="badge bg-<?php echo $config['badge']; ?> badge-status">
                                    <i class="bi bi-<?php echo $config['icon']; ?> me-1"></i>
                                    <?php echo $config['text']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($produto['token']): ?>
                            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-link-45deg me-1"></i>
                                    Link de assinatura disponível
                                </small>
                                <a href="./assinatura-14-1-form.php?id_produto=<?php echo $produto['id']; ?>&id_planilha=<?php echo $id_planilha; ?>" 
                                   class="btn btn-sm btn-outline-primary"
                                   onclick="event.stopPropagation();">
                                    <i class="bi bi-pencil me-1"></i>
                                    Editar Individual
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <h5 class="text-muted">Nenhum produto para assinar</h5>
            <p class="text-muted small mb-0">
                Certifique-se de que existem produtos marcados para impressão no relatório 14.1
            </p>
        </div>
    </div>
<?php endif; ?>

<script>
let produtosSelecionados = new Set();

function toggleProduto(id) {
    const checkbox = document.getElementById('produto_' + id);
    const card = document.querySelector(`[data-produto-id="${id}"]`);
    
    if (checkbox.checked) {
        checkbox.checked = false;
        produtosSelecionados.delete(id);
        card.classList.remove('selected');
    } else {
        checkbox.checked = true;
        produtosSelecionados.add(id);
        card.classList.add('selected');
    }
    
    atualizarToolbar();
}

function atualizarToolbar() {
    const toolbar = document.getElementById('selectionToolbar');
    const counter = document.getElementById('countSelected');
    const count = produtosSelecionados.size;
    
    counter.textContent = count;
    
    if (count > 0) {
        toolbar.classList.add('active');
    } else {
        toolbar.classList.remove('active');
    }
}

function selecionarTodos() {
    const checkboxes = document.querySelectorAll('.checkbox-produto');
    checkboxes.forEach(cb => {
        const id = parseInt(cb.value);
        cb.checked = true;
        produtosSelecionados.add(id);
        const card = document.querySelector(`[data-produto-id="${id}"]`);
        if (card) card.classList.add('selected');
    });
    atualizarToolbar();
}

function limparSelecao() {
    const checkboxes = document.querySelectorAll('.checkbox-produto');
    checkboxes.forEach(cb => {
        cb.checked = false;
        const card = document.querySelector(`[data-produto-id="${cb.value}"]`);
        if (card) card.classList.remove('selected');
    });
    produtosSelecionados.clear();
    atualizarToolbar();
}

function assinarSelecionados() {
    if (produtosSelecionados.size === 0) {
        alert('Selecione pelo menos um produto para assinar.');
        return;
    }
    
    const ids = Array.from(produtosSelecionados).join(',');
    window.location.href = './assinatura-14-1-form.php?ids=' + ids + '&id_planilha=<?php echo $id_planilha; ?>';
}

// Listener nos checkboxes para sincronizar com a seleção
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.checkbox-produto');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function(e) {
            const id = parseInt(this.value);
            const card = document.querySelector(`[data-produto-id="${id}"]`);
            
            if (this.checked) {
                produtosSelecionados.add(id);
                if (card) card.classList.add('selected');
            } else {
                produtosSelecionados.delete(id);
                if (card) card.classList.remove('selected');
            }
            
            atualizarToolbar();
        });
    });
});
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinatura_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
