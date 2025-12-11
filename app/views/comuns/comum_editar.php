<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ../../../index.php');
    exit;
}

$comum = obter_comum_por_id($conexao, $id);
if (!$comum) {
    $_SESSION['mensagem'] = 'Comum nÃƒÂ£o encontrada.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../../../index.php');
    exit;
}

$pageTitle = 'Editar Comum';
$backUrl = '../../../index.php';

$mt_cidades = [
    'MT - Acorizal','MT - ÃƒÂgua Boa','MT - Alta Floresta','MT - Alto Araguaia','MT - Alto Boa Vista','MT - Alto GarÃƒÂ§as','MT - Alto Paraguai','MT - Alto Taquari','MT - ApiacÃƒÂ¡s','MT - Araguaiana','MT - Araguainha','MT - Araputanga','MT - ArenÃƒÂ¡polis','MT - AripuanÃƒÂ£','MT - BarÃƒÂ£o de MelgaÃƒÂ§o','MT - Barra do Bugres','MT - Barra do GarÃƒÂ§as','MT - Bom Jesus do Araguaia','MT - Brasnorte','MT - CÃƒÂ¡ceres','MT - CampinÃƒÂ¡polis','MT - Campo Novo do Parecis','MT - Campo Verde','MT - Campos de JÃƒÂºlio','MT - Canabrava do Norte','MT - Canarana','MT - Carlinda','MT - Castanheira','MT - Chapada dos GuimarÃƒÂ£es','MT - ClÃƒÂ¡udia','MT - Cocalinho','MT - ColÃƒÂ­der','MT - Colniza','MT - Comodoro','MT - Confresa','MT - Conquista d\'Oeste','MT - CotriguaÃƒÂ§u','MT - CuiabÃƒÂ¡','MT - CurvelÃƒÂ¢ndia','MT - Denise','MT - Diamantino','MT - Dom Aquino','MT - Feliz Natal','MT - FigueirÃƒÂ³polis d\'Oeste','MT - GaÃƒÂºcha do Norte','MT - General Carneiro','MT - GlÃƒÂ³ria d\'Oeste','MT - GuarantÃƒÂ£ do Norte','MT - Guiratinga','MT - IndiavaÃƒÂ­','MT - Ipiranga do Norte','MT - ItanhangÃƒÂ¡','MT - ItaÃƒÂºba','MT - Itiquira','MT - Jaciara','MT - Jangada','MT - Jauru','MT - Juara','MT - JuÃƒÂ­na','MT - Juruena','MT - Juscimeira','MT - Lambari d\'Oeste','MT - Lucas do Rio Verde','MT - Luciara','MT - MarcelÃƒÂ¢ndia','MT - MatupÃƒÂ¡','MT - Mirassol d\'Oeste','MT - Nobres','MT - NortelÃƒÂ¢ndia','MT - Nossa Senhora do Livramento','MT - Nova Bandeirantes','MT - Nova BrasilÃƒÂ¢ndia','MT - Nova CanaÃƒÂ£ do Norte','MT - Nova Guarita','MT - Nova Lacerda','MT - Nova MarilÃƒÂ¢ndia','MT - Nova MaringÃƒÂ¡','MT - Nova Monte Verde','MT - Nova Mutum','MT - Nova NazarÃƒÂ©','MT - Nova OlÃƒÂ­mpia','MT - Nova Santa Helena','MT - Nova UbiratÃƒÂ£','MT - Nova Xavantina','MT - Novo Horizonte do Norte','MT - Novo Mundo','MT - Novo Santo AntÃƒÂ´nio','MT - Novo SÃƒÂ£o Joaquim','MT - ParanaÃƒÂ­ta','MT - Paranatinga','MT - Pedra Preta','MT - Peixoto de Azevedo','MT - Planalto da Serra','MT - PoconÃƒÂ©','MT - Pontal do Araguaia','MT - Ponte Branca','MT - Pontes e Lacerda','MT - Porto Alegre do Norte','MT - Porto dos GaÃƒÂºchos','MT - Porto EsperidiÃƒÂ£o','MT - Porto Estrela','MT - PoxorÃƒÂ©u','MT - Primavera do Leste','MT - QuerÃƒÂªncia','MT - Reserva do CabaÃƒÂ§al','MT - RibeirÃƒÂ£o Cascalheira','MT - RibeirÃƒÂ£ozinho','MT - Rio Branco','MT - RondolÃƒÂ¢ndia','MT - RondonÃƒÂ³polis','MT - RosÃƒÂ¡rio Oeste','MT - Salto do CÃƒÂ©u','MT - Santa Carmem','MT - Santa Cruz do Xingu','MT - Santa Rita do Trivelato','MT - Santa Terezinha','MT - Santo Afonso','MT - Santo AntÃƒÂ´nio do Leste','MT - Santo AntÃƒÂ´nio do Leverger','MT - SÃƒÂ£o FÃƒÂ©lix do Araguaia','MT - SÃƒÂ£o JosÃƒÂ© do Povo','MT - SÃƒÂ£o JosÃƒÂ© do Rio Claro','MT - SÃƒÂ£o JosÃƒÂ© do Xingu','MT - SÃƒÂ£o JosÃƒÂ© dos Quatro Marcos','MT - SÃƒÂ£o Pedro da Cipa','MT - Sapezal','MT - Serra Nova Dourada','MT - Sinop','MT - Sorriso','MT - TabaporÃƒÂ£','MT - TangarÃƒÂ¡ da Serra','MT - Tapurah','MT - Terra Nova do Norte','MT - Tesouro','MT - TorixorÃƒÂ©u','MT - UniÃƒÂ£o do Sul','MT - Vale de SÃƒÂ£o Domingos','MT - VÃƒÂ¡rzea Grande','MT - Vera','MT - Vila Bela da SantÃƒÂ­ssima Trindade','MT - Vila Rica'
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
            <form method="POST" action="../../../app/controllers/update/ComumUpdateController.php" novalidate>
                <input type="hidden" name="id" value="<?php echo (int)$comum['id']; ?>">

                <div class="mb-3">
                    <label class="form-label">CÃƒÂ³digo</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($comum['codigo']); ?>" disabled>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="descricao" class="form-label">DescriÃƒÂ§ÃƒÂ£o <span class="text-danger">*</span></label>
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
                        <label for="administracao" class="form-label">AdministraÃƒÂ§ÃƒÂ£o <span class="text-danger">*</span></label>
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
include __DIR__ . '/../layouts/app_wrapper.php';
@unlink($contentFile);
?>



