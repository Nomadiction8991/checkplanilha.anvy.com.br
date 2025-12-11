<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../CRUD/conexao.php';

$id_planilha = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id_planilha) {
    header('Location: ../../planilhas/listar-planilhas.php');
    exit;
}

$usuario_id = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
$usuario_tipo = isset($_SESSION['usuario_tipo']) ? $_SESSION['usuario_tipo'] : '';

// Determinar coluna de assinatura baseado no tipo de usuário
$coluna_assinatura = '';
if ($usuario_tipo === 'Administrador/Acessor') {
    $coluna_assinatura = 'administrador_acessor_id';
} elseif ($usuario_tipo === 'Doador/Cônjuge') {
    $coluna_assinatura = 'doador_conjugue_id';
}

// Buscar produtos da planilha
$sql = "SELECT 
            p.id_produto,
            p.codigo,
            p.descricao_completa,
            p.complemento,
            p.imprimir_14_1,
            p.{$coluna_assinatura} as minha_assinatura,
            tb.descricao as tipo_descricao,
            d.descricao as dependencia_descricao
        FROM produtos p
        LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
        LEFT JOIN dependencias d ON p.dependencia_id = d.id
        WHERE p.planilha_id = :id_planilha AND p.ativo = 1
        ORDER BY p.id_produto ASC";
$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_planilha', $id_planilha);
$stmt->execute();
$produtos = $stmt->fetchAll();

$pageTitle = 'Assinar Produtos';
$backUrl = '../../planilhas/view-planilha.php?id=' . $id_planilha;

ob_start();
?>

<style>
.produto-card {
    border-left: 4px solid #dee2e6;
    transition: all 0.3s;
}
.produto-card.assinado {
    border-left-color: #198754;
    background-color: #f8fff8;
}
.produto-card.selecionado {
    border-left-color: #0d6efd;
    background-color: #f0f7ff;
}
.produto-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Instruções:</strong> Selecione os produtos que deseja assinar. 
    <?php if ($usuario_tipo === 'Administrador/Acessor'): ?>
        Você está assinando como <strong>Administrador/Acessor</strong>.
    <?php else: ?>
        Você está assinando como <strong>Doador/Cônjuge</strong>.
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-boxes me-2"></i>
            Produtos Disponíveis
        </span>
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selecionarTodos()">
                <i class="bi bi-check-all"></i> Todos
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desmarcarTodos()">
                <i class="bi bi-x-lg"></i> Nenhum
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($produtos)): ?>
            <p class="text-muted text-center mb-0">Nenhum produto disponível nesta planilha.</p>
        <?php else: ?>
            <div id="produtosContainer">
                <?php foreach ($produtos as $produto): ?>
                    <?php 
                        $assinado_por_mim = ($produto['minha_assinatura'] == $usuario_id);
                        $pode_desassinar = $assinado_por_mim;
                    ?>
                    <div class="card produto-card mb-2 <?php echo $assinado_por_mim ? 'assinado' : ''; ?>" data-produto-id="<?php echo $produto['id_produto']; ?>">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center">
                                <div class="form-check me-3">
                                    <input class="form-check-input produto-checkbox" 
                                           type="checkbox" 
                                           value="<?php echo $produto['id_produto']; ?>"
                                           id="produto_<?php echo $produto['id_produto']; ?>">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($produto['codigo'] ?? 'S/N'); ?>
                                        <?php if ($assinado_por_mim): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="bi bi-check-circle"></i> Assinado por você
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($produto['imprimir_14_1']): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-file-earmark-pdf"></i> No relatório 14.1
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo htmlspecialchars($produto['tipo_descricao'] ?? ''); ?>
                                        <?php if ($produto['complemento']): ?>
                                            - <?php echo htmlspecialchars($produto['complemento']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($produto['dependencia_descricao']): ?>
                                        <div class="small text-muted">
                                            <i class="bi bi-building"></i>
                                            <?php echo htmlspecialchars($produto['dependencia_descricao']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($produtos)): ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success flex-grow-1" onclick="assinarProdutos()">
                <i class="bi bi-pen me-1"></i>
                Assinar Selecionados
            </button>
            <button type="button" class="btn btn-danger flex-grow-1" onclick="desassinarProdutos()">
                <i class="bi bi-x-circle me-1"></i>
                Remover Assinatura
            </button>
        </div>
        <small class="text-muted d-block mt-2">
            <i class="bi bi-info-circle"></i>
            Selecione os produtos acima e clique em "Assinar" ou "Remover Assinatura"
        </small>
    </div>
</div>
<?php endif; ?>

<script>
function selecionarTodos() {
    document.querySelectorAll('.produto-checkbox').forEach(cb => {
        cb.checked = true;
        cb.closest('.produto-card').classList.add('selecionado');
    });
}

function desmarcarTodos() {
    document.querySelectorAll('.produto-checkbox').forEach(cb => {
        cb.checked = false;
        cb.closest('.produto-card').classList.remove('selecionado');
    });
}

// Atualizar visual ao selecionar
document.querySelectorAll('.produto-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        if (this.checked) {
            this.closest('.produto-card').classList.add('selecionado');
        } else {
            this.closest('.produto-card').classList.remove('selecionado');
        }
    });
});

function assinarProdutos() {
    const selecionados = Array.from(document.querySelectorAll('.produto-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selecionados.length === 0) {
        alert('Selecione pelo menos um produto para assinar');
        return;
    }
    
    if (!confirm(`Deseja assinar ${selecionados.length} produto(s)?`)) {
        return;
    }
    
    executarAcao('assinar', selecionados);
}

function desassinarProdutos() {
    const selecionados = Array.from(document.querySelectorAll('.produto-checkbox:checked'))
        .map(cb => cb.value);
    
    if (selecionados.length === 0) {
        alert('Selecione pelo menos um produto para remover a assinatura');
        return;
    }
    
    if (!confirm(`Deseja remover sua assinatura de ${selecionados.length} produto(s)?`)) {
        return;
    }
    
    executarAcao('desassinar', selecionados);
}

function executarAcao(acao, produtos) {
    const formData = new FormData();
    formData.append('acao', acao);
    produtos.forEach(id => formData.append('produtos[]', id));
    
    fetch('../../../CRUD/UPDATE/assinar-produtos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erro ao processar solicitação');
        console.error(error);
    });
}
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinar_produtos_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
