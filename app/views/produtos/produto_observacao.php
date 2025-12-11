<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AutenticaÃ§Ã£o
include __DIR__ . '/../../../app/controllers/update/ProdutoObservacaoController.php';

$pageTitle = "ObservaÃ§Ãµes";
$backUrl = getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status);

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-box-seam me-2"></i>
        Produto
    </div>
    <div class="card-body">
        <div class="row g-2 small">
            <div class="col-12"><?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></div>
            <div class="col-12"><?php echo htmlspecialchars($produto['descricao_completa'] ?? ''); ?></div>
        </div>
        
        <div class="mt-2">
            <?php if ($check['checado'] == 1): ?>
                <span class="badge bg-success">âœ… Checado</span>
            <?php endif; ?>
            <?php if (!empty($check['observacoes'])): ?>
                <span class="badge bg-warning">ðŸ“œ Com Obs</span>
            <?php endif; ?>
            <?php if ($check['imprimir'] == 1): ?>
                <span class="badge bg-info">ðŸ·ï¸ Imprimir</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="POST">
    <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
    <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
    <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">

    <div class="card mb-3">
        <div class="card-body">
            <label for="observacoes" class="form-label">
                <i class="bi bi-chat-square-text me-2"></i>
                ObservaÃ§Ãµes
            </label>
            <textarea class="form-control" id="observacoes" name="observacoes" rows="6" 
                      placeholder="Digite as observaÃ§Ãµes do produto..."><?php echo htmlspecialchars($check['observacoes'] ?? ''); ?></textarea>
            <div class="form-text">Deixe em branco para remover as observaÃ§Ãµes</div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-save me-2"></i>
        Salvar ObservaÃ§Ãµes
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_obs_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>



