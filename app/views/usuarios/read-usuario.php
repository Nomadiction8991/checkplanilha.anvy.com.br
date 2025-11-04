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

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Usuário atualizado com sucesso!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filtros de Pesquisa -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="filtroNome" class="form-label">
                    <i class="bi bi-search me-1"></i>
                    Buscar por nome
                </label>
                <input type="text" class="form-control" id="filtroNome" placeholder="Digite o nome do usuário...">
            </div>
            <div class="col-md-4">
                <label for="filtroStatus" class="form-label">
                    <i class="bi bi-funnel me-1"></i>
                    Status
                </label>
                <select class="form-select" id="filtroStatus">
                    <option value="">Todos</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-secondary w-100" onclick="limparFiltros()">
                    <i class="bi bi-x-lg me-1"></i>
                    Limpar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-people me-2"></i>
            Lista de Usuários
        </div>
        <small class="text-muted">
            <span id="totalUsuarios"><?php echo count($usuarios); ?></span> 
            <span id="usuariosTexto">usuário(s)</span>
        </small>
    </div>
    <div class="card-body p-0">
        <?php if (empty($usuarios)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Nenhum usuário cadastrado
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabelaUsuarios">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Nome</th>
                            <th style="width: 150px;" class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr data-nome="<?php echo strtolower(htmlspecialchars($usuario['nome'])); ?>" 
                                data-status="<?php echo $usuario['ativo']; ?>">
                                <td><?php echo $usuario['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($usuario['nome']); ?>
                                    <?php if ($usuario['ativo']): ?>
                                        <span class="badge bg-success ms-2">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary ms-2">Inativo</span>
                                    <?php endif; ?>
                                </td>
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
// Filtro de busca por nome
document.getElementById('filtroNome').addEventListener('input', aplicarFiltros);
document.getElementById('filtroStatus').addEventListener('change', aplicarFiltros);

function aplicarFiltros() {
    const filtroNome = document.getElementById('filtroNome').value.toLowerCase();
    const filtroStatus = document.getElementById('filtroStatus').value;
    const linhas = document.querySelectorAll('#tabelaUsuarios tbody tr');
    let totalVisiveis = 0;

    linhas.forEach(linha => {
        const nome = linha.getAttribute('data-nome');
        const status = linha.getAttribute('data-status');
        
        let mostrarNome = true;
        let mostrarStatus = true;

        // Filtro por nome
        if (filtroNome && !nome.includes(filtroNome)) {
            mostrarNome = false;
        }

        // Filtro por status
        if (filtroStatus !== '' && status !== filtroStatus) {
            mostrarStatus = false;
        }

        // Mostrar ou ocultar linha
        if (mostrarNome && mostrarStatus) {
            linha.style.display = '';
            totalVisiveis++;
        } else {
            linha.style.display = 'none';
        }
    });

    // Atualizar contador
    document.getElementById('totalUsuarios').textContent = totalVisiveis;
}

function limparFiltros() {
    document.getElementById('filtroNome').value = '';
    document.getElementById('filtroStatus').value = '';
    aplicarFiltros();
}

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
