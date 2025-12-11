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
        <div class="info-value"><?php echo htmlspecialchars($usuario['nome']); ?></div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-label">CPF</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['cpf'] ?? '-'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">RG</div>
                <div class="info-value">
                    <?php 
                    if (!empty($usuario['rg_igual_cpf'])) {
                        echo htmlspecialchars($usuario['cpf'] ?? '-') . ' <span class="badge bg-info">Igual ao CPF</span>';
                    } else {
                        echo htmlspecialchars($usuario['rg'] ?? '-');
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="info-label">Telefone</div>
        <div class="info-value"><?php echo htmlspecialchars($usuario['telefone'] ?? '-'); ?></div>

        <div class="info-label">Email</div>
        <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>

        <div class="info-label">Tipo de UsuÃƒÂ¡rio</div>
        <div class="info-value">
            <span class="badge bg-<?php echo ($usuario['tipo'] ?? 'Administrador/Acessor') === 'Administrador/Acessor' ? 'primary' : 'success'; ?>">
                <?php echo htmlspecialchars($usuario['tipo'] ?? 'Administrador/Acessor'); ?>
            </span>
        </div>

        <div class="info-label">Status</div>
        <div class="info-value">
            <span class="badge bg-<?php echo $usuario['ativo'] ? 'success' : 'secondary'; ?>">
                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
            </span>
        </div>
    </div>
</div>

<!-- Card 2: Assinatura Digital -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-pen me-2"></i>
        Assinatura Digital
    </div>
    <div class="card-body">
        <?php if (!empty($usuario['assinatura'])): ?>
            <div class="signature-preview-container">
                <img src="<?php echo htmlspecialchars($usuario['assinatura']); ?>" 
                     alt="Assinatura" 
                     class="img-fluid" 
                     style="border:1px solid #dee2e6; border-radius:0.375rem; max-width:100%; height:auto; background:#f8f9fa;">
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Nenhuma assinatura cadastrada
            </p>
        <?php endif; ?>
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
                <span class="badge bg-success">Casado(a)</span>
            <?php else: ?>
                <span class="badge bg-secondary">NÃƒÂ£o informado / Solteiro(a)</span>
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
        <div class="info-value"><?php echo htmlspecialchars($usuario['nome_conjuge'] ?? '-'); ?></div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-label">CPF do CÃƒÂ´njuge</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['cpf_conjuge'] ?? '-'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">RG do CÃƒÂ´njuge</div>
                <div class="info-value">
                    <?php 
                    if (!empty($usuario['rg_conjuge_igual_cpf'])) {
                        echo htmlspecialchars($usuario['cpf_conjuge'] ?? '-') . ' <span class="badge bg-info">Igual ao CPF</span>';
                    } else {
                        echo htmlspecialchars($usuario['rg_conjuge'] ?? '-');
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="info-label">Telefone do CÃƒÂ´njuge</div>
        <div class="info-value"><?php echo htmlspecialchars($usuario['telefone_conjuge'] ?? '-'); ?></div>

        <hr>
        <div class="info-label">Assinatura Digital do CÃƒÂ´njuge</div>
        <?php if (!empty($usuario['assinatura_conjuge'])): ?>
            <div class="signature-preview-container">
                <img src="<?php echo htmlspecialchars($usuario['assinatura_conjuge']); ?>" 
                     alt="Assinatura do CÃƒÂ´njuge" 
                     class="img-fluid" 
                     style="border:1px solid #dee2e6; border-radius:0.375rem; max-width:100%; height:auto; background:#f8f9fa;">
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Nenhuma assinatura cadastrada
            </p>
        <?php endif; ?>
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
                <div class="info-value"><?php echo htmlspecialchars($usuario['endereco_cep'] ?? '-'); ?></div>
            </div>
            <div class="col-md-9">
                <div class="info-label">Logradouro</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['endereco_logradouro'] ?? '-'); ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="info-label">NÃƒÂºmero</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['endereco_numero'] ?? '-'); ?></div>
            </div>
            <div class="col-md-9">
                <div class="info-label">Complemento</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['endereco_complemento'] ?? '-'); ?></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-label">Bairro</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['endereco_bairro'] ?? '-'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="info-label">Cidade</div>
                <div class="info-value"><?php echo htmlspecialchars($usuario['endereco_cidade'] ?? '-'); ?></div>
            </div>
        </div>

        <div class="info-label">Estado</div>
        <div class="info-value"><?php echo htmlspecialchars($usuario['endereco_estado'] ?? '-'); ?></div>
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

