<?php
require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'Comuns';
$backUrl = null;

$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuPrincipal" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuPrincipal">';

// Mostrar "Listagem de Usuários" apenas para Administrador/Acessor
if (isAdmin()) {
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="app/views/usuarios/usuarios_listar.php">
                    <i class="bi bi-people me-2"></i>Listagem de Usuários
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="app/views/dependencias/dependencias_listar.php">
                    <i class="bi bi-diagram-3 me-2"></i>Listagem de Dependências
                </a>
            </li>';
}

// Doador/Conjugue: adicionar opção "Editar Meu Usuário"
if (isDoador() && isset($_SESSION['usuario_id'])) {
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="app/views/usuarios/usuario_editar.php?id=' . (int)$_SESSION['usuario_id'] . '">
                    <i class="bi bi-pencil-square me-2"></i>Editar Meu Usuário
                </a>
            </li>';
}

// Mostrar "Importar Planilha" apenas para Administrador/Acessor
if (isAdmin()) {
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="app/views/planilhas/planilha_importar.php">
                    <i class="bi bi-upload me-2"></i>Importar Planilha
                </a>
            </li>';
}

$headerActions .= '
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
.table.table-center thead th, .table.table-center tbody td { text-align: center; vertical-align: middle; }
';

$busca = trim($_GET['busca'] ?? '');
$comums = buscar_comuns($conexao, $busca);

function formatar_codigo_comum($codigo) {
    $codigo = preg_replace("/\\D/", '', (string) $codigo);
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
                    <input type="text" name="busca" id="busca" class="form-control"
                           value="<?php echo htmlspecialchars($busca); ?>"
                           placeholder="Pesquise pelo código ou nome da comum...">
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
    <div class="card-footer text-muted small">
        <?php echo count($comums); ?> comum(ns) encontrado(s)
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-building me-2"></i>Comuns cadastrados
        </span>
        <span class="badge bg-white text-dark"><?php echo count($comums); ?> itens</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-center mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 40%">Código</th>
                        <th>Descrição</th>
                        <th style="width: 140px">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comums)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                Nenhum comum encontrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($comums as $comum): ?>
                            <?php
                                $cadastro_ok = trim((string) $comum['descricao']) !== ''
                                               && trim((string) $comum['cnpj']) !== ''
                                               && trim((string) $comum['administracao']) !== ''
                                               && trim((string) $comum['cidade']) !== '';
                            ?>
                            <tr>
                                <td class="fw-semibold text-uppercase">
                                    <?php echo htmlspecialchars($comum['codigo']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($comum['descricao']); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a class="btn btn-outline-primary" href="app/views/comuns/comum_editar.php?id=<?php echo (int) $comum['id']; ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($cadastro_ok): ?>
                                            <a class="btn btn-outline-secondary" href="app/views/planilhas/planilha_visualizar.php?comum_id=<?php echo (int) $comum['id']; ?>" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" type="button" title="Complete o cadastro para visualizar" disabled>
                                                <i class="bi bi-eye-slash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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

require_once __DIR__ . '/app/views/layouts/app_wrapper.php';

@unlink($contentFile);
?>
