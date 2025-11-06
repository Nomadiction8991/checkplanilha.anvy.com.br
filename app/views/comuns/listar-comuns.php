<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/conexao.php';
require_once __DIR__ . '/../../../app/functions/comum_functions.php';

// Configurações da página
$pageTitle = 'Gerenciar Comuns';
$backUrl = '../shared/menu.php';
$headerActions = '
    <a href="../shared/menu.php" class="btn-header-action" title="Menu">
        <i class="bi bi-list fs-5"></i>
    </a>
';

// Obter todos os comuns
try {
    $comuns = obter_todos_comuns($conexao);
} catch (Exception $e) {
    $comuns = [];
    $erro = "Erro ao carregar comuns: " . $e->getMessage();
}

// Iniciar buffer para capturar o conteúdo
ob_start();
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-info text-white">
            <i class="bi bi-building me-2"></i>
            Lista de Comuns
        </div>
        <div class="card-body">
            
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($comuns)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Nenhuma comum cadastrada no momento.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Código</th>
                                <th width="60%">Descrição</th>
                                <th width="15%" class="text-center">Produtos</th>
                                <th width="10%" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comuns as $comum): ?>
                                <?php 
                                    $total_produtos = contar_produtos_por_comum($conexao, $comum['id']);
                                    $badge_class = $total_produtos > 0 ? 'badge-success' : 'badge-secondary';
                                ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($comum['codigo']); ?></code>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($comum['descricao']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $total_produtos; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="?id=<?php echo $comum['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver detalhes">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Total: <strong><?php echo count($comuns); ?></strong> comuns cadastradas
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php
$conteudo = ob_get_clean();

// Incluir layout
require_once __DIR__ . '/../shared/layout.php';
?>
