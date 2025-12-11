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
    $_SESSION['mensagem'] = 'Comum nÃ£o encontrada.';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: ../../../index.php');
    exit;
}

$pageTitle = 'Editar Comum';
$backUrl = '../../../index.php';

$mt_cidades = [
    'MT - Acorizal','MT - Ãgua Boa','MT - Alta Floresta','MT - Alto Araguaia','MT - Alto Boa Vista','MT - Alto GarÃ§as','MT - Alto Paraguai','MT - Alto Taquari','MT - ApiacÃ¡s','MT - Araguaiana','MT - Araguainha','MT - Araputanga','MT - ArenÃ¡polis','MT - AripuanÃ£','MT - BarÃ£o de MelgaÃ§o','MT - Barra do Bugres','MT - Barra do GarÃ§as','MT - Bom Jesus do Araguaia','MT - Brasnorte','MT - CÃ¡ceres','MT - CampinÃ¡polis','MT - Campo Novo do Parecis','MT - Campo Verde','MT - Campos de JÃºlio','MT - Canabrava do Norte','MT - Canarana','MT - Carlinda','MT - Castanheira','MT - Chapada dos GuimarÃ£es','MT - ClÃ¡udia','MT - Cocalinho','MT - ColÃ­der','MT - Colniza','MT - Comodoro','MT - Confresa','MT - Conquista d\'Oeste','MT - CotriguaÃ§u','MT - CuiabÃ¡','MT - CurvelÃ¢ndia','MT - Denise','MT - Diamantino','MT - Dom Aquino','MT - Feliz Natal','MT - FigueirÃ³polis d\'Oeste','MT - GaÃºcha do Norte','MT - General Carneiro','MT - GlÃ³ria d\'Oeste','MT - GuarantÃ£ do Norte','MT - Guiratinga','MT - IndiavaÃ­','MT - Ipiranga do Norte','MT - ItanhangÃ¡','MT - ItaÃºba','MT - Itiquira','MT - Jaciara','MT - Jangada','MT - Jauru','MT - Juara','MT - JuÃ­na','MT - Juruena','MT - Juscimeira','MT - Lambari d\'Oeste','MT - Lucas do Rio Verde','MT - Luciara','MT - MarcelÃ¢ndia','MT - MatupÃ¡','MT - Mirassol d\'Oeste','MT - Nobres','MT - NortelÃ¢ndia','MT - Nossa Senhora do Livramento','MT - Nova Bandeirantes','MT - Nova BrasilÃ¢ndia','MT - Nova CanaÃ£ do Norte','MT - Nova Guarita','MT - Nova Lacerda','MT - Nova MarilÃ¢ndia','MT - Nova MaringÃ¡','MT - Nova Monte Verde','MT - Nova Mutum','MT - Nova NazarÃ©','MT - Nova OlÃ­mpia','MT - Nova Santa Helena','MT - Nova UbiratÃ£','MT - Nova Xavantina','MT - Novo Horizonte do Norte','MT - Novo Mundo','MT - Novo Santo AntÃ´nio','MT - Novo SÃ£o Joaquim','MT - ParanaÃ­ta','MT - Paranatinga','MT - Pedra Preta','MT - Peixoto de Azevedo','MT - Planalto da Serra','MT - PoconÃ©','MT - Pontal do Araguaia','MT - Ponte Branca','MT - Pontes e Lacerda','MT - Porto Alegre do Norte','MT - Porto dos GaÃºchos','MT - Porto EsperidiÃ£o','MT - Porto Estrela','MT - PoxorÃ©u','MT - Primavera do Leste','MT - QuerÃªncia','MT - Reserva do CabaÃ§al','MT - RibeirÃ£o Cascalheira','MT - RibeirÃ£ozinho','MT - Rio Branco','MT - RondolÃ¢ndia','MT - RondonÃ³polis','MT - RosÃ¡rio Oeste','MT - Salto do CÃ©u','MT - Santa Carmem','MT - Santa Cruz do Xingu','MT - Santa Rita do Trivelato','MT - Santa Terezinha','MT - Santo Afonso','MT - Santo AntÃ´nio do Leste','MT - Santo AntÃ´nio do Leverger','MT - SÃ£o FÃ©lix do Araguaia','MT - SÃ£o JosÃ© do Povo','MT - SÃ£o JosÃ© do Rio Claro','MT - SÃ£o JosÃ© do Xingu','MT - SÃ£o JosÃ© dos Quatro Marcos','MT - SÃ£o Pedro da Cipa','MT - Sapezal','MT - Serra Nova Dourada','MT - Sinop','MT - Sorriso','MT - TabaporÃ£','MT - TangarÃ¡ da Serra','MT - Tapurah','MT - Terra Nova do Norte','MT - Tesouro','MT - TorixorÃ©u','MT - UniÃ£o do Sul','MT - Vale de SÃ£o Domingos','MT - VÃ¡rzea Grande','MT - Vera','MT - Vila Bela da SantÃ­ssima Trindade','MT - Vila Rica'
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
            <form method="POST" action="../../../app/controllers/update/comum.php" novalidate>
                <input type="hidden" name="id" value="<?php echo (int)$comum['id']; ?>">

                <div class="mb-3">
                    <label class="form-label">CÃ³digo</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($comum['codigo']); ?>" disabled>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="descricao" class="form-label">DescriÃ§Ã£o <span class="text-danger">*</span></label>
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
                        <label for="administracao" class="form-label">AdministraÃ§Ã£o <span class="text-danger">*</span></label>
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

