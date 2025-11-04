<?php
require_once '../../../auth.php'; // Autenticação
include __DIR__ . '/../../../CRUD/UPDATE/produto.php';

$pageTitle = 'Editar Produto';
$backUrl = './read-produto.php?id=' . urlencode($id_planilha) . '&' . gerarParametrosFiltro();

ob_start();
?>

<?php if (!empty($erros)): ?>
  <div class="alert alert-danger">
    <strong>Erros encontrados:</strong>
    <ul class="mb-0">
      <?php foreach ($erros as $erro): ?>
        <li><?php echo htmlspecialchars($erro); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-box-seam me-2"></i>
    Produto #<?php echo htmlspecialchars($produto['id'] ?? ''); ?>
  </div>
  <div class="card-body small text-muted">
    <div><strong>Código:</strong> <?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></div>
    <div><strong>Nome atual:</strong> <?php echo htmlspecialchars($produto['nome'] ?? ''); ?></div>
    <div><strong>Dependência atual:</strong> <?php echo htmlspecialchars($produto['dependencia'] ?? ''); ?></div>
  </div>
</div>

<form method="POST" id="form-produto" class="needs-validation" novalidate>
  <div class="card mb-3">
    <div class="card-body">
      <div class="mb-3">
        <label for="codigo" class="form-label">Código <span class="text-muted">(opcional)</span></label>
        <input type="text" id="codigo" name="codigo" class="form-control" value="<?php echo htmlspecialchars($produto['codigo'] ?? ''); ?>" placeholder="Código gerado por outro sistema">
        <div class="form-text">Campo opcional. Código externo que não será incluído na descrição completa.</div>
      </div>

      <div class="mb-3">
        <label for="quantidade" class="form-label">Quantidade</label>
        <input type="number" id="quantidade" name="quantidade" class="form-control" min="1" value="<?php echo htmlspecialchars($produto['quantidade'] ?? '1'); ?>" required>
        <div class="invalid-feedback">Informe a quantidade.</div>
      </div>

      <div class="mb-3">
        <label for="id_tipo_ben" class="form-label">Tipos de Bens</label>
        <select id="id_tipo_ben" name="id_tipo_ben" class="form-select" required>
          <option value="">Selecione um tipo de bem</option>
          <?php foreach ($tipos_bens as $tipo): ?>
            <option value="<?php echo $tipo['id']; ?>" data-descricao="<?php echo htmlspecialchars($tipo['descricao']); ?>"
              <?php echo ($produto['id_tipo_ben'] == $tipo['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['descricao']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Selecione um tipo de bem.</div>
      </div>

      <div class="mb-3">
        <label for="tipo_ben" class="form-label">Bem</label>
        <select id="tipo_ben" name="tipo_ben" class="form-select" required>
          <option value="">Primeiro selecione um tipo de bem</option>
        </select>
        <div class="invalid-feedback">Selecione um bem.</div>
      </div>

      <div class="mb-3">
        <label for="complemento" class="form-label">Complemento</label>
        <textarea id="complemento" name="complemento" class="form-control" rows="3" placeholder="Digite o complemento do produto" required><?php echo htmlspecialchars($produto['complemento'] ?? ''); ?></textarea>
        <div class="invalid-feedback">Informe o complemento.</div>
      </div>

      <div class="mb-3">
        <label for="id_dependencia" class="form-label">Dependência</label>
        <select id="id_dependencia" name="id_dependencia" class="form-select" required>
          <option value="">Selecione uma dependência</option>
          <?php foreach ($dependencias as $dep): ?>
            <option value="<?php echo $dep['id']; ?>" <?php echo ($produto['id_dependencia'] == $dep['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($dep['descricao']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Selecione a dependência.</div>
      </div>

      <div class="mb-2">
        <label class="form-label">Status</label>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="possui_nota" name="possui_nota" value="1" <?php echo ($produto['possui_nota'] == 1) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="possui_nota">Possui Nota</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1" <?php echo ($produto['imprimir_14_1'] == 1) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="imprimir_14_1">Imprimir 14.1</label>
        </div>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary w-100">
    <i class="bi bi-save me-2"></i>
    Atualizar Produto
  </button>
</form>

<script>
  const selectTipoBen = document.getElementById('id_tipo_ben');
  const selectBem = document.getElementById('tipo_ben');
  const produtoBem = <?php echo json_encode($produto['tipo_ben'] ?? ''); ?>;

  function separarOpcoesPorBarra(descricao) {
    return descricao.split('/').map(item => item.trim()).filter(item => item !== '');
  }

  function atualizarOpcoesBem() {
    const selectedOption = selectTipoBen.options[selectTipoBen.selectedIndex];
    const descricao = selectedOption ? (selectedOption.getAttribute('data-descricao') || '') : '';
    selectBem.innerHTML = '';
    if (selectTipoBen.value && descricao) {
      const opcoes = separarOpcoesPorBarra(descricao);
      const optionPadrao = document.createElement('option');
      optionPadrao.value = '';
      optionPadrao.textContent = 'Selecione um bem';
      selectBem.appendChild(optionPadrao);
      opcoes.forEach(opcao => {
        const option = document.createElement('option');
        option.value = opcao;
        option.textContent = opcao;
        if (opcao === produtoBem) option.selected = true;
        selectBem.appendChild(option);
      });
      selectBem.disabled = false;
    } else {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'Primeiro selecione um tipo de bem';
      selectBem.appendChild(option);
      selectBem.disabled = true;
    }
  }

  selectTipoBen.addEventListener('change', atualizarOpcoesBem);
  document.addEventListener('DOMContentLoaded', atualizarOpcoesBem);

  // Validação Bootstrap
  (() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_update_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
