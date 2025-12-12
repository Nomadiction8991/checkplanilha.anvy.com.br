<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Autenticação
// Apenas admins podem visualizar usuários
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$idParam) {
    header('Location: ./usuarios_listar.php');
    exit;
}

$stmt = $conexao->prepare('SELECT * FROM usuarios WHERE id = :id');
$stmt->bindValue(':id', $idParam, PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: ./usuarios_listar.php');
    exit;
}

$loggedId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
$isSelf = ($loggedId === $idParam);

$pageTitle = 'Visualizar Usuário';
$backUrl = './usuarios_listar.php';

function format_usuario_valor($valor)
{
    if ($valor === null || $valor === '') {
        return '-';
    }

    return mb_strtoupper(htmlspecialchars($valor, ENT_QUOTES, 'UTF-8'), 'UTF-8');
}

ob_start();
?>

<!-- jQuery e InputMask -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>

<style>
.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
    text-transform: none;
}
.info-value {
    color: #212529;
    font-size: 0.95rem;
    margin-bottom: 0.85rem;
}
.badge-status {
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.badge-info-flag {
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.08em;
}
</style>

<div class="card mb-3 shadow-sm">
    <div class="card-header bg-white border-bottom-0">
        <h5 class="mb-0 text-secondary"><i class="bi bi-person-plus me-2"></i>Dados Básicos</h5>
    </div>
    <div class="card-body border-top">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="info-label">Nome Completo</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['nome']); ?></div>
                <div class="info-label">CPF</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['cpf'] ?? ''); ?></div>
                <div class="info-label">RG</div>
                <div class="info-value">
                    <?php
                    if (!empty($usuario['rg_igual_cpf'])) {
                        echo format_usuario_valor($usuario['cpf'] ?? '') . ' <span class="badge badge-info-flag bg-info text-dark">IGUAL AO CPF</span>';
                    } else {
                        echo format_usuario_valor($usuario['rg'] ?? '');
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-label">Telefone</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['telefone'] ?? ''); ?></div>
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['email']); ?></div>
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="badge badge-status bg-<?php echo $usuario['ativo'] ? 'success' : 'secondary'; ?> px-3 py-2 fs-6">
                        <?php echo $usuario['ativo'] ? 'ATIVO' : 'INATIVO'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3 shadow-sm">
    <div class="card-header bg-white border-bottom-0">
        <h5 class="mb-0 text-secondary"><i class="bi bi-person-hearts me-2"></i>Estado Civil</h5>
    </div>
    <div class="card-body border-top">
        <div class="info-value">
            <?php if (!empty($usuario['casado'])): ?>
                <span class="badge badge-status bg-success px-3 py-2">CASADO(A)</span>
            <?php else: ?>
                <span class="badge badge-status bg-secondary px-3 py-2">NÃO INFORMADO / SOLTEIRO(A)</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($usuario['casado'])): ?>
<div class="card mb-3 shadow-sm">
    <div class="card-header bg-white border-bottom-0">
        <h5 class="mb-0 text-secondary"><i class="bi bi-people-fill me-2"></i>Dados do Cônjuge</h5>
    </div>
    <div class="card-body border-top">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="info-label">Nome Completo</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['nome_conjuge'] ?? ''); ?></div>
                <div class="info-label">CPF</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['cpf_conjuge'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">RG</div>
                <div class="info-value">
                    <?php
                    if (!empty($usuario['rg_conjuge_igual_cpf'])) {
                        echo format_usuario_valor($usuario['cpf_conjuge'] ?? '') . ' <span class="badge badge-info-flag bg-info text-dark">IGUAL AO CPF</span>';
                    } else {
                        echo format_usuario_valor($usuario['rg_conjuge'] ?? '');
                    }
                    ?>
                </div>
                <div class="info-label">Telefone</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['telefone_conjuge'] ?? ''); ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3 shadow-sm">
    <div class="card-header bg-white border-bottom-0">
        <h5 class="mb-0 text-secondary"><i class="bi bi-geo-alt me-2"></i>Endereço</h5>
    </div>
    <div class="card-body border-top">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="info-label">CEP</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_cep'] ?? ''); ?></div>
            </div>
            <div class="col-md-9">
                <div class="info-label">Logradouro</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_logradouro'] ?? ''); ?></div>
            </div>
            <div class="col-md-3">
                <div class="info-label">Número</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_numero'] ?? ''); ?></div>
            </div>
            <div class="col-md-9">
                <div class="info-label">Complemento</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_complemento'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">Bairro</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_bairro'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">Cidade</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_cidade'] ?? ''); ?></div>
            </div>
            <div class="col-md-12">
                <div class="info-label">Estado</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_estado'] ?? ''); ?></div>
            </div>
        </div>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_ver_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>

