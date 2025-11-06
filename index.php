<?php
require_once __DIR__ . '/auth.php'; // Verificar autenticação
require_once __DIR__ . '/CRUD/conexao.php';
require_once __DIR__ . '/app/functions/comum_functions.php';

$pageTitle = "Anvy - Seleção de Comum";
$backUrl = null;

ob_start();
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="card border-0 shadow-sm mb-4 bg-primary text-white">
                <div class="card-body text-center py-4">
                    <i class="bi bi-building fs-1 mb-3 d-block"></i>
                    <h1 class="card-title mb-2">Selecione um Comum</h1>
                    <p class="card-text mb-0">Escolha uma instituição para gerenciar suas planilhas</p>
                </div>
            </div>

            <!-- Filtro de pesquisa -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" id="filtro-comum" placeholder="Buscar por código, descrição ou cidade...">
                    </div>
                </div>
            </div>

            <!-- Lista de comuns -->
            <div id="lista-comuns" class="row g-3">
                <?php
                try {
                    $comums = obter_todos_comuns($conexao);
                    
                    if (empty($comums)) {
                        echo '<div class="col-12"><div class="alert alert-info text-center py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p>Nenhum comum cadastrado</p>
                              </div></div>';
                    } else {
                        foreach ($comums as $comum) {
                            $planilhas_count = contar_planilhas_por_comum($conexao, $comum['id']);
                            $produtos_count = contar_produtos_por_comum($conexao, $comum['id']);
                            
                            echo '<div class="col-lg-6 col-xl-4 comum-item" data-id="' . $comum['id'] . '" 
                                      data-codigo="' . htmlspecialchars($comum['codigo']) . '" 
                                      data-descricao="' . htmlspecialchars($comum['descricao']) . '" 
                                      data-cidade="' . htmlspecialchars($comum['cidade']) . '">';
                            echo '  <div class="card h-100 cursor-pointer card-hover shadow-sm" 
                                       onclick="selecionarComum(' . $comum['id'] . ', \'' . addslashes(htmlspecialchars($comum['descricao'])) . '\')">';
                            echo '    <div class="card-body">';
                            echo '      <h5 class="card-title mb-2">';
                            echo '        <span class="badge bg-primary">BR ' . str_pad($comum['codigo'], 8, '0', STR_PAD_LEFT) . '</span>';
                            echo '      </h5>';
                            echo '      <p class="card-text text-dark fw-bold mb-2">' . htmlspecialchars($comum['descricao']) . '</p>';
                            echo '      <small class="text-muted d-block mb-2">';
                            echo '        <i class="bi bi-geo-alt me-1"></i>' . htmlspecialchars($comum['cidade']);
                            if (!empty($comum['administracao'])) {
                                echo '<br><i class="bi bi-building me-1"></i>' . htmlspecialchars($comum['administracao']);
                            }
                            echo '      </small>';
                            echo '    </div>';
                            echo '    <div class="card-footer bg-transparent border-top">';
                            echo '      <div class="row text-center text-sm">';
                            echo '        <div class="col-6">';
                            echo '          <small class="d-block text-muted">Planilhas</small>';
                            echo '          <strong>' . $planilhas_count . '</strong>';
                            echo '        </div>';
                            echo '        <div class="col-6">';
                            echo '          <small class="d-block text-muted">Produtos</small>';
                            echo '          <strong>' . $produtos_count . '</strong>';
                            echo '        </div>';
                            echo '      </div>';
                            echo '    </div>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="col-12"><div class="alert alert-danger">Erro ao carregar comuns: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.card-hover {
    transition: all 0.3s ease;
    cursor: pointer;
}

.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
}

.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
function selecionarComum(id, descricao) {
    // Redirecionar para página de planilhas do comum
    window.location.href = 'app/views/comuns/listar-planilhas.php?comum_id=' + id;
}

// Filtro de pesquisa
document.getElementById('filtro-comum').addEventListener('keyup', function(e) {
    const termo = this.value.toLowerCase();
    const items = document.querySelectorAll('.comum-item');
    
    items.forEach(item => {
        const codigo = item.dataset.codigo.toLowerCase();
        const descricao = item.dataset.descricao.toLowerCase();
        const cidade = item.dataset.cidade.toLowerCase();
        
        const visivel = codigo.includes(termo) || descricao.includes(termo) || cidade.includes(termo);
        item.style.display = visivel ? '' : 'none';
    });
});
</script>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/temp_index_content.php';
file_put_contents($contentFile, $contentHtml);

// Incluir layout principal
require_once 'app/views/layouts/main-layout.php';

// Limpar arquivo temporário
@unlink($contentFile);
?>
