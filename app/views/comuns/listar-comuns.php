<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação
require_once PROJECT_ROOT . '/CRUD/conexao.php';
require_once PROJECT_ROOT . '/app/functions/comum_functions.php';

// Configurações da página
$pageTitle = 'Gerenciar Comuns';
$backUrl = '../shared/menu.php';
$headerActions = '
    <a href="../shared/menu.php" class="btn-header-action" title="Menu">
        <i class="bi bi-list fs-5"></i>
    </a>
';

// Paginação de comuns
$pagina = isset($_GET['pagina']) ? max(1,(int)$_GET['pagina']) : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

// Obter total
try {
    $total_registros = (int)$conexao->query("SELECT COUNT(*) FROM comums")->fetchColumn();
    $total_paginas = (int)ceil($total_registros / $limite);
} catch (Exception $e) {
    $total_registros = 0;
    $total_paginas = 1;
    $erro = "Erro ao contar comuns: " . $e->getMessage();
}

// Obter página atual
try {
    $stmt_comuns = $conexao->prepare("SELECT id, codigo, cnpj, descricao, administracao, cidade, setor FROM comums ORDER BY codigo ASC LIMIT :limite OFFSET :offset");
    $stmt_comuns->bindValue(':limite',$limite,PDO::PARAM_INT);
    $stmt_comuns->bindValue(':offset',$offset,PDO::PARAM_INT);
    $stmt_comuns->execute();
    $comuns = $stmt_comuns->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $comuns = [];
    $erro = "Erro ao carregar comuns: " . $e->getMessage();
}

// Iniciar buffer para capturar o conteúdo
ob_start();
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <span>
            <i class="bi bi-building me-2"></i>
            Lista de Comuns
            </span>
            <span class="badge bg-white text-dark"><?php echo $total_registros; ?> itens (pág. <?php echo $pagina; ?>/<?php echo $total_paginas ?: 1; ?>)</span>
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

                <div class="mt-3 text-muted small d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-info-circle me-1"></i>
                        Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?> | Exibindo <strong><?php echo count($comuns); ?></strong> de <strong><?php echo $total_registros; ?></strong>
                    </span>
                </div>

                <?php if($total_paginas > 1): ?>
                <nav class="mt-2" aria-label="Paginação comuns">
                  <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php if($pagina > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$pagina-1])); ?>">&laquo;</a></li>
                    <?php endif; ?>
                    <?php $ini = max(1,$pagina-2); $fim = min($total_paginas,$pagina+2); for($i=$ini;$i<=$fim;$i++): ?>
                      <li class="page-item <?php echo $i==$pagina?'active':''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$i])); ?>"><?php echo $i; ?></a>
                      </li>
                    <?php endfor; ?>
                    <?php if($pagina < $total_paginas): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET,['pagina'=>$pagina+1])); ?>">&raquo;</a></li>
                    <?php endif; ?>
                  </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php
$conteudo = ob_get_clean();

// Incluir layout
require_once PROJECT_ROOT . '/app/views/shared/layout.php';
?>
