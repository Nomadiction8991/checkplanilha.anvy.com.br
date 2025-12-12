<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // Autenticação

// Apenas admins podem acessar gestão de usuários
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

include __DIR__ . '/../../../app/controllers/read/UsuarioListController.php';

$pageTitle = 'Usuários';
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtro de busca por nome (aplica apenas quando o usuário clica em Buscar)
    var btnBuscar = document.getElementById('btnBuscarUsuarios');
    if (btnBuscar) {
        btnBuscar.addEventListener('click', aplicarFiltros);
    }

    // Permitir Enter no campo para acionar o botão Buscar
    var filtroNomeEl = document.getElementById('filtroNome');
    if (filtroNomeEl) {
        filtroNomeEl.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (btnBuscar) btnBuscar.click();
            }
        });
    }

    function aplicarFiltros() {
        console.log('aplicarFiltros() called');
        const filtroNome = (document.getElementById('filtroNome') ? document.getElementById('filtroNome').value.toLowerCase() : '');
        const filtroStatus = (document.getElementById('filtroStatus') ? document.getElementById('filtroStatus').value : '');
        const linhas = document.querySelectorAll('#tabelaUsuarios tbody tr');
        let totalVisiveis = 0;

        linhas.forEach(linha => {
            const nome = linha.getAttribute('data-nome');
            const email = linha.getAttribute('data-email');
            const status = linha.getAttribute('data-status');
            
            let mostrarNome = true;
            let mostrarStatus = true;

            // Filtro por nome
            if (filtroNome && !(nome.includes(filtroNome) || (email && email.includes(filtroNome)))) {
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

        fetch('../../../app/controllers/delete/UsuarioDeleteController.php', {
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
});
</script>
                            <tr data-nome="<?php echo strtolower(htmlspecialchars($usuario['nome'])); ?>" 
                                data-email="<?php echo strtolower(htmlspecialchars($usuario['email'])); ?>"
                                data-status="<?php echo $usuario['ativo']; ?>">
                                <td>
                                    <div class="d-flex flex-column">
                                        <div class="fw-semibold text-wrap"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                                        <div class="small text-muted text-wrap"><?php echo htmlspecialchars($usuario['email']); ?></div>
                                        <div class="mt-2 d-flex gap-1 flex-wrap justify-content-end">
                                            <a href="./usuario_ver.php?id=<?php echo $usuario['id']; ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($is_self): ?>
                                                <a href="./usuario_editar.php?id=<?php echo $usuario['id']; ?>"
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
// Filtro de busca por nome (aplica apenas quando o usuário clica em Buscar)
var btnBuscar = document.getElementById('btnBuscarUsuarios');
if (btnBuscar) {
    btnBuscar.addEventListener('click', aplicarFiltros);
}

// Permitir Enter no campo para acionar o botão Buscar
var filtroNomeEl = document.getElementById('filtroNome');
if (filtroNomeEl) {
    filtroNomeEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (btnBuscar) btnBuscar.click();
        }
    });
}

function aplicarFiltros() {
    const filtroNome = document.getElementById('filtroNome').value.toLowerCase();
    const filtroStatus = document.getElementById('filtroStatus').value;
    const linhas = document.querySelectorAll('#tabelaUsuarios tbody tr');
    let totalVisiveis = 0;

    linhas.forEach(linha => {
        const nome = linha.getAttribute('data-nome');
        const email = linha.getAttribute('data-email');
        const status = linha.getAttribute('data-status');
        
        let mostrarNome = true;
        let mostrarStatus = true;

        // Filtro por nome
        if (filtroNome && !(nome.includes(filtroNome) || (email && email.includes(filtroNome)))) {
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

    fetch('../../../app/controllers/delete/UsuarioDeleteController.php', {
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
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>

