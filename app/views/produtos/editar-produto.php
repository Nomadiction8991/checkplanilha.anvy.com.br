<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/UPDATE/editar-produto.php';

$pageTitle = "Editar Produto";
$backUrl = getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status);

ob_start();
?>

<style>
.text-uppercase-input {
    text-transform: uppercase;
}
</style>

<script>
// Mapeamento de tipos de bens e suas opções de bem
const tiposBensOpcoes = <?php echo json_encode(array_reduce($tipos_bens, function($carry, $item) {
    // Separar opções por / se houver
    $opcoes = [];
    if (!empty($item['descricao'])) {
        $partes = explode('/', $item['descricao']);
        $opcoes = array_map('trim', $partes);
    }
    $carry[$item['id']] = [
        'codigo' => $item['codigo'],
        'descricao' => $item['descricao'],
        'opcoes' => $opcoes
    ];
    return $carry;
}, [])); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const selectTipoBem = document.getElementById('novo_tipo_bem_id');
    const selectBem = document.getElementById('novo_bem');
    
    // Função para atualizar opções de Bem baseado no Tipo de Bem selecionado
    function atualizarOpcoesBem() {
        const tipoBemId = selectTipoBem.value;
        
        if (!tipoBemId) {
            // Desabilitar e limpar
            selectBem.disabled = true;
            selectBem.innerHTML = '<option value="">-- Escolha o Tipo de Bem acima --</option>';
            return;
        }
        
        const opcoes = tiposBensOpcoes[tipoBemId]?.opcoes || [];
        
        if (opcoes.length > 1) {
            // Tem múltiplas opções separadas por /
            selectBem.disabled = false;
            selectBem.innerHTML = '<option value="">-- Selecione --</option>';
            opcoes.forEach(opcao => {
                const opt = document.createElement('option');
                opt.value = opcao.toUpperCase();
                opt.textContent = opcao.toUpperCase();
                selectBem.appendChild(opt);
            });
        } else if (opcoes.length === 1) {
            // Apenas uma opção, preencher automaticamente
            selectBem.disabled = false;
            selectBem.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = opcoes[0].toUpperCase();
            opt.textContent = opcoes[0].toUpperCase();
            opt.selected = true;
            selectBem.appendChild(opt);
        } else {
            // Sem opções, campo livre
            selectBem.disabled = true;
            selectBem.innerHTML = '<option value="">-- Não aplicável --</option>';
        }
    }
    
    // Listener para mudança de Tipo de Bem
    selectTipoBem.addEventListener('change', atualizarOpcoesBem);
    
    // Inicializar estado
    atualizarOpcoesBem();
    
    // Converter inputs para uppercase automaticamente
    document.querySelectorAll('.text-uppercase-input').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });

    // Pré-preencher BEM usando o valor já processado pelo controller (editado ou original)
    const bemPrefill = '<?php echo !empty($novo_bem) ? strtoupper(addslashes($novo_bem)) : ''; ?>';
    if (bemPrefill) {
        if (selectTipoBem.value) {
            atualizarOpcoesBem();
            for (const opt of selectBem.options) {
                if (opt.value === bemPrefill) { opt.selected = true; break; }
            }
        } else {
            selectBem.innerHTML = '<option value="'+bemPrefill+'" selected>'+bemPrefill+'</option>';
            selectBem.disabled = true;
        }
    }
});
</script>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-info-circle me-2"></i>
        Informações Atuais
    </div>
    <div class="card-body">
        <div class="row g-2 small">
            <div class="col-12">
                <strong>Código:</strong> <?php echo htmlspecialchars($produto['codigo'] ?? ''); ?>
            </div>
            <div class="col-12">
                <strong>Bem:</strong> <?php echo htmlspecialchars($produto['ben'] ?? ''); ?>
            </div>
            <div class="col-12">
                <strong>Complemento:</strong> <?php echo htmlspecialchars($produto['complemento'] ?? ''); ?>
            </div>
            <div class="col-12">
                <strong>Dependência:</strong> <?php echo htmlspecialchars($produto['dependencia_descricao'] ?? $produto['dependencia_id'] ?? ''); ?>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info small">
    <strong>ℹ️ Informação:</strong> Campos em branco = sem alteração. 
    <br><strong>⚠️ Atenção:</strong> Editar marca automaticamente para impressão.
    <br><strong>🔤 Nota:</strong> Todos os campos serão convertidos para MAIÚSCULAS automaticamente.
</div>

<form method="POST">
    <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
    <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
    <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
    <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">

    <div class="card mb-3">
        <div class="card-body">
            <!-- TIPO DE BEM -->
            <div class="mb-3">
                <label for="novo_tipo_bem_id" class="form-label">
                    <i class="bi bi-tag me-1"></i>
                    Tipo de Bem
                </label>
                <select class="form-select" id="novo_tipo_bem_id" name="novo_tipo_bem_id">
                    <option value="">-- Não alterar --</option>
                    <?php foreach ($tipos_bens as $tb): ?>
                        <option value="<?php echo $tb['id']; ?>" 
                            <?php echo (isset($novo_tipo_bem_id) && $novo_tipo_bem_id == $tb['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tb['codigo'] . ' - ' . $tb['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Selecione o tipo de bem para desbloquear o campo "Bem"</div>
            </div>

            <!-- BEM (sempre visível, desabilitado até escolher tipo) -->
            <div class="mb-3" id="div_bem">
                <label for="novo_bem" class="form-label">
                    <i class="bi bi-box me-1"></i>
                    Bem
                </label>
                <select class="form-select text-uppercase-input" id="novo_bem" name="novo_bem" disabled>
                    <option value="">-- Escolha o Tipo de Bem acima --</option>
                </select>
                <div class="form-text">Fica bloqueado até selecionar o Tipo de Bem</div>
            </div>

            <!-- COMPLEMENTO -->
            <div class="mb-3">
                <label for="novo_complemento" class="form-label">
                    <i class="bi bi-card-text me-1"></i>
                    Complemento
                </label>
                <textarea class="form-control text-uppercase-input" id="novo_complemento" name="novo_complemento" 
                          rows="3" placeholder="CARACTERÍSTICA + MARCA + MEDIDAS"><?php echo htmlspecialchars($novo_complemento ?? ''); ?></textarea>
                <div class="form-text">Deixe em branco para não alterar. Ex: COR PRETA + MARCA XYZ + 1,80M X 0,80M</div>
            </div>

            <!-- DEPENDÊNCIA -->
            <div class="mb-3">
                <label for="nova_dependencia_id" class="form-label">
                    <i class="bi bi-building me-1"></i>
                    Dependência
                </label>
                <select class="form-select" id="nova_dependencia_id" name="nova_dependencia_id">
                    <option value="">-- Não alterar --</option>
                    <?php foreach ($dependencias as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>" 
                            <?php echo (isset($nova_dependencia_id) && $nova_dependencia_id == $dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        Salvar Alterações
    </button>
</form>

<div class="mt-3">
    <a href="./limpar-edicoes.php?id=<?php echo $id_planilha; ?>&id_produto=<?php echo $id_produto; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"
       class="btn btn-outline-danger w-100"
       onclick="return confirm('Tem certeza que deseja limpar as edições deste produto?');">
        <i class="bi bi-trash3 me-2"></i>
        Limpar Edições
    </a>
    <div class="form-text mt-1">Remove todos os campos editados e desmarca para impressão.</div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_produto_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
