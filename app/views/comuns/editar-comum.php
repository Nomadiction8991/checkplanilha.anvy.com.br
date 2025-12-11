<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../CRUD/conexao.php';
require_once __DIR__ . '/../../../app/functions/comum_functions.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ./listar-comuns.php');
    exit;
}

$comum = obter_comum_por_id($conexao, $id);
if (!$comum) {
    $_SESSION['mensagem'] = 'Comum não encontrada.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ./listar-comuns.php');
    exit;
}

$pageTitle = 'Editar Comum';
$backUrl = './listar-comuns.php';

ob_start();
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-pencil-square me-2"></i>Editar Comum</span>
            <a href="./listar-comuns.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="../../../CRUD/UPDATE/comum.php" novalidate>
                <input type="hidden" name="id" value="<?php echo (int)$comum['id']; ?>">

                <div class="mb-3">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($comum['codigo']); ?>" disabled>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                        <input type="text" id="descricao" name="descricao" class="form-control" required
                               value="<?php echo htmlspecialchars($comum['descricao']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="cnpj" class="form-label">CNPJ <span class="text-danger">*</span></label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control" required
                               value="<?php echo htmlspecialchars($comum['cnpj']); ?>" placeholder="00.000.000/0000-00">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="administracao" class="form-label">Administração <span class="text-danger">*</span></label>
                        <input type="text" id="administracao" name="administracao" class="form-control" required
                               value="<?php echo htmlspecialchars($comum['administracao']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                        <input type="text" id="cidade" name="cidade" class="form-control" required
                               value="<?php echo htmlspecialchars($comum['cidade']); ?>">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="setor" class="form-label">Setor (opcional)</label>
                        <input type="text" id="setor" name="setor" class="form-control"
                               value="<?php echo htmlspecialchars($comum['setor'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput && window.Inputmask) {
        Inputmask({"mask": "99.999.999/9999-99"}).mask(cnpjInput);
    }
});
</script>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../temp_editar_comum_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include __DIR__ . '/../layouts/app-wrapper.php';
@unlink($contentFile);
?>
