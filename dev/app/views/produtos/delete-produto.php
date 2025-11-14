<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação
include PROJECT_ROOT . '/CRUD/DELETE/produto.php';

$pageTitle = 'Excluir Produto';
$backUrl = './read-produto.php?id=' . urlencode($id_planilha) . '&' . gerarParametrosFiltro();

ob_start();
?>

<div class="alert alert-warning">
  <strong>Atenção:</strong> Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.
  <?php if (!empty($erros)): ?>
    <ul class="mb-0 mt-2">
      <?php foreach ($erros as $erro): ?>
        <li><?php echo htmlspecialchars($erro); ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  
</div>

<form method="POST" id="form-produto">
  <div class="card mb-3">
    <div class="card-body">
      <?php if (!empty($produto['codigo'])): ?>
      <div class="mb-3">
        <label class="form-label">Código</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($produto['codigo']); ?>" disabled>
      </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Tipos de Bens</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars(($produto['tipo_codigo'] ?? '') . ' - ' . ($produto['tipo_descricao'] ?? '')); ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">Bem</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($produto['tipo_ben'] ?? ''); ?>" disabled>
      </div>

      <div class="mb-3">
        <label class="form-label">Complemento</label>
        <textarea class="form-control" rows="3" disabled><?php echo htmlspecialchars($produto['complemento'] ?? ''); ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Dependência</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($produto['dependencia_descricao'] ?? $produto['dependencia'] ?? ''); ?>" disabled>
      </div>

      <div class="mb-2">
        <label class="form-label">Status</label>
        <div class="d-flex gap-2">
          <span class="badge bg-<?php echo (isset($produto['condicao_141']) && ($produto['condicao_141'] == 1 || $produto['condicao_141'] == 3)) ? 'warning text-dark' : 'secondary'; ?>">Nota</span>
          <span class="badge bg-<?php echo ($produto['imprimir_14_1'] == 1) ? 'primary' : 'secondary'; ?>">14.1</span>
        </div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-danger w-100">
    <i class="bi bi-trash me-2"></i>
    Confirmar Exclusão
  </button>
</form>

<?php
$contentHtml = ob_get_clean();
$tempFile = PROJECT_ROOT . '/temp_delete_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include PROJECT_ROOT . '/layouts/app-wrapper.php';
unlink($tempFile);
?>
