<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
include __DIR__ . '/../../../CRUD/CREATE/produto.php';

$pageTitle = 'Cadastrar Produto';
$backUrl = './read-produto.php?comum_id=' . urlencode($comum_id) . '&' . gerarParametrosFiltro();

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

<form method="POST" id="form-produto" class="needs-validation" novalidate>
  <div class="card mb-3">
    <div class="card-body">
      <div class="mb-3">
        <label for="codigo" class="form-label">Código <span class="text-muted">(opcional)</span></label>
        <input type="text" id="codigo" name="codigo" class="form-control" value="<?php echo htmlspecialchars($_POST['codigo'] ?? ''); ?>" placeholder="Código gerado por outro sistema">
        <div class="form-text">Campo opcional. Código externo que não será incluído na descrição completa.</div>
      </div>

      <div class="mb-3">
        <label for="multiplicador" class="form-label">Multiplicador</label>
        <input type="number" id="multiplicador" name="multiplicador" class="form-control" min="1" value="<?php echo htmlspecialchars($_POST['multiplicador'] ?? '1'); ?>" required>
        <div class="invalid-feedback">Informe o multiplicador.</div>
      </div>

      <div class="mb-3">
        <label for="id_tipo_ben" class="form-label">Tipos de Bens</label>
        <select id="id_tipo_ben" name="id_tipo_ben" class="form-select" required>
          <option value="">Selecione um tipo de bem</option>
          <?php foreach ($tipos_bens as $tipo): ?>
            <option value="<?php echo $tipo['id']; ?>" data-descricao="<?php echo htmlspecialchars($tipo['descricao']); ?>"
              <?php echo (isset($_POST['id_tipo_ben']) && $_POST['id_tipo_ben'] == $tipo['id']) ? 'selected' : ''; ?>>
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
        <textarea id="complemento" name="complemento" class="form-control" rows="3" placeholder="Digite o complemento do produto" required><?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?></textarea>
        <div class="invalid-feedback">Informe o complemento.</div>
      </div>

      <div class="mb-3">
        <label for="id_dependencia" class="form-label">Dependência</label>
        <select id="id_dependencia" name="id_dependencia" class="form-select" required>
          <option value="">Selecione uma dependência</option>
          <?php foreach ($dependencias as $dep): ?>
            <option value="<?php echo $dep['id']; ?>" <?php echo (isset($_POST['id_dependencia']) && $_POST['id_dependencia'] == $dep['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($dep['descricao']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Selecione a dependência.</div>
      </div>

      <div class="mb-2">
        <label class="form-label">Status</label>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1" <?php echo (isset($_POST['imprimir_14_1']) && $_POST['imprimir_14_1'] == 1) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="imprimir_14_1">Imprimir 14.1</label>
        </div>
      </div>

      <!-- Campos de Condição 14.1 e Nota Fiscal removidos a pedido -->
    </div>
  </div>

  <button type="submit" class="btn btn-primary w-100">
    <i class="bi bi-save me-2"></i>
    Cadastrar Produto
  </button>
</form>

<script>
  // Dependência do select "Bem" em função do "Tipos de Bens"
  const selectTipoBen = document.getElementById('id_tipo_ben');
  const selectBem = document.getElementById('tipo_ben');

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
        <?php if (isset($_POST['tipo_ben']) && isset($_POST['id_tipo_ben'])): ?>
        if (opcao === '<?php echo $_POST['tipo_ben']; ?>' && selectTipoBen.value === '<?php echo $_POST['id_tipo_ben']; ?>') {
          option.selected = true;
        }
        <?php endif; ?>
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
$tempFile = __DIR__ . '/../../../temp_create_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
