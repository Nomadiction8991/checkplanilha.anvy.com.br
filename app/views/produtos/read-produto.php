<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação
include PROJECT_ROOT . '/CRUD/READ/produto.php';

$pageTitle = 'Visualizar Produtos';
$backUrl = '../planilhas/view-planilha.php?id=' . urlencode($id_planilha);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuProdutos" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuProdutos">
            <li>
                <a href="./create-produto.php?id=' . urlencode($id_planilha) . '&' . gerarParametrosFiltro(true) . '" class="dropdown-item">
                    <i class="bi bi-plus-lg me-2"></i>Novo Produto
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="../../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

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

<!-- BOTÃO DE EXCLUSÃO EM MASSA (inicialmente oculto) -->
<div id="deleteButtonContainer" class="card mb-3" style="display: none;">
  <div class="card-body">
    <form method="POST" id="deleteForm">
      <input type="hidden" name="id_planilha" value="<?php echo htmlspecialchars($id_planilha); ?>">
      <div id="selectedProducts"></div>
      <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Tem certeza que deseja excluir os produtos selecionados?');">
        <i class="bi bi-trash me-2"></i>
        Excluir <span id="countSelected">0</span> Produto(s) Selecionado(s)
      </button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>
      <i class="bi bi-box-seam me-2"></i>
      Produtos
    </span>
    <span class="badge bg-white text-dark"><?php echo $total_registros ?? 0; ?> itens (pág. <?php echo $pagina; ?>/<?php echo $total_paginas ?: 1; ?>)</span>
  </div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>Produtos</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($produtos)): ?>
          <?php foreach ($produtos as $produto): ?>
            <tr>
              <td>
                <div class="d-flex gap-2">
                  <div class="form-check mt-1">
                    <input class="form-check-input produto-checkbox" type="checkbox" value="<?php echo $produto['id']; ?>" id="produto_<?php echo $produto['id']; ?>">
                  </div>
                  <div class="flex-grow-1">
                    <!-- Linha 1: Descrição -->
                    <div class="fw-semibold mb-2">
                      <?php echo htmlspecialchars($produto['descricao_completa']); ?>
                    </div>
                    <!-- Linha 2: Status e Ações -->
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="d-flex gap-1 flex-wrap">
                        <?php if (!empty($produto['codigo'])): ?>
                          <span class="badge bg-info text-dark"><?php echo htmlspecialchars($produto['codigo']); ?></span>
                        <?php endif; ?>
                        <?php if (isset($produto['condicao_141']) && ($produto['condicao_141'] == 1 || $produto['condicao_141'] == 3)): ?>
                          <span class="badge bg-warning text-dark">Nota</span>
                        <?php endif; ?>
                        <?php if ($produto['imprimir_14_1'] == 1): ?>
                          <span class="badge bg-primary">14.1</span>
                        <?php endif; ?>
                      </div>
                      <div class="btn-group btn-group-sm">
                        <a class="btn btn-outline-primary btn-sm" title="Editar" href="./update-produto.php?id_produto=<?php echo $produto['id']; ?>&id=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(true); ?>">
                          <i class="bi bi-pencil-fill"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td class="text-center text-muted py-4">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const checkboxes = document.querySelectorAll('.produto-checkbox');
  const deleteButtonContainer = document.getElementById('deleteButtonContainer');
  const selectedProductsDiv = document.getElementById('selectedProducts');
  const countSelected = document.getElementById('countSelected');
  const deleteForm = document.getElementById('deleteForm');

  function atualizarContagem() {
    const checados = document.querySelectorAll('.produto-checkbox:checked').length;
    countSelected.textContent = checados;
    
    // Mostrar/ocultar container de exclusão
    if (checados > 0) {
      deleteButtonContainer.style.display = 'block';
    } else {
      deleteButtonContainer.style.display = 'none';
    }
    
    // Atualizar inputs ocultos com IDs selecionados
    selectedProductsDiv.innerHTML = '';
    document.querySelectorAll('.produto-checkbox:checked').forEach(checkbox => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids_produtos[]';
      input.value = checkbox.value;
      selectedProductsDiv.appendChild(input);
    });
  }

  // Adicionar listener em cada checkbox
  checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', atualizarContagem);
  });

  // Enviar form de exclusão via POST
  deleteForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Enviar via AJAX ou formulário tradicional
    const formData = new FormData(deleteForm);
    
    fetch('./delete-produtos.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Recarregar a página
        location.reload();
      } else {
        alert('Erro ao excluir produtos: ' + (data.message || 'Erro desconhecido'));
      }
    })
    .catch(error => {
      console.error('Erro:', error);
      alert('Erro ao excluir produtos');
    });
  });
});
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = PROJECT_ROOT . '/temp_read_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include PROJECT_ROOT . '/layouts/app-wrapper.php';
unlink($tempFile);
?>
