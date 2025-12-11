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
$buscaDisplay = mb_strtoupper($busca, 'UTF-8');

// Paginação: 10 por página
$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

$total_count = contar_comuns($conexao, $busca);
$total_paginas = $total_count > 0 ? (int) ceil($total_count / $limite) : 1;
$comums = buscar_comuns_paginated($conexao, $busca, $limite, $offset);

// AJAX handler: retorna as linhas da tabela e a contagem em JSON
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    // recompute pagina/limit/offset from ajax params
    $pagina_ajax = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
    $limite_ajax = isset($_GET['limite']) ? max(1, (int) $_GET['limite']) : $limite;
    $offset_ajax = ($pagina_ajax - 1) * $limite_ajax;

    $comums_page = buscar_comuns_paginated($conexao, $busca, $limite_ajax, $offset_ajax);

    $rowsHtml = '';
    if (empty($comums_page)) {
        $rowsHtml = '<tr><td colspan="3" class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum comum encontrado</td></tr>';
    } else {
        foreach ($comums_page as $comum) {
            $cadastro_ok = trim((string) $comum['descricao']) !== ''
                           && trim((string) $comum['cnpj']) !== ''
                           && trim((string) $comum['administracao']) !== ''
                           && trim((string) $comum['cidade']) !== '';

            $rowsHtml .= '<tr>';
            $rowsHtml .= '<td class="fw-semibold text-uppercase">' . htmlspecialchars($comum['codigo']) . '</td>';
            $rowsHtml .= '<td class="text-uppercase">' . htmlspecialchars($comum['descricao']) . '</td>';
            $rowsHtml .= '<td>';
            $rowsHtml .= '<div class="btn-group btn-group-sm" role="group">';
            $rowsHtml .= '<a class="btn btn-outline-primary" href="app/views/comuns/comum_editar.php?id=' . (int) $comum['id'] . '" title="Editar"><i class="bi bi-pencil"></i></a>';
            $rowsHtml .= '<a class="btn btn-outline-secondary btn-view-planilha" href="app/views/planilhas/planilha_visualizar.php?comum_id=' . (int) $comum['id'] . '" data-cadastro-ok="' . ($cadastro_ok ? '1' : '0') . '" data-edit-url="app/views/comuns/comum_editar.php?id=' . (int) $comum['id'] . '" title="Visualizar planilha"><i class="bi bi-eye"></i></a>';
            $rowsHtml .= '</div>';
            $rowsHtml .= '</td>';
            $rowsHtml .= '</tr>';
        }
    }

    $total_count_ajax = contar_comuns($conexao, $busca);
    $total_pages_ajax = $total_count_ajax > 0 ? (int) ceil($total_count_ajax / $limite_ajax) : 1;

    echo json_encode([
        'rows' => $rowsHtml,
        'count' => $total_count_ajax,
        'page' => $pagina_ajax,
        'total_pages' => $total_pages_ajax
    ]);
    exit;
}

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
                    <input type="text" name="busca" id="busca" class="form-control text-uppercase"
                           value="<?php echo htmlspecialchars($buscaDisplay); ?>">
                </div>
            </div>
        <!-- Busca automática: botão removido para pesquisa em tempo real -->
        </form>
    </div>
    <div id="comumCount" class="card-footer text-muted small">
        <?php echo count($comums); ?> comum(ns) encontrado(s)
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-building me-2"></i>Comuns cadastrados
        </span>
        <span id="comumBadge" class="badge bg-white text-dark"><?php echo count($comums); ?> itens</span>
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
                <tbody id="comunsTbody">
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
                            <td class="text-uppercase">
                                <?php echo htmlspecialchars($comum['descricao']); ?>
                            </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a class="btn btn-outline-primary" href="app/views/comuns/comum_editar.php?id=<?php echo (int) $comum['id']; ?>" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a class="btn btn-outline-secondary btn-view-planilha"
                                           href="app/views/planilhas/planilha_visualizar.php?comum_id=<?php echo (int) $comum['id']; ?>"
                                           data-cadastro-ok="<?php echo $cadastro_ok ? '1' : '0'; ?>"
                                           data-edit-url="app/views/comuns/comum_editar.php?id=<?php echo (int) $comum['id']; ?>"
                                           title="Visualizar planilha">
                                            <i class="bi bi-eye"></i>
                                        </a>
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

