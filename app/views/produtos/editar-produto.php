<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
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
                <strong>Descrição Completa:</strong> <?php echo htmlspecialchars($produto['descricao_completa'] ?? ''); ?>
            </div>
            <div class="col-12">
                <strong>Complemento:</strong> <?php echo htmlspecialchars($produto['complemento'] ?? ''); ?>
            </div>
            <div class="col-12">
                <strong>Bem:</strong> <?php echo htmlspecialchars($produto['ben'] ?? ''); ?>
            </div>
            <div class="col-12">
                <strong>Dependência:</strong> <?php echo htmlspecialchars($produto['dependencia_id'] ?? ''); ?>
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
                <label for="nova_descricao" class="form-label">Nova Descrição Completa</label>
                <textarea class="form-control" id="nova_descricao" name="nova_descricao" rows="3"
                       placeholder="Deixe em branco para não alterar"><?php echo htmlspecialchars($nova_descricao ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="novo_complemento" class="form-label">Novo Complemento</label>
                <input type="text" class="form-control" id="novo_complemento" name="novo_complemento" 
                       value="<?php echo htmlspecialchars($novo_complemento ?? ''); ?>" 
                       placeholder="Deixe em branco para não alterar">
            </div>

            <div class="mb-3">
                <label for="novo_ben" class="form-label">Novo Bem</label>
                <input type="text" class="form-control" id="novo_ben" name="novo_ben" 
                       value="<?php echo htmlspecialchars($novo_ben ?? ''); ?>" 
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

<div class="mt-3">
    <a href="./limpar-edicoes.php?id=<?php echo $id_planilha; ?>&id_produto=<?php echo $id_produto; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"
       class="btn btn-outline-danger w-100"
       onclick="return confirm('Tem certeza que deseja limpar as edições deste produto?');">
        <i class="bi bi-trash3 me-2"></i>
        Limpar Edições
    </a>
    <div class="form-text mt-1">Remove nome/dependência editados e desmarca para impressão.</div>
    </div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
