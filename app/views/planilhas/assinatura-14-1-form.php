<?php
require_once PROJECT_ROOT . '/auth.php';
require_once PROJECT_ROOT . '/CRUD/conexao.php';

$id_produto = $_GET['id'] ?? null;
$ids_produtos = $_GET['ids'] ?? null;
$id_planilha = $_GET['id_planilha'] ?? null;

// Determinar se é múltiplo ou único
$produtos_ids = [];
if ($ids_produtos) {
    $produtos_ids = array_map('intval', explode(',', $ids_produtos));
} elseif ($id_produto) {
    $produtos_ids = [intval($id_produto)];
}

if (empty($produtos_ids) || !$id_planilha) {
    header('Location: ../../../index.php');
    exit;
}

$multiplo = count($produtos_ids) > 1;

// Buscar dados dos produtos
$placeholders = implode(',', array_fill(0, count($produtos_ids), '?'));
$sql = "SELECT p.id_produto, p.descricao_completa, p.condicao_14_1, p.nota_numero, p.nota_data, p.nota_valor, p.nota_fornecedor, p.doador_conjugue_id, tb.descricao as tipo_descricao, u.nome as doador_nome FROM produtos p LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id LEFT JOIN usuarios u ON p.doador_conjugue_id = u.id WHERE p.id_produto IN ($placeholders)";
$stmt = $conexao->prepare($sql);
$stmt->execute($produtos_ids);
$produtos = $stmt->fetchAll();

if (empty($produtos)) {
    header('Location: assinatura-14-1.php?id=' . urlencode($id_planilha));
    exit;
}

$todos_assinados = !empty($produtos) && array_reduce($produtos, function($carry, $p){
    return $carry && !is_null($p['doador_conjugue_id']) && $p['doador_conjugue_id'] > 0;
}, true);

// Ajustar título conforme estado (todos assinados => desfazer)
$pageTitle = $todos_assinados
    ? ($multiplo ? 'Desfazer Assinatura de ' . count($produtos) . ' Produtos' : 'Desfazer Assinatura do Produto')
    : ($multiplo ? 'Assinar ' . count($produtos) . ' Produtos' : 'Assinar Produto');
$backUrl = 'assinatura-14-1.php?id=' . urlencode($id_planilha);
ob_start();
?>