<nav id="comumPagination" class="mt-3" aria-label="Paginação comuns">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <?php if($pagina > 1): ?>
        <li class="page-item"><a class="page-link" href="#" data-page="<?php echo $pagina-1; ?>">&laquo;</a></li>
        <?php endif; ?>
        <?php $ini = max(1,$pagina-2); $fim = min($total_paginas,$pagina+2); for($i=$ini;$i<=$fim;$i++): ?>
            <li class="page-item <?php echo $i==$pagina?'active':''; ?>">
                <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <?php if($pagina < $total_paginas): ?>
        <li class="page-item"><a class="page-link" href="#" data-page="<?php echo $pagina+1; ?>">&raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/temp_index_content.php';
file_put_contents($contentFile, $contentHtml);

require_once __DIR__ . '/app/views/layouts/app_wrapper.php';

@unlink($contentFile);
?>

<!-- Modal cadastro incompleto -->
<div class="modal fade" id="cadastroIncompletoModal" tabindex="-1" aria-labelledby="cadastroIncompletoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cadastroIncompletoLabel">Cadastro incompleto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Complete os dados da comum (descrição, CNPJ, administração e cidade) para visualizar a planilha.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary w-47" data-bs-dismiss="modal">Deixar para depois</button>
                <a href="#" class="btn btn-primary btn-edit-agora w-47">
                    <i class="bi bi-pencil-square me-1"></i>Editar agora
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('cadastroIncompletoModal');
    var modalInstance = modalEl ? new bootstrap.Modal(modalEl) : null;

    // Delegated handler for view-planilha buttons (works for dynamically loaded rows)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-view-planilha');
        if (!btn) return;
        var ok = btn.getAttribute('data-cadastro-ok') === '1';
        if (!ok) {
            e.preventDefault();
            if (modalInstance && modalEl) {
                var editBtn = modalEl.querySelector('.btn-edit-agora');
                if (editBtn) {
                    editBtn.setAttribute('href', btn.getAttribute('data-edit-url'));
                }
                modalInstance.show();
            }
        }
    });

    // Live search with debounce and prevent form submit
    var input = document.getElementById('busca');
    if (!input) return;
    var timeout = null;

    function doSearch(q, page) {
        page = page || 1;
        var url = window.location.pathname + '?ajax=1&busca=' + encodeURIComponent(q) + '&pagina=' + encodeURIComponent(page) + '&limite=' + encodeURIComponent(<?php echo $limite; ?>);
        fetch(url, { credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var tbody = document.getElementById('comunsTbody');
                var countEl = document.getElementById('comumCount');
                var badge = document.getElementById('comumBadge');
                if (tbody) tbody.innerHTML = data.rows;
                if (countEl) countEl.textContent = data.count + ' comum(ns) encontrado(s)';
                if (badge) badge.textContent = data.count + ' itens';
                // rebuild pagination
                var pagination = document.getElementById('comumPagination');
                if (pagination && data.total_pages) {
                    var page = data.page || 1;
                    var total = data.total_pages;
                    var ini = Math.max(1, page - 2);
                    var fim = Math.min(total, page + 2);
                    var html = '<ul class="pagination pagination-sm justify-content-center mb-0">';
                    if (page > 1) html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (page-1) + '">&laquo;</a></li>';
                    for (var i = ini; i <= fim; i++) {
                        html += '<li class="page-item ' + (i===page? 'active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
                    }
                    if (page < total) html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (page+1) + '">&raquo;</a></li>';
                    html += '</ul>';
                    pagination.innerHTML = html;
                }
            })
            .catch(function(err) {
                console.error('Erro na busca AJAX:', err);
            });
    }

    // Prevent the form from submitting with Enter and from default GET reloads
    var form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            doSearch(input.value.trim());
        });
    }

    // Debounced input
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        // reset to first page when typing
        currentPage = 1;
        timeout = setTimeout(function() { doSearch(input.value.trim(), currentPage); }, 300);
    });

    // If user presses Enter in the input, prevent default and trigger immediate search
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(timeout);
            currentPage = 1;
            doSearch(input.value.trim(), currentPage);
        }
    });

    // Pagination click handling (delegated)
    var pagination = document.getElementById('comumPagination');
    if (pagination) {
        pagination.addEventListener('click', function(e) {
            var a = e.target.closest('a[data-page]');
            if (!a) return;
            e.preventDefault();
            var page = parseInt(a.getAttribute('data-page'), 10) || 1;
            currentPage = page;
            doSearch(input.value.trim(), currentPage);
        });
    }

    // current page variable
    var currentPage = <?php echo $pagina; ?>;
});
</script>
