<?php
include __DIR__ . '/../../../CRUD/READ/usuario.php';

$pageTitle = 'Usuários';
$backUrl = '../../../index.php';
$headerActions = '
    <a href="./create-usuario.php" class="btn-header-action" title="Novo Usuário"><i class="bi bi-plus-lg"></i></a>
';

ob_start();
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Usuário cadastrado com sucesso!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-people me-2"></i>
        Lista de Usuários
    </div>
    <div class="card-body p-0">
        <?php if (empty($usuarios)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Nenhum usuário cadastrado
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <?php if ($usuario['ativo']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="./editar-usuario.php?id=<?php echo $usuario['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button onclick="excluirUsuario(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nome']); ?>')" 
                                                class="btn btn-sm btn-outline-danger" title="Excluir">
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

<script>
function excluirUsuario(id, nome) {
    if (!confirm('Tem certeza que deseja excluir o usuário "' + nome + '"?')) {
        return;
    }

    fetch('../../../CRUD/DELETE/usuario.php', {
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
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Erro ao excluir usuário');
        console.error(error);
    });
}
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_read_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
