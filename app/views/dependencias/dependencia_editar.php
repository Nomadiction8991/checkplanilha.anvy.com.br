<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';


if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idParam <= 0) {
    header('Location: ./dependencias_listar.php');
    exit;
}

include __DIR__ . '/../../../app/controllers/update/DependenciaUpdateController.php';

$pageTitle = 'Editar DependÃªncia';
$backUrl = './dependencias_listar.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($dependencia)): ?>
<form method="POST" id="formDependencia">
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-pencil-square me-2"></i>
            Editar DependÃªncia
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="codigo" class="form-label">CÃ³digo</label>
                <input type="text" class="form-control" id="codigo" name="codigo" 
                       value="<?php echo htmlspecialchars($dependencia['codigo']); ?>" maxlength="50">
                <small class="text-muted">CÃ³digo Ãºnico da dependÃªncia (opcional)</small>
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">DescriÃ§Ã£o <span class="text-danger">*</span></label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3" required><?php echo htmlspecialchars($dependencia['descricao']); ?></textarea>
                <small class="text-muted">DescriÃ§Ã£o da dependÃªncia</small>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i>
            Atualizar DependÃªncia
        </button>
    </div>
</form>

<script>
// ValidaÃ§Ã£o do formulÃ¡rio
document.getElementById('formDependencia').addEventListener('submit', function(e) {
    const descricao = document.getElementById('descricao').value.trim();
    
    if (!descricao) {
        e.preventDefault();
        alert('A descriÃ§Ã£o Ã© obrigatÃ³ria!');
        return false;
    }
});
</script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
$tempFile = sys_get_temp_dir() . '/temp_editar_dependencia_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>



