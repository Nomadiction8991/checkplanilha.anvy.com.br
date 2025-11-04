<?php
require_once '../../../auth.php'; // Autenticação
include __DIR__ . '/../../../CRUD/UPDATE/usuario.php';

$pageTitle = 'Editar Usuário';
$backUrl = './read-usuario.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($usuario)): ?>
<form method="POST">
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-pencil me-2"></i>
            Dados do Usuário
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nome" name="nome" 
                       value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Deixe os campos de senha em branco para manter a senha atual
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="senha" class="form-label">Nova Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" minlength="6">
                    <small class="text-muted">Mínimo de 6 caracteres (deixe em branco para não alterar)</small>
                </div>

                <div class="col-md-6">
                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" minlength="6">
                </div>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo $usuario['ativo'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ativo">
                    Usuário Ativo
                </label>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            Atualizar
        </button>
    </div>
</form>

<script>
// Validar se as senhas são iguais (quando preenchidas)
document.querySelector('form').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha').value;
    const confirmar = document.getElementById('confirmar_senha').value;
    
    if (senha || confirmar) {
        if (senha !== confirmar) {
            e.preventDefault();
            alert('As senhas não conferem!');
            return false;
        }
    }
});
</script>
<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
