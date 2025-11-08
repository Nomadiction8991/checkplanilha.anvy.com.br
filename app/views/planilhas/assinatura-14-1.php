<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/conexao.php';
// Config central de URL base
require_once __DIR__ . '/../../../config.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../../../index.php');
    exit;
}

// Buscar produtos que podem ser assinados (imprimir_14_1 = 1)
$sql = "SELECT 
            p.id_produto,
            p.descricao_completa,
            p.tipo_bem_id,
            p.condicao_14_1,
            p.doador_conjugue_id,
            tb.descricao as tipo_descricao,
            u.nome as doador_nome
        FROM produtos p
        LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
        LEFT JOIN usuarios u ON p.doador_conjugue_id = u.id
        WHERE p.planilha_id = :id_planilha 
        AND p.imprimir_14_1 = 1
        ORDER BY p.id_produto ASC";

$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_planilha', $id_planilha);
$stmt->execute();
$produtos = $stmt->fetchAll();

// Calcular estatísticas
$total_produtos = count($produtos);
$produtos_assinados = 0;
$doacoes_por_pessoa = [];

foreach ($produtos as $produto) {
    if (!empty($produto['doador_conjugue_id'])) {
        $produtos_assinados++;
        $nome_doador = $produto['doador_nome'] ?? 'Sem nome';
        if (!isset($doacoes_por_pessoa[$nome_doador])) {
            $doacoes_por_pessoa[$nome_doador] = 0;
        }
        $doacoes_por_pessoa[$nome_doador]++;
    }
}

// Ordenar por quantidade de doações
arsort($doacoes_por_pessoa);

$pageTitle = 'Assinar Documentos 14.1';
$backUrl = 'relatorio-14-1.php?id=' . urlencode($id_planilha);
$headerActions = '';

ob_start();
?>

<style>
.produto-card {
    transition: all 0.2s;
    border: 1px solid #dee2e6;
    border-left: 4px solid #dee2e6;
    border-radius: 0.375rem;
    cursor: pointer;
}

.produto-card:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.produto-card.assinado {
    border-left-color: #28a745;
}

.produto-card.pendente {
    border-left-color: #ffc107;
}

.produto-card.selected {
    background-color: #e7f3ff;
    border-color: #007bff !important;
    border-left-color: #007bff !important;
}

.doador-tag {
    display: inline-block;
    background: #28a745;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.stats-number {
    font-size: 2.5rem;
    font-weight: bold;
    line-height: 1;
}

.doacoes-list {
    max-height: 200px;
    overflow-y: auto;
}

.doacao-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.doacao-item:last-child {
    border-bottom: none;
}
</style>

<!-- Resumo Informativo -->
<div class="alert alert-info mb-4">
    <h5 class="alert-heading mb-3">
        <i class="bi bi-info-circle-fill me-2"></i>
        Informações sobre as Assinaturas
    </h5>
    
    <div class="mb-3">
        <strong>Total de produtos nesta planilha:</strong> <?php echo $total_produtos; ?> 
        (<?php echo $produtos_assinados; ?> assinados, <?php echo $total_produtos - $produtos_assinados; ?> pendentes)
    </div>
    
    <?php if (!empty($doacoes_por_pessoa)): ?>
    <div class="mb-2">
        <strong>Produtos já assinados por:</strong>
    </div>
    <ul class="mb-0">
        <?php foreach ($doacoes_por_pessoa as $nome => $quantidade): ?>
        <li>
            <strong><?php echo htmlspecialchars($nome); ?></strong> - 
            <?php echo $quantidade; ?> <?php echo $quantidade == 1 ? 'produto' : 'produtos'; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <div class="text-muted">
        <em>Nenhum produto foi assinado ainda.</em>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-pen me-2"></i>
        Produtos para Assinar
    </div>
    <div class="card-body">
        <p class="mb-2">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Selecione</strong> um ou mais produtos e clique em "Assinar Selecionados" para assinar todos de uma vez.
        </p>
        <p class="mb-0 text-muted small">
            Ou clique diretamente em um produto individual para assiná-lo separadamente.
        </p>
    </div>
</div>

<!-- Barra de ações para produtos selecionados -->
<div class="alert alert-success mb-3" id="toolbarSelecao" style="display: none;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><span id="contadorSelecionados">0</span> produto(s) selecionado(s)</strong>
        </div>
        <div>
            <button type="button" class="btn btn-success btn-sm" onclick="assinarSelecionados()">
                <i class="bi bi-check2-all me-1"></i>
                Assinar Selecionados
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limparSelecao()">
                <i class="bi bi-x-lg me-1"></i>
                Limpar Seleção
            </button>
        </div>
    </div>
</div>

<?php if (count($produtos) > 0): ?>
    <div class="row g-3">
        <?php foreach ($produtos as $produto): 
            $assinado = !empty($produto['doador_conjugue_id']);
            $status_class = $assinado ? 'assinado' : 'pendente';
        ?>
            <div class="col-12">
                <div class="card produto-card <?php echo $status_class; ?>" 
                     data-produto-id="<?php echo $produto['id_produto']; ?>">
                    <div class="card-body">
                        <div class="d-flex gap-3">
                            <!-- Checkbox de seleção -->
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="check_<?php echo $produto['id_produto']; ?>"
                                       value="<?php echo $produto['id_produto']; ?>"
                                       onchange="atualizarSelecao()"
                                       style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                            </div>
                            
                            <!-- Conteúdo do produto -->
                            <div class="flex-grow-1" onclick="abrirAssinatura(<?php echo $produto['id_produto']; ?>)" style="cursor: pointer;">
                                <?php if ($assinado): ?>
                                <div class="doador-tag">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    Doado por: <?php echo htmlspecialchars($produto['doador_nome']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <h6 class="card-title mb-2">
                                    <i class="bi bi-box-seam me-1"></i>
                                    <?php echo htmlspecialchars($produto['tipo_descricao'] ?? 'Produto'); ?>
                                </h6>
                                <p class="card-text small text-muted mb-0">
                                    <?php echo htmlspecialchars(substr($produto['descricao_completa'], 0, 150)); ?>
                                    <?php if (strlen($produto['descricao_completa']) > 150): ?>...<?php endif; ?>
                                </p>
                            </div>
                        </div>
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

function atualizarSelecao() {
    produtosSelecionados.clear();
    document.querySelectorAll('.form-check-input:checked').forEach(checkbox => {
        produtosSelecionados.add(parseInt(checkbox.value));
    });
    
    const toolbar = document.getElementById('toolbarSelecao');
    const contador = document.getElementById('contadorSelecionados');
    
    contador.textContent = produtosSelecionados.size;
    
    if (produtosSelecionados.size > 0) {
        toolbar.style.display = 'block';
    } else {
        toolbar.style.display = 'none';
    }
}

function limparSelecao() {
    document.querySelectorAll('.form-check-input').forEach(checkbox => {
        checkbox.checked = false;
    });
    atualizarSelecao();
}

function assinarSelecionados() {
    if (produtosSelecionados.size === 0) {
        alert('Selecione pelo menos um produto para assinar.');
        return;
    }
    
    const ids = Array.from(produtosSelecionados).join(',');
    window.location.href = 'assinatura-14-1-form.php?ids=' + ids + '&id_planilha=<?php echo $id_planilha; ?>';
}

function abrirAssinatura(id) {
    window.location.href = 'assinatura-14-1-form.php?id=' + id + '&id_planilha=<?php echo $id_planilha; ?>';
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
