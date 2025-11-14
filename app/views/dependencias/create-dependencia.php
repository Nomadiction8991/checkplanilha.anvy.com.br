<?php
declare(strict_types=1);
require_once PROJECT_ROOT . '/auth.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

include PROJECT_ROOT . '/CRUD/CREATE/dependencia.php';

$pageTitle = 'Nova Dependência';
$backUrl = './read-dependencia.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-plus-circle me-2"></i>
        Cadastrar Nova Dependência
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row g-3">
                <!-- campo codigo removido conforme solicitado -->
                <div class="col-md-6">
                    <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="descricao" name="descricao" required
                           placeholder="Digite a descrição">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-2"></i>
                    Cadastrar Dependência
                </button>
                <!-- botão Voltar removido conforme solicitado -->
            </div>
        </form>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = sys_get_temp_dir() . '/temp_create_dependencia_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include PROJECT_ROOT . '/layouts/app-wrapper.php';
unlink($tempFile);
?>
<parameter name="filePath">/home/weverton/Documentos/Github-Gitlab/GitHub/checkplanilha.anvy.com.br/app/views/dependencias/create-dependencia.php