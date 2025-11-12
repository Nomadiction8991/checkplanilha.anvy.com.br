<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../auth.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idParam <= 0) {
    header('Location: ./read-dependencia.php');
    exit;
}

include __DIR__ . '/../../../CRUD/UPDATE/dependencia.php';

$pageTitle = 'Editar Dependência';
$backUrl = './read-dependencia.php';

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
            Editar Dependência
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="codigo" class="form-label">Código</label>
                <input type="text" class="form-control" id="codigo" name="codigo" 
                       value="<?php echo htmlspecialchars($dependencia['codigo']); ?>" maxlength="50">
                <small class="text-muted">Código único da dependência (opcional)</small>
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3" required><?php echo htmlspecialchars($dependencia['descricao']); ?></textarea>
                <small class="text-muted">Descrição da dependência</small>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i>
            Atualizar Dependência
        </button>
    </div>
</form>

<script>
// Validação do formulário
document.getElementById('formDependencia').addEventListener('submit', function(e) {
    const descricao = document.getElementById('descricao').value.trim();
    
    if (!descricao) {
        e.preventDefault();
        alert('A descrição é obrigatória!');
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
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>