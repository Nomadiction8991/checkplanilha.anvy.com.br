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

.badge-status {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
}
</style>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-pen me-2"></i>
        Selecione os Produtos para Assinar
    </div>
    <div class="card-body">
        <p class="mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Clique no produto para iniciar ou gerenciar o processo de assinatura digital.
        </p>
    </div>
</div>

<?php if (count($produtos) > 0): ?>
    <div class="row g-3">
        <?php foreach ($produtos as $produto): ?>
            <div class="col-12">
                <a href="./assinatura-14-1-form.php?id_produto=<?php echo $produto['id']; ?>&id_planilha=<?php echo $id_planilha; ?>" 
                   class="text-decoration-none">
                    <div class="card produto-card status-<?php echo $produto['status_assinatura']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
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
                                <div class="mt-3 pt-3 border-top">
                                    <small class="text-muted">
                                        <i class="bi bi-link-45deg me-1"></i>
                                        Link de assinatura disponível
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
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

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinatura_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
