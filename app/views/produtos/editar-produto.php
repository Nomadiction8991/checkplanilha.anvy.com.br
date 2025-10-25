<?php
require_once __DIR__ . '/../../../CRUD/UPDATE/editar-produto.php';

$pageTitle = "Editar Produto";
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
        <i class="bi bi-info-circle me-2"></i>
        Informações Atuais
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-12">
                <strong>Código:</strong> <?php echo htmlspecialchars($produto['codigo'] ?? ''); ?>
            </div>
            <div class="col-12">
                <strong>Nome:</strong> <?php echo htmlspecialchars($produto['nome'] ?? ''); ?>
            </div>
            <div class="col-12">
                <strong>Dependência:</strong> <?php echo htmlspecialchars($produto['dependencia'] ?? ''); ?>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info small">
    <strong>ℹ️ Informação:</strong> Campos em branco = sem alteração. 
    <br><strong>⚠️ Atenção:</strong> Editar marca automaticamente para impressão.
</div>

<form method="POST">
    <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
    <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
    <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">

    <div class="card mb-3">
        <div class="card-body">
            <div class="mb-3">
                <label for="novo_nome" class="form-label">Novo Nome</label>
                <input type="text" class="form-control" id="novo_nome" name="novo_nome" 
                       value="<?php echo htmlspecialchars($novo_nome ?? ''); ?>" 
                       placeholder="Deixe em branco para não alterar">
            </div>

            <div class="mb-3">
                <label for="nova_dependencia" class="form-label">Nova Dependência</label>
                <select class="form-select" id="nova_dependencia" name="nova_dependencia">
                    <option value="">-- Não alterar --</option>
                    <?php foreach ($dependencia_options as $dep): ?>
                        <option value="<?php echo htmlspecialchars($dep); ?>" 
                            <?php echo (isset($nova_dependencia) && $nova_dependencia === $dep) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        Salvar Alterações
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