<style>
.radio-card { border: 2px solid #dee2e6; border-radius: 0.5rem; padding: 1rem; cursor: pointer; transition: all 0.2s; margin-bottom: 1rem; }
.radio-card:hover { border-color: #007bff; background-color: #f8f9fa; }
.radio-card.selected { border-color: #007bff; background-color: #e7f3ff; }
.radio-card input[type="radio"] { width: 1.25rem; height: 1.25rem; cursor: pointer; }
.radio-card label { cursor: pointer; margin-bottom: 0; flex-grow: 1; }
.campos-nota { display: none; animation: fadeIn 0.3s; }
.campos-nota.show { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
.produto-info { background: #e9ecef; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #007bff; }
</style>

<?php if ($multiplo && !$todos_assinados): ?>
<div class="alert alert-info mb-3">
    <h5 class="mb-2"><i class="bi bi-info-circle-fill me-2"></i>Assinatura Múltipla</h5>
    <p class="mb-0">Você está assinando <strong><?php echo count($produtos); ?> produtos</strong> de uma vez. As mesmas condições serão aplicadas a todos.</p>
</div>

<div class="card mb-3">
    <div class="card-header bg-light">
        <strong>Produtos Selecionados:</strong>
    </div>
    <div class="card-body">
        <ul class="mb-0">
            <?php foreach ($produtos as $p): ?>
            <li><?php echo htmlspecialchars($p['tipo_descricao'] . ' - ' . substr($p['descricao_completa'], 0, 80)); ?><?php if (strlen($p['descricao_completa']) > 80): ?>...<?php endif; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php elseif(!$multiplo && !$todos_assinados): ?>
<?php $produto = $produtos[0]; ?>
<div class="produto-info">
    <h6 class="mb-2"><i class="bi bi-box-seam me-2"></i><?php echo htmlspecialchars($produto['tipo_descricao']); ?></h6>
    <p class="mb-0 small"><?php echo htmlspecialchars($produto['descricao_completa']); ?></p>
</div>
<?php endif; ?>
<?php if($todos_assinados && $multiplo): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Todos os produtos selecionados já estão assinados. Você pode desfazer a assinatura abaixo.
</div>
<?php elseif($todos_assinados && !$multiplo): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Este produto já está assinado. Você pode desfazer a assinatura abaixo.
</div>
<?php endif; ?>

<form id="formAssinatura" method="POST" action="../../../CRUD/UPDATE/<?php echo $todos_assinados ? 'desassinar-produto-14-1.php' : 'assinar-produto-14-1.php'; ?>">
    <?php foreach ($produtos_ids as $pid): ?>
    <input type="hidden" name="ids_produtos[]" value="<?php echo $pid; ?>">
    <?php endforeach; ?>
    <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
    
    <?php if(!$todos_assinados): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white"><i class="bi bi-check2-square me-2"></i>Selecione a Condição do Bem</div>
        <div class="card-body">
            <div class="radio-card" data-value="1" onclick="selecionarCondicao(1)">
                <div class="d-flex align-items-start gap-3">
                    <input type="radio" name="condicao_14_1" id="condicao1" value="1">
                    <label for="condicao1"><strong>Opção 1:</strong> O bem tem mais de cinco anos de uso e o documento fiscal de aquisição está anexo.</label>
                </div>
            </div>
            <div class="radio-card" data-value="2" onclick="selecionarCondicao(2)">
                <div class="d-flex align-items-start gap-3">
                    <input type="radio" name="condicao_14_1" id="condicao2" value="2">
                    <label for="condicao2"><strong>Opção 2:</strong> O bem tem mais de cinco anos de uso, porém o documento fiscal de aquisição foi extraviado.</label>
                </div>
            </div>
            <div class="radio-card" data-value="3" onclick="selecionarCondicao(3)">
                <div class="d-flex align-items-start gap-3">
                    <input type="radio" name="condicao_14_1" id="condicao3" value="3">
                    <label for="condicao3"><strong>Opção 3:</strong> O bem tem até cinco anos de uso e o documento fiscal de aquisição está anexo.</label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4 campos-nota" id="camposNota" <?php if($todos_assinados): ?>style="display:none"<?php endif; ?>>
        <div class="card-header bg-info text-white"><i class="bi bi-receipt me-2"></i>Dados da Nota Fiscal</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Número da Nota <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nota_numero" name="nota_numero">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Data da Nota <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="nota_data" name="nota_data">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Valor da Nota <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nota_valor" name="nota_valor" placeholder="Ex: 1.500,00">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Fornecedor <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nota_fornecedor" name="nota_fornecedor">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if(!$todos_assinados): ?>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success flex-fill"><i class="bi bi-check-lg me-2"></i>Salvar e Assinar</button>
            <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-2"></i>Cancelar</a>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Estes produto(s) já estão assinados. Clique abaixo para desfazer a assinatura e limpar os dados de nota.
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger flex-fill"><i class="bi bi-arrow-counterclockwise me-2"></i>Desfazer Assinatura</button>
            <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-2"></i>Cancelar</a>
        </div>
    <?php endif; ?>
</form>

<script>
function selecionarCondicao(v) {
    document.querySelectorAll('.radio-card').forEach(c => c.classList.remove('selected'));
    document.querySelector('.radio-card[data-value="'+v+'"]').classList.add('selected');
    document.getElementById('condicao'+v).checked = true;
    var cn = document.getElementById('camposNota');
    var ins = cn.querySelectorAll('input');
    if (v === 1 || v === 3) {
        cn.classList.add('show');
        ins.forEach(i => i.required = true);
    } else {
        cn.classList.remove('show');
        ins.forEach(i => { i.required = false; i.value = ''; });
    }
}
// Se todos assinados não há seleção de condição
<?php if($todos_assinados): ?>
document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.radio-card input[type="radio"]').forEach(r=>r.disabled = true);
});
<?php endif; ?>
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = PROJECT_ROOT . '/temp_assinatura_form_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include PROJECT_ROOT . '/layouts/app-wrapper.php';
unlink($tempFile);
?>
