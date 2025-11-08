<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação

// Apenas admins podem acessar gestão de usuários
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

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
            <div class="col-md-8">
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
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-people me-2"></i>
            Lista de Usuários
        </span>
        <span class="badge bg-white text-dark"><?php echo $total_registros; ?> itens (pág. <?php echo $pagina; ?>/<?php echo $total_paginas ?: 1; ?>)</span>
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
                            <th>Usuário</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php
                                $telefone_limpo = preg_replace('/\D/','', $usuario['telefone'] ?? '');
                                $wa_link = ($telefone_limpo && (strlen($telefone_limpo) === 10 || strlen($telefone_limpo) === 11))
                                    ? ('https://wa.me/55' . $telefone_limpo)
                                    : null;
                                $loggedId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
                                $is_self = $loggedId === (int)$usuario['id'];
                            ?>
                            <tr data-nome="<?php echo strtolower(htmlspecialchars($usuario['nome'])); ?>" 
                                data-status="<?php echo $usuario['ativo']; ?>">
                                <td>
                                    <div class="d-flex flex-column">
                                        <div class="fw-semibold text-wrap"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                                        <div class="small text-muted text-wrap"><?php echo htmlspecialchars($usuario['email']); ?></div>
                                        <div class="mt-2 d-flex gap-1 flex-wrap">
                                            <a href="./ver-usuario.php?id=<?php echo $usuario['id']; ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($is_self): ?>
                                                <a href="./editar-usuario.php?id=<?php echo $usuario['id']; ?>"
                                                   class="btn btn-sm btn-outline-primary" title="Editar meu perfil">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($wa_link): ?>
                                                <a href="<?php echo $wa_link; ?>" target="_blank" rel="noopener" 
                                                   class="btn btn-sm btn-outline-success" title="WhatsApp">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
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

<?php if($total_paginas > 1): ?>
<nav class="mt-3" aria-label="Paginação usuários">
  <ul class="pagination pagination-sm justify-content-center mb-0">
    <?php if($pagina > 1): ?>
    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$pagina-1])); ?>">&laquo;</a></li>
    <?php endif; ?>
    <?php $ini = max(1,$pagina-2); $fim = min($total_paginas,$pagina+2); for($i=$ini;$i<=$fim;$i++): ?>
      <li class="page-item <?php echo $i==$pagina?'active':''; ?>">
        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$i])); ?>"><?php echo $i; ?></a>
      </li>
    <?php endfor; ?>
    <?php if($pagina < $total_paginas): ?>
    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$pagina+1])); ?>">&raquo;</a></li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>

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

    // Atualizar contador (se existir no layout)
    const totalEl = document.getElementById('totalUsuarios');
    if (totalEl) totalEl.textContent = totalVisiveis;
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
