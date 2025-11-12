<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação

// Apenas admins podem acessar gestão de dependências
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

include __DIR__ . '/../../../CRUD/READ/dependencia.php';

$pageTitle = 'Dependências';
$backUrl = '../../../index.php';
$headerActions = '
    <a href="./create-dependencia.php" class="btn-header-action" title="Nova Dependência"><i class="bi bi-plus-lg"></i></a>
';

ob_start();
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Operação realizada com sucesso!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list me-2"></i>
            Lista de Dependências
        </span>
        <span class="badge bg-white text-dark"><?php echo $total_registros; ?> itens (pág. <?php echo $pagina; ?>/<?php echo $total_paginas ?: 1; ?>)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($dependencias)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Nenhuma dependência cadastrada
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dependencias as $dependencia): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dependencia['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($dependencia['descricao']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="./edit-dependencia.php?id=<?php echo $dependencia['id']; ?>"
                                           class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="deletarDependencia(<?php echo $dependencia['id']; ?>)"
                                                title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Paginação -->
<?php if ($total_paginas > 1): ?>
    <nav aria-label="Paginação" class="mt-3">
        <ul class="pagination justify-content-center">
            <?php if ($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($pagina < $total_paginas): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>">Próximo</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<script>
function deletarDependencia(id) {
    if (confirm('Tem certeza que deseja excluir esta dependência?')) {
        fetch('../../../CRUD/DELETE/dependencia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro na requisição: ' + error);
        });
    }
}
</script>

<?php
$contentHtml = ob_get_clean();
include __DIR__ . '/../layouts/app-wrapper.php';
?></content>
<parameter name="filePath">/home/weverton/Documentos/Github-Gitlab/GitHub/checkplanilha.anvy.com.br/app/views/dependencias/read-dependencia.php