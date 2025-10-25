<?php
include __DIR__ . '/../../../CRUD/READ/produto.php';

$pageTitle = 'Visualizar Produtos';
$backUrl = '../shared/menu.php?id=' . urlencode($id_planilha);
$headerActions = '<a href="./create-produto.php?id=' . urlencode($id_planilha) . '&' . gerarParametrosFiltro(true) . '" class="btn-header-action" title="Novo Produto"><i class="bi bi-plus-lg"></i></a>';

ob_start();
?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-funnel me-2"></i>
    Filtros
  </div>
  <div class="card-body">
    <form method="GET" class="row g-2">
      <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_planilha); ?>">

      <div class="col-12">
        <label for="pesquisa_id" class="form-label">ID</label>
        <input type="number" id="pesquisa_id" name="pesquisa_id" class="form-control" value="<?php echo htmlspecialchars($pesquisa_id); ?>" placeholder="Digite o ID">
      </div>

      <div class="col-12">
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

      <div class="col-12">
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

      <div class="col-12">
        <label for="filtro_complemento" class="form-label">Complemento</label>
        <input type="text" id="filtro_complemento" name="filtro_complemento" class="form-control" value="<?php echo htmlspecialchars($filtro_complemento); ?>" placeholder="Pesquisar no complemento">
      </div>

      <div class="col-12">
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

      <div class="col-12">
        <label for="filtro_status" class="form-label">Status</label>
        <select id="filtro_status" name="filtro_status" class="form-select">
          <option value="">Todos</option>
          <option value="com_nota" <?php echo $filtro_status === 'com_nota' ? 'selected' : ''; ?>>Com Nota</option>
          <option value="com_14_1" <?php echo $filtro_status === 'com_14_1' ? 'selected' : ''; ?>>Com 14.1</option>
          <option value="sem_status" <?php echo $filtro_status === 'sem_status' ? 'selected' : ''; ?>>Sem Status</option>
        </select>
      </div>

      <div class="col-12 d-grid">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-search me-2"></i>Filtrar
        </button>
      </div>
    </form>
  </div>
  <div class="card-footer text-muted small">
    <?php echo $total_registros ?? 0; ?> registros encontrados
  </div>
  
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th class="text-center" style="width:70px;">ID</th>
          <th>Descrição</th>
          <th class="text-center" style="width:110px;">Status</th>
          <th class="text-center" style="width:110px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($produtos)): ?>
          <?php foreach ($produtos as $produto): ?>
            <tr>
              <td class="text-center"><span class="badge bg-secondary">#<?php echo htmlspecialchars($produto['id']); ?></span></td>
              <td>
                <div class="fw-semibold">
                  <?php 
                    echo htmlspecialchars($produto['tipo_codigo'] . ' - ' . $produto['tipo_descricao']);
                    echo ' [' . htmlspecialchars($produto['tipo_ben']) . ']';
                  ?>
                </div>
                <div class="text-muted small">
                  <?php echo htmlspecialchars($produto['complemento']); ?>
                  <?php if (!empty($produto['dependencia_descricao'])): ?>
                    <span class="ms-1">(<?php echo htmlspecialchars($produto['dependencia_descricao']); ?>)</span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="text-center">
                <?php if ($produto['possui_nota'] == 1): ?>
                  <span class="badge bg-warning text-dark">Nota</span>
                <?php endif; ?>
                <?php if ($produto['imprimir_14_1'] == 1): ?>
                  <span class="badge bg-primary">14.1</span>
                <?php endif; ?>
                <?php if ($produto['possui_nota'] == 0 && $produto['imprimir_14_1'] == 0): ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="btn-group">
                  <a class="btn btn-sm btn-outline-success" title="Editar" href="./update-produto.php?id_produto=<?php echo $produto['id']; ?>&id=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(true); ?>">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a class="btn btn-sm btn-outline-danger" title="Excluir" href="./delete-produto.php?id_produto=<?php echo $produto['id']; ?>&id=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(true); ?>">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" class="text-center text-muted py-4">
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
