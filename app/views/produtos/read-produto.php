<?php
include __DIR__ . '/../../../CRUD/READ/produto.php';

$pageTitle = 'Visualizar Produtos';
$backUrl = '../shared/menu.php?id=' . urlencode($id_planilha);
$headerActions = '<a href="./create-produto.php?id=' . urlencode($id_planilha) . '&' . gerarParametrosFiltro(true) . '" class="btn-header-action" title="Novo Produto"><i class="bi bi-plus-lg"></i></a>';

// CSS customizado para garantir exibição dos botões
$customCss = '
.btn-group { display: inline-flex !important; }
.btn-group .btn { display: inline-block !important; visibility: visible !important; }
.table td, .table th { font-size: 0.85rem; }
.fw-semibold { font-size: 0.8rem; }
';

ob_start();
?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-funnel me-2"></i>
    Filtros
  </div>
  <div class="card-body">
    <form method="GET">
      <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_planilha); ?>">

      <!-- Campo principal de busca por descrição -->
      <div class="mb-3">
        <label for="filtro_complemento" class="form-label">
          <i class="bi bi-search me-1"></i>
          Pesquisar por Descrição
        </label>
        <input type="text" id="filtro_complemento" name="filtro_complemento" class="form-control" value="<?php echo htmlspecialchars($filtro_complemento); ?>" placeholder="Digite para buscar...">
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
                <label for="pesquisa_id" class="form-label">ID</label>
                <input type="number" id="pesquisa_id" name="pesquisa_id" class="form-control" value="<?php echo htmlspecialchars($pesquisa_id); ?>" placeholder="Digite o ID">
              </div>

              <div class="mb-3">
                <label for="filtro_tipo_ben" class="form-label">Tipos de Bens</label>
                <select id="filtro_tipo_ben" name="filtro_tipo_ben" class="form-select">
                  <option value="">Todos</option>
                  <?php foreach ($tipos_bens as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>" <?php echo $filtro_tipo_ben == $tipo['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['descricao']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="filtro_bem" class="form-label">Bem</label>
                <select id="filtro_bem" name="filtro_bem" class="form-select">
                  <option value="">Todos</option>
                  <?php foreach ($bem_codigos as $bem): ?>
                    <option value="<?php echo htmlspecialchars($bem['tipo_ben']); ?>" <?php echo $filtro_bem == $bem['tipo_ben'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($bem['tipo_ben']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="filtro_dependencia" class="form-label">Dependência</label>
                <select id="filtro_dependencia" name="filtro_dependencia" class="form-select">
                  <option value="">Todas</option>
                  <?php foreach ($dependencias as $dep): ?>
                    <option value="<?php echo $dep['id']; ?>" <?php echo $filtro_dependencia == $dep['id'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($dep['descricao']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="filtro_status" class="form-label">Status</label>
                <select id="filtro_status" name="filtro_status" class="form-select">
                  <option value="">Todos</option>
                  <option value="com_nota" <?php echo $filtro_status === 'com_nota' ? 'selected' : ''; ?>>Com Nota</option>
                  <option value="com_14_1" <?php echo $filtro_status === 'com_14_1' ? 'selected' : ''; ?>>Com 14.1</option>
                  <option value="sem_status" <?php echo $filtro_status === 'sem_status' ? 'selected' : ''; ?>>Sem Status</option>
                </select>
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
  <div class="card-footer text-muted small">
    <?php echo $total_registros ?? 0; ?> registros encontrados
  </div>
  
</div>

<div class="card">
  <div class="card-header">
    <i class="bi bi-box-seam me-2"></i>
    Produtos
  </div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>Produtos</th>
          <th class="text-center" style="width:110px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($produtos)): ?>
          <?php foreach ($produtos as $produto): ?>
            <tr>
              <td>
                <div class="d-flex align-items-start gap-2">
                  <div class="flex-shrink-0">
                    <?php if (!empty($produto['codigo'])): ?>
                      <div class="badge bg-info text-dark mb-1"><?php echo htmlspecialchars($produto['codigo']); ?></div>
                    <?php endif; ?>
                    <?php if ($produto['possui_nota'] == 1): ?>
                      <span class="badge bg-warning text-dark mt-1">Nota</span>
                    <?php endif; ?>
                    <?php if ($produto['imprimir_14_1'] == 1): ?>
                      <span class="badge bg-primary mt-1">14.1</span>
                    <?php endif; ?>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold">
                      <?php echo htmlspecialchars($produto['descricao_completa']); ?>
                    </div>
                  </div>
                </div>
              </td>
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                  <a class="btn btn-outline-primary" title="Editar" href="./update-produto.php?id_produto=<?php echo $produto['id']; ?>&id=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(true); ?>">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a class="btn btn-outline-danger" title="Excluir" href="./delete-produto.php?id_produto=<?php echo $produto['id']; ?>&id=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(true); ?>">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="2" class="text-center text-muted py-4">
              <i class="bi bi-inbox fs-1 d-block mb-2"></i>
              <?php echo ($pesquisa_id || $filtro_tipo_ben || $filtro_bem || $filtro_complemento || $filtro_dependencia || $filtro_status)
                ? 'Nenhum produto encontrado com os filtros aplicados.'
                : 'Nenhum produto cadastrado para esta planilha.'; ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (($total_paginas ?? 1) > 1): ?>
    <div class="card-footer">
      <nav>
        <ul class="pagination justify-content-center mb-0">
          <?php
            $pagina_inicial = max(1, $pagina - 1);
            $pagina_final = min($total_paginas, $pagina + 1);
            if ($pagina_final - $pagina_inicial < 2) {
              if ($pagina_inicial == 1 && $total_paginas >= 3) $pagina_final = 3;
              elseif ($pagina_final == $total_paginas && $total_paginas >= 3) $pagina_inicial = $total_paginas - 2;
            }
          ?>
          <?php if ($pagina > 2): ?>
            <li class="page-item">
              <a class="page-link" href="?id=<?php echo $id_planilha; ?>&pagina=1&<?php echo gerarParametrosFiltro(); ?>" aria-label="Primeira">
                <span aria-hidden="true">«</span>
              </a>
            </li>
          <?php endif; ?>

          <?php for ($i = $pagina_inicial; $i <= $pagina_final; $i++): ?>
            <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
              <a class="page-link" href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $i; ?>&<?php echo gerarParametrosFiltro(); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>

          <?php if ($pagina < $total_paginas - 1): ?>
            <li class="page-item">
              <a class="page-link" href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $total_paginas; ?>&<?php echo gerarParametrosFiltro(); ?>" aria-label="Última">
                <span aria-hidden="true">»</span>
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_read_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
