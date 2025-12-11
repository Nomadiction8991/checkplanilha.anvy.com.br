<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../CRUD/conexao.php';
require_once __DIR__ . '/../../../app/functions/comum_functions.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ./listar-comuns.php');
    exit;
}

$comum = obter_comum_por_id($conexao, $id);
if (!$comum) {
    $_SESSION['mensagem'] = 'Comum não encontrada.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ./listar-comuns.php');
    exit;
}

$pageTitle = 'Editar Comum';
$backUrl = '../../index.php';

$mt_cidades = [
    'MT - Acorizal','MT - Água Boa','MT - Alta Floresta','MT - Alto Araguaia','MT - Alto Boa Vista','MT - Alto Garças','MT - Alto Paraguai','MT - Alto Taquari','MT - Apiacás','MT - Araguaiana','MT - Araguainha','MT - Araputanga','MT - Arenápolis','MT - Aripuanã','MT - Barão de Melgaço','MT - Barra do Bugres','MT - Barra do Garças','MT - Bom Jesus do Araguaia','MT - Brasnorte','MT - Cáceres','MT - Campinápolis','MT - Campo Novo do Parecis','MT - Campo Verde','MT - Campos de Júlio','MT - Canabrava do Norte','MT - Canarana','MT - Carlinda','MT - Castanheira','MT - Chapada dos Guimarães','MT - Cláudia','MT - Cocalinho','MT - Colíder','MT - Colniza','MT - Comodoro','MT - Confresa','MT - Conquista d\'Oeste','MT - Cotriguaçu','MT - Cuiabá','MT - Curvelândia','MT - Denise','MT - Diamantino','MT - Dom Aquino','MT - Feliz Natal','MT - Figueirópolis d\'Oeste','MT - Gaúcha do Norte','MT - General Carneiro','MT - Glória d\'Oeste','MT - Guarantã do Norte','MT - Guiratinga','MT - Indiavaí','MT - Ipiranga do Norte','MT - Itanhangá','MT - Itaúba','MT - Itiquira','MT - Jaciara','MT - Jangada','MT - Jauru','MT - Juara','MT - Juína','MT - Juruena','MT - Juscimeira','MT - Lambari d\'Oeste','MT - Lucas do Rio Verde','MT - Luciara','MT - Marcelândia','MT - Matupá','MT - Mirassol d\'Oeste','MT - Nobres','MT - Nortelândia','MT - Nossa Senhora do Livramento','MT - Nova Bandeirantes','MT - Nova Brasilândia','MT - Nova Canaã do Norte','MT - Nova Guarita','MT - Nova Lacerda','MT - Nova Marilândia','MT - Nova Maringá','MT - Nova Monte Verde','MT - Nova Mutum','MT - Nova Nazaré','MT - Nova Olímpia','MT - Nova Santa Helena','MT - Nova Ubiratã','MT - Nova Xavantina','MT - Novo Horizonte do Norte','MT - Novo Mundo','MT - Novo Santo Antônio','MT - Novo São Joaquim','MT - Paranaíta','MT - Paranatinga','MT - Pedra Preta','MT - Peixoto de Azevedo','MT - Planalto da Serra','MT - Poconé','MT - Pontal do Araguaia','MT - Ponte Branca','MT - Pontes e Lacerda','MT - Porto Alegre do Norte','MT - Porto dos Gaúchos','MT - Porto Esperidião','MT - Porto Estrela','MT - Poxoréu','MT - Primavera do Leste','MT - Querência','MT - Reserva do Cabaçal','MT - Ribeirão Cascalheira','MT - Ribeirãozinho','MT - Rio Branco','MT - Rondolândia','MT - Rondonópolis','MT - Rosário Oeste','MT - Salto do Céu','MT - Santa Carmem','MT - Santa Cruz do Xingu','MT - Santa Rita do Trivelato','MT - Santa Terezinha','MT - Santo Afonso','MT - Santo Antônio do Leste','MT - Santo Antônio do Leverger','MT - São Félix do Araguaia','MT - São José do Povo','MT - São José do Rio Claro','MT - São José do Xingu','MT - São José dos Quatro Marcos','MT - São Pedro da Cipa','MT - Sapezal','MT - Serra Nova Dourada','MT - Sinop','MT - Sorriso','MT - Tabaporã','MT - Tangará da Serra','MT - Tapurah','MT - Terra Nova do Norte','MT - Tesouro','MT - Torixoréu','MT - União do Sul','MT - Vale de São Domingos','MT - Várzea Grande','MT - Vera','MT - Vila Bela da Santíssima Trindade','MT - Vila Rica'
];

ob_start();
?>

<?php if (!empty($_SESSION['mensagem'])): ?>
    <div class="alert alert-<?php echo $_SESSION['tipo_mensagem'] ?? 'info'; ?> alert-dismissible fade show">
        <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['mensagem'], $_SESSION['tipo_mensagem']); ?>
<?php endif; ?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <i class="bi bi-pencil-square me-2"></i>Editar Comum
        </div>
        <div class="card-body">
            <form method="POST" action="../../../CRUD/UPDATE/comum.php" novalidate>
                <input type="hidden" name="id" value="<?php echo (int)$comum['id']; ?>">

                <div class="mb-3">
                    <label class="form-label">Código</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($comum['codigo']); ?>" disabled>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                        <input type="text" id="descricao" name="descricao" class="form-control" required
                               value="<?php echo htmlspecialchars($comum['descricao']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="cnpj" class="form-label">CNPJ <span class="text-danger">*</span></label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control" required
                               value="<?php echo htmlspecialchars($comum['cnpj']); ?>" placeholder="00.000.000/0000-00">
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="administracao" class="form-label">Administração <span class="text-danger">*</span></label>
                        <select id="administracao" name="administracao" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($mt_cidades as $op): ?>
                                <option value="<?php echo htmlspecialchars($op); ?>" <?php echo ($comum['administracao'] ?? '') === $op ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($op); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                        <select id="cidade" name="cidade" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($mt_cidades as $op): ?>
                                <option value="<?php echo htmlspecialchars($op); ?>" <?php echo ($comum['cidade'] ?? '') === $op ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($op); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="setor" class="form-label">Setor (opcional)</label>
                        <input type="text" id="setor" name="setor" class="form-control"
                               value="<?php echo htmlspecialchars($comum['setor'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cnpjInput = document.getElementById('cnpj');
    if (cnpjInput && window.Inputmask) {
        Inputmask({"mask": "99.999.999/9999-99"}).mask(cnpjInput);
    }
});
</script>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../temp_editar_comum_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include __DIR__ . '/../layouts/app-wrapper.php';
@unlink($contentFile);
?>
