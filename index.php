<?php
require_once 'CRUD/READ/index.php';

// Configurações da página
$pageTitle = "Anvy - Planilhas";
$backUrl = null; // Sem botão voltar na home
$headerActions = '
    <a href="app/views/planilhas/importar-planilha.php" class="btn-header-action" title="Importar Planilha">
        <i class="bi bi-plus-lg fs-5"></i>
    </a>
';

// Iniciar buffer para capturar o conteúdo
ob_start();
?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>
        Filtros
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <!-- Campo principal -->
            <div class="mb-3">
                <label class="form-label" for="comum">
                    <i class="bi bi-search me-1"></i>
                    Pesquisar por Comum
                </label>
                <input type="text" class="form-control" id="comum" name="comum" 
                       value="<?php echo htmlspecialchars($filtro_comum ?? ''); ?>" 
                       placeholder="Digite para buscar...">
            </div>

            <!-- Filtros Avançados recolhíveis -->
            <div class="accordion" id="filtrosAvancados">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros">
                            <i class="bi bi-sliders me-2"></i>
                            Filtros Avançados
                        </button>
                    </h2>
                    <div id="collapseFiltros" class="accordion-collapse collapse" data-bs-parent="#filtrosAvancados">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label class="form-label" for="status">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Status
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <?php foreach ($status_options as $status): ?>
                                        <option value="<?php echo $status; ?>"
                                            <?php echo ($filtro_status ?? '') === $status ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="ativo">
                                    <i class="bi bi-eye me-1"></i>
                                    Exibir
                                </label>
                                <select class="form-select" id="ativo" name="ativo">
                                    <option value="1" <?php echo ($filtro_ativo ?? '1') === '1' ? 'selected' : ''; ?>>Ativos</option>
                                    <option value="0" <?php echo ($filtro_ativo ?? '1') === '0' ? 'selected' : ''; ?>>Inativos</option>
                                    <option value="todos" <?php echo ($filtro_ativo ?? '1') === 'todos' ? 'selected' : ''; ?>>Todos</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label" for="data_inicio">Data Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                       value="<?php echo htmlspecialchars($filtro_data_inicio ?? ''); ?>">
                            </div>
                            <div>
                                <label class="form-label" for="data_fim">Data Fim</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" 
                                       value="<?php echo htmlspecialchars($filtro_data_fim ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3">
                <i class="bi bi-search me-2"></i>
                Filtrar
            </button>
        </form>
    </div>
    
</div>

<!-- Legenda -->
<div class="card mb-3">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <span class="badge bg-secondary">
                <i class="bi bi-circle-fill me-1"></i>
                Pendente
            </span>
            <span class="badge bg-warning text-dark">
                <i class="bi bi-circle-fill me-1"></i>
                Em Execução
            </span>
            <span class="badge bg-success">
                <i class="bi bi-circle-fill me-1"></i>
                Concluído
            </span>
            <span class="badge bg-danger">
                <i class="bi bi-circle-fill me-1"></i>
                Inativo
            </span>
        </div>
    </div>
</div>

<!-- Listagem -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-file-earmark-spreadsheet me-2"></i>
            Planilhas
        </span>
        <span class="badge bg-white text-dark"><?php echo count($planilhas ?? []); ?> itens</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th style="width: 55%;">Comum</th>
                    <th style="width: 20%;" class="text-center">Data</th>
                    <th style="width: 25%;" class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($planilhas) && count($planilhas) > 0): ?>
                    <?php foreach ($planilhas as $planilha): ?>
                    <?php
                    // Classe da linha baseada no status (em vez de coluna de status)
                    $row_class = '';
                    if (($planilha['ativo'] ?? 1) == 0) {
                        $row_class = 'table-secondary'; // Inativa
                    } else {
                        switch (strtolower($planilha['status'] ?? '')) {
                            case 'concluido':
                            case 'concluído':
                                $row_class = 'table-success';
                                break;
                            case 'execucao':
                            case 'em execução':
                            case 'em execucao':
                                $row_class = 'table-warning';
                                break;
                            case 'pendente':
                            default:
                                $row_class = ''; // sem cor especial
                                break;
                        }
                    }

                    // Formatar data
                    $data_posicao = '';
                    if (!empty($planilha['data_posicao']) && $planilha['data_posicao'] != '0000-00-00') {
                        $data_posicao = date('d/m/Y', strtotime($planilha['data_posicao']));
                    }
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td>
                            <div class="<?php echo $planilha['ativo'] == 0 ? 'text-muted' : 'fw-semibold'; ?>">
                                <?php echo htmlspecialchars($planilha['comum']); ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <small><?php echo $data_posicao; ?></small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="app/views/planilhas/view-planilha.php?id=<?php echo $planilha['id']; ?>" 
                                   class="btn btn-outline-primary" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="app/views/planilhas/editar-planilha.php?id=<?php echo $planilha['id']; ?>" 
                                   class="btn btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                            <span class="text-muted">Nenhuma planilha encontrada</span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
<?php if (isset($total_paginas) && $total_paginas > 1): ?>
<nav aria-label="Navegação de página" class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <?php if ($pagina > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&comum=<?php echo urlencode($filtro_comum ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>&ativo=<?php echo $filtro_ativo ?? '1'; ?>&data_inicio=<?php echo urlencode($filtro_data_inicio ?? ''); ?>&data_fim=<?php echo urlencode($filtro_data_fim ?? ''); ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $i; ?>&comum=<?php echo urlencode($filtro_comum ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>&ativo=<?php echo $filtro_ativo ?? '1'; ?>&data_inicio=<?php echo urlencode($filtro_data_inicio ?? ''); ?>&data_fim=<?php echo urlencode($filtro_data_fim ?? ''); ?>">
                <?php echo $i; ?>
            </a>
        </li>
        <?php endfor; ?>
        
        <?php if ($pagina < $total_paginas): ?>
        <li class="page-item">
            <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&comum=<?php echo urlencode($filtro_comum ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>&ativo=<?php echo $filtro_ativo ?? '1'; ?>&data_inicio=<?php echo urlencode($filtro_data_inicio ?? ''); ?>&data_fim=<?php echo urlencode($filtro_data_fim ?? ''); ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php
// Capturar o conteúdo
$contentHtml = ob_get_clean();

// Criar arquivo temporário com o conteúdo
file_put_contents(__DIR__ . '/temp_index_content.php', $contentHtml);
$contentFile = __DIR__ . '/temp_index_content.php';

// Renderizar o layout
include __DIR__ . '/app/views/layouts/app-wrapper.php';

// Limpar arquivo temporário
unlink(__DIR__ . '/temp_index_content.php');
?>
