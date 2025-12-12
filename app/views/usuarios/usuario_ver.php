<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AutenticaÃƒÂ§ÃƒÂ£o

// Apenas admins podem acessar visualizaÃƒÂ§ÃƒÂ£o de usuÃƒÂ¡rios
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$idParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$idParam) {
    header('Location: ./usuarios_listar.php');
    exit;
}


// Buscar usuÃƒÂ¡rio
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

$pageTitle = 'Visualizar UsuÃƒÂ¡rio';
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
.signature-preview-canvas {
    pointer-events: none;
}
.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}
.info-value {
    color: #212529;
    margin-bottom: 1rem;
}
</style>

<?php if (isset($usuario)): ?>

<!-- Card 1: Dados BÃƒÂ¡sicos -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-person-plus me-2"></i>
        Dados BÃƒÂ¡sicos
    </div>
    <div class="card-body">
        <div class="info-label">Nome Completo</div>
        <div class="info-value"><?php echo format_usuario_valor($usuario['nome']); ?></div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-label">CPF</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['cpf'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">RG</div>
                <div class="info-value">
                    <?php 
                    if (!empty($usuario['rg_igual_cpf'])) {
                        echo format_usuario_valor($usuario['cpf'] ?? '') . ' <span class="badge bg-info">IGUAL AO CPF</span>';
                    } else {
                        echo format_usuario_valor($usuario['rg'] ?? '');
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="info-label">Telefone</div>
        <div class="info-value"><?php echo format_usuario_valor($usuario['telefone'] ?? ''); ?></div>

        <div class="info-label">Email</div>
        <div class="info-value"><?php echo format_usuario_valor($usuario['email']); ?></div>

        <div class="info-label">Status</div>
        <div class="info-value">
            <span class="badge bg-<?php echo $usuario['ativo'] ? 'success' : 'secondary'; ?>">
                <?php echo $usuario['ativo'] ? 'ATIVO' : 'INATIVO'; ?>
            </span>
        </div>
    </div>
</div>

<!-- Card 3: Estado civil -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-person-hearts me-2"></i>
        Estado civil
    </div>
    <div class="card-body">
        <div class="info-value">
            <?php if (!empty($usuario['casado'])): ?>
                <span class="badge bg-success">CASADO(A)</span>
            <?php else: ?>
                <span class="badge bg-secondary">NÃO INFORMADO / SOLTEIRO(A)</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Card 4: Dados do CÃƒÂ´njuge (condicional) -->
<?php if (!empty($usuario['casado'])): ?>
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-people-fill me-2"></i>
        Dados do CÃƒÂ´njuge
    </div>
    <div class="card-body">
        <div class="info-label">Nome Completo do CÃƒÂ´njuge</div>
        <div class="info-value"><?php echo format_usuario_valor($usuario['nome_conjuge'] ?? ''); ?></div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-label">CPF do CÃƒÂ´njuge</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['cpf_conjuge'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">RG do CÃƒÂ´njuge</div>
                <div class="info-value">
                    <?php 
                    if (!empty($usuario['rg_conjuge_igual_cpf'])) {
                        echo format_usuario_valor($usuario['cpf_conjuge'] ?? '') . ' <span class="badge bg-info">IGUAL AO CPF</span>';
                    } else {
                        echo format_usuario_valor($usuario['rg_conjuge'] ?? '');
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="info-label">Telefone do CÃƒÂ´njuge</div>
        <div class="info-value"><?php echo format_usuario_valor($usuario['telefone_conjuge'] ?? ''); ?></div>

        <hr>
    </div>
</div>
<?php endif; ?>

<!-- Card 5: EndereÃƒÂ§o -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-geo-alt me-2"></i>
        EndereÃƒÂ§o
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="info-label">CEP</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_cep'] ?? ''); ?></div>
            </div>
            <div class="col-md-9">
                <div class="info-label">Logradouro</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_logradouro'] ?? ''); ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="info-label">NÃƒÂºmero</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_numero'] ?? ''); ?></div>
            </div>
            <div class="col-md-9">
                <div class="info-label">Complemento</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_complemento'] ?? ''); ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-label">Bairro</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_bairro'] ?? ''); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">Cidade</div>
                <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_cidade'] ?? ''); ?></div>
            </div>
        </div>

        <div class="info-label">Estado</div>
        <div class="info-value"><?php echo format_usuario_valor($usuario['endereco_estado'] ?? ''); ?></div>
    </div>
</div>

<?php endif; ?>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_ver_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>

