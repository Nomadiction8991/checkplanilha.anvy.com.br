<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação

// Apenas admins podem criar dependências
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

include __DIR__ . '/../../../CRUD/CREATE/dependencia.php';

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
                <div class="col-md-6">
                    <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="codigo" name="codigo" required
                           placeholder="Digite o código único">
                    <div class="form-text">O código deve ser único.</div>
                </div>
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
                <a href="./read-dependencia.php" class="btn btn-secondary ms-2">
                    <i class="bi bi-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app-wrapper.php';
?></content>
<parameter name="filePath">/home/weverton/Documentos/Github-Gitlab/GitHub/checkplanilha.anvy.com.br/app/views/dependencias/create-dependencia.php