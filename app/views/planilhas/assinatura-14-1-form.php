<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../CRUD/conexao.php';

$id_produto = $_GET['id'] ?? null;
$id_planilha = $_GET['id_planilha'] ?? null;

if (!$id_produto || !$id_planilha) {
    header('Location: ../../../index.php');
    exit;
}

$sql = "SELECT p.id_produto, p.descricao_completa, p.condicao_14_1, p.nota_numero, p.nota_data, p.nota_valor, p.nota_fornecedor, p.doador_conjugue_id, tb.descricao as tipo_descricao, u.nome as doador_nome FROM produtos p LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id LEFT JOIN usuarios u ON p.doador_conjugue_id = u.id WHERE p.id_produto = :id_produto";
$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_produto', $id_produto);
$stmt->execute();
$produto = $stmt->fetch();

if (!$produto) {
    header('Location: assinatura-14-1.php?id=' . urlencode($id_planilha));
    exit;
}

$pageTitle = 'Assinar Produto';
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
.produto-info { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; }
</style>

<div class="produto-info">
    <h5 class="mb-3"><i class="bi bi-box-seam me-2"></i>Informações do Produto</h5>
    <p class="mb-2"><strong>Tipo:</strong> <?php echo htmlspecialchars($produto['tipo_descricao']); ?></p>
    <p class="mb-0"><strong>Descrição:</strong> <?php echo htmlspecialchars($produto['descricao_completa']); ?></p>
</div>

<form id="formAssinatura" method="POST" action="../../../CRUD/UPDATE/assinar-produto-14-1.php">
    <input type="hidden" name="id_produto" value="<?php echo $produto['id_produto']; ?>">
    <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
    
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
    
    <div class="card mb-4 campos-nota" id="camposNota">
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
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success flex-fill"><i class="bi bi-check-lg me-2"></i>Salvar e Assinar</button>
        <a href="<?php echo $backUrl; ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-2"></i>Cancelar</a>
    </div>
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
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinatura_form_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
