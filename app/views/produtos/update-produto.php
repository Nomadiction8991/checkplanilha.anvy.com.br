<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
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
          <input class="form-check-input" type="checkbox" id="imprimir_14_1" name="imprimir_14_1" value="1" <?php echo ($produto['imprimir_14_1'] == 1) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="imprimir_14_1">Imprimir 14.1</label>
        </div>
      </div>

      <!-- Condição 14.1 (apenas uma opção) -->
      <div class="mt-3">
        <label class="form-label d-block">Condição (Relatório 14.1) <small class="text-muted">(escolha uma)</small></label>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="condicao_141" id="condicao_141_1" value="1" <?php echo (($produto['condicao_141'] ?? null) == 1) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="condicao_141_1">
            O bem tem mais de cinco anos de uso e o documento fiscal de aquisição está anexo.
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="condicao_141" id="condicao_141_2" value="2" <?php echo (($produto['condicao_141'] ?? null) == 2) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="condicao_141_2">
            O bem tem mais de cinco anos de uso, porém o documento fiscal de aquisição foi extraviado.
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="condicao_141" id="condicao_141_3" value="3" <?php echo (($produto['condicao_141'] ?? null) == 3) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="condicao_141_3">
            O bem tem até cinco anos de uso e o documento fiscal de aquisição está anexo.
          </label>
        </div>
      </div>

      <!-- Campos da Nota Fiscal (visíveis somente quando condicao_141 = 3) -->
      <div id="camposNota" class="border rounded p-3 mt-3" style="display:none;">
        <h6 class="mb-3">Dados da Nota Fiscal</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label for="numero_nota" class="form-label">Número da Nota Fiscal <span class="text-danger">*</span></label>
            <input type="text" id="numero_nota" name="numero_nota" class="form-control" value="<?php echo htmlspecialchars($produto['numero_nota'] ?? ''); ?>">
            <div class="invalid-feedback">Informe o número da nota.</div>
          </div>
          <div class="col-md-6">
            <label for="data_emissao" class="form-label">Data de Emissão <span class="text-danger">*</span></label>
            <input type="date" id="data_emissao" name="data_emissao" class="form-control" value="<?php echo htmlspecialchars($produto['data_emissao'] ?? ''); ?>">
            <div class="invalid-feedback">Informe a data de emissão.</div>
          </div>
          <div class="col-md-6">
            <label for="valor_nota" class="form-label">Valor <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="number" step="0.01" min="0" id="valor_nota" name="valor_nota" class="form-control" value="<?php echo htmlspecialchars($produto['valor_nota'] ?? ''); ?>">
            </div>
            <div class="invalid-feedback">Informe o valor da nota.</div>
          </div>
          <div class="col-md-6">
            <label for="fornecedor_nota" class="form-label">Fornecedor <span class="text-danger">*</span></label>
            <input type="text" id="fornecedor_nota" name="fornecedor_nota" class="form-control" value="<?php echo htmlspecialchars($produto['fornecedor_nota'] ?? ''); ?>">
            <div class="invalid-feedback">Informe o fornecedor.</div>
          </div>
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
        // Aplicar required dinamicamente quando condicao_141 = 1 ou 3 (ambas exigem nota)
        const cond1 = document.getElementById('condicao_141_1');
        const cond3 = document.getElementById('condicao_141_3');
        const reqFields = ['numero_nota', 'data_emissao', 'valor_nota', 'fornecedor_nota'];
        const mostrarNota = (cond1 && cond1.checked) || (cond3 && cond3.checked);
        reqFields.forEach(id => {
          const el = document.getElementById(id);
          if (el) el.required = mostrarNota;
        });

        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  })();

  // Exibir/ocultar campos da nota conforme seleção da condição 1 ou 3
  (function(){
    const radios = document.querySelectorAll('input[name="condicao_141"]');
    const cond1 = document.getElementById('condicao_141_1');
    const cond3 = document.getElementById('condicao_141_3');
    const box = document.getElementById('camposNota');
    const reqFields = ['numero_nota', 'data_emissao', 'valor_nota', 'fornecedor_nota'];
    
    function toggleNota(){
      const show = (cond1 && cond1.checked) || (cond3 && cond3.checked);
      box.style.display = show ? '' : 'none';
      reqFields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.required = show;
      });
    }
    
    radios.forEach(r => r.addEventListener('change', toggleNota));
    document.addEventListener('DOMContentLoaded', toggleNota);
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
