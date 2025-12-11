<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AutenticaÃ§Ã£o

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../../../index.php');
    exit;
}

// Buscar produtos que podem ser assinados (imprimir_14_1 = 1)
$sql = "SELECT 
            p.id_produto as id,
            p.descricao_completa,
            p.bem as tipo_ben,
            tb.descricao as tipo_descricao,
            COALESCE(a.status, 'pendente') as status_assinatura,
            a.token,
            a.id as id_assinatura
        FROM produtos p
        LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
        LEFT JOIN assinaturas_14_1 a ON a.id_produto = p.id_produto
        WHERE p.planilha_id = :id_planilha 
        AND p.imprimir_14_1 = 1
        ORDER BY p.id_produto ASC";

$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_planilha', $id_planilha);
$stmt->execute();
$produtos = $stmt->fetchAll();

$pageTitle = 'Assinar Documentos 14.1';
$backUrl = '../planilhas/relatorio-14-1.php?id=' . urlencode($id_planilha);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuAssinatura" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuAssinatura">
            <li>
                <a class="dropdown-item" href="../../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

// Gerar URL de compartilhamento desta pÃ¡gina (inclui parÃ¢metros atuais)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$uri  = $_SERVER['REQUEST_URI'] ?? ('/app/views/planilhas/assinatura-14-1.php?id=' . urlencode($id_planilha));
$url_compartilhar = $scheme . '://' . $host . $uri;

ob_start();
?>

<style>
.produto-card {
    transition: all 0.2s;
    border: 3px solid #dee2e6;
    border-radius: 0.375rem;
    cursor: pointer;
}

.produto-card:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.produto-card.status-pendente {
    border-color: #ffc107;
}

.produto-card.status-assinado {
    border-color: #28a745;
}

.produto-card.selected {
    background-color: #e7f3ff;
    border-color: #007bff !important;
    box-shadow: 0 0 0 2px #007bff;
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
 .legenda-status {
     display: flex;
     gap: 1.5rem;
     flex-wrap: wrap;
     align-items: center;
 }
 
 .legenda-item {
     display: flex;
     align-items: center;
     gap: 0.5rem;
     font-size: 0.875rem;
 }
 
 .legenda-cor {
     width: 30px;
     height: 20px;
     border-radius: 3px;
     border: 3px solid;
 }
 
 .legenda-cor.pendente {
     border-color: #ffc107;
 }
 
 .legenda-cor.assinado {
     border-color: #28a745;
 }
</style>

<!-- Card com Link de Compartilhamento da PÃ¡gina -->
<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-share me-2"></i>
        Link para Compartilhamento desta PÃ¡gina
    </div>
    <div class="card-body">
        <p class="mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Envie este link para a pessoa que vai assinar os documentos desta planilha.
        </p>
        <div class="input-group">
            <input type="text" class="form-control" id="linkCompartilharSelecao" value="<?php echo htmlspecialchars($url_compartilhar); ?>" readonly>
            <button class="btn btn-primary" type="button" onclick="copiarLinkSelecao()">
                <i class="bi bi-clipboard me-1"></i>
                Copiar
            </button>
        </div>
        <small class="text-muted d-block mt-2">
            <i class="bi bi-shield-check me-1"></i>
            O link abre esta pÃ¡gina para seleÃ§Ã£o e assinatura.
        </small>
    </div>
    
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-pen me-2"></i>
        Selecione os Produtos para Assinar
    </div>
    <div class="card-body">
        <p class="mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Clique no produto para selecionÃ¡-lo. VocÃª pode selecionar vÃ¡rios produtos para assinar todos de uma vez.
        </p>
        <!-- Legenda de Status -->
        <div class="legenda-status">
            <div class="legenda-item">
                <div class="legenda-cor pendente"></div>
                <span>Pendente</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor assinado"></div>
                <span>Assinado</span>
            </div>
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
                                <!-- EspaÃ§o reservado para conteÃºdo adicional se necessÃ¡rio -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="bi bi-info-circle me-2"></i> Nenhum produto encontrado para assinatura.
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

// Listener nos checkboxes para sincronizar com a seleÃ§Ã£o
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

function copiarLinkSelecao() {
    const input = document.getElementById('linkCompartilharSelecao');
    input.select();
    input.setSelectionRange(0, 99999);
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(input.value).then(() => {
            alert('Link copiado!');
        }).catch(() => {
            document.execCommand('copy');
            alert('Link copiado!');
        });
    } else {
        document.execCommand('copy');
        alert('Link copiado!');
    }
}
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinatura_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>

