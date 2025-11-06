<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/CRUD/conexao.php';
require_once __DIR__ . '/app/functions/comum_functions.php';

$pageTitle = 'Comuns';
$backUrl = null;

$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuPrincipal" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuPrincipal">
            <li>
                <a class="dropdown-item" href="app/views/planilhas/importar-planilha.php">
                    <i class="bi bi-upload me-2"></i>Importar Planilha
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

$customCss = '
.table-hover tbody tr { cursor: pointer; }
.input-group .btn-clear { border-top-left-radius: 0; border-bottom-left-radius: 0; }
';

$busca = trim($_GET['busca'] ?? '');
$comums = buscar_comuns($conexao, $busca);

function formatar_codigo_comum($codigo) {
    $codigo = preg_replace('/\D/', '', (string) $codigo);
    if ($codigo === '') {
        return 'BR --';
    }

    $codigo = str_pad($codigo, 6, '0', STR_PAD_LEFT);
    $prefixo = substr($codigo, 0, 2);
    $sufixo = substr($codigo, 2);

    return 'BR ' . $prefixo . '-' . $sufixo;
}

ob_start();
?>

<?php if (!empty($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo ($_SESSION['tipo_mensagem'] ?? 'info') === 'success' ? 'success' : (($_SESSION['tipo_mensagem'] ?? 'info') === 'danger' ? 'danger' : 'info'); ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['mensagem']); unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-search me-2"></i>Pesquisar Comum
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12">
                <label for="busca" class="form-label">Código ou descrição</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" name="busca" id="busca" class="form-control"
                           value="<?php echo htmlspecialchars($busca); ?>"
                           placeholder="Ex: 09-0040 ou SIBIPIRUNAS">
                    <?php if ($busca !== ''): ?>
                        <a href="index.php" class="btn btn-outline-secondary btn-clear" title="Limpar filtro">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Buscar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-building me-2"></i>Comuns cadastrados
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 40%">Código</th>
                        <th>Descrição</th>
                        <th style="width: 110px">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comums)): ?>
                        <tr>
                            <td colspan="2" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nenhum comum encontrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($comums as $comum): ?>
                            <tr data-href="app/views/comuns/listar-planilhas.php?comum_id=<?php echo (int) $comum['id']; ?>">
                                <td class="fw-semibold text-uppercase">
                                    <?php echo htmlspecialchars(formatar_codigo_comum($comum['codigo'])); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($comum['descricao']); ?>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="app/views/comuns/listar-planilhas.php?comum_id=<?php echo (int) $comum['id']; ?>" title="Abrir">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/temp_index_content.php';
file_put_contents($contentFile, $contentHtml);

$customJs = '
document.querySelectorAll("[data-href]").forEach(function(row) {
    row.addEventListener("click", function() {
        var destino = row.getAttribute("data-href");
        if (destino) {
            window.location.href = destino;
        }
    });
});
';

require_once __DIR__ . '/app/views/layouts/app-wrapper.php';

@unlink($contentFile);
?>
