<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';


$id_planilha = $_GET['id'] ?? null;
$contexto = $_GET['contexto'] ?? 'auto';
$origem = $_GET['origem'] ?? null;
$modo_publico = !empty($_SESSION['public_acesso']);

if ($contexto === 'auto') {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'relatorio141') !== false) {
        $contexto = 'relatorio';
    } elseif (strpos($referer, 'planilha_visualizar.php') !== false || 
              strpos($referer, 'configuracao_importacao_editar.php') !== false ||
              strpos($referer, 'produto_copiar_etiquetas.php') !== false ||
              strpos($referer, 'relatorio_imprimir_alteracao.php') !== false ||
              strpos($referer, 'produtos_listar.php') !== false) {
        $contexto = 'planilha';
    } else {
        $contexto = 'principal';
    }
}

if ($origem) {
    $backUrl = $origem;
} elseif (($contexto === 'planilha' || $contexto === 'relatorio') && $id_planilha) {
    if ($modo_publico) {
        $backUrl = '../../../public/assinatura_publica.php';
    } else {
        $backUrl = '../planilhas/planilha_visualizar.php?id=' . urlencode($id_planilha);
    }
} else {
    $backUrl = $modo_publico ? '../../../public/assinatura_publica.php' : '../../../index.php';
}

$pageTitle = "Menu";
$headerActions = '';

ob_start();
?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list me-2"></i>Menu de OpÃ§Ãµes
    </div>
    <div class="list-group list-group-flush">
        <?php if ($contexto === 'principal'): ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> PLANILHAS
            </div>
            <a href="../planilhas/planilha_importar.php" class="list-group-item list-group-item-action">
                <i class="bi bi-upload me-2"></i>Importar Planilha
            </a>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-people me-1"></i> ADMINISTRAÃ‡ÃƒO
            </div>
            <a href="../usuarios/usuarios_listar.php" class="list-group-item list-group-item-action">
                <i class="bi bi-people me-2"></i>Listagem de UsuÃ¡rios
            </a>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-gear me-1"></i> SISTEMA
            </div>
            <a href="../../../logout.php" class="list-group-item list-group-item-action text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>Sair
            </a>
        <?php endif; ?>
        
        <?php if ($contexto === 'planilha' && $id_planilha): ?>
            <?php if (!$modo_publico): ?>
                <div class="list-group-item bg-light text-muted small fw-semibold">
                    <i class="bi bi-box-seam me-1"></i> PRODUTOS
                </div>
                <a href="../produtos/produtos_listar.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-list-ul me-2"></i>Listagem de Produtos
                </a>
            <?php endif; ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-file-earmark-text me-1"></i> RELATÃ“RIOS
            </div>
            <a href="../planilhas/relatorio141_view.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-file-earmark-pdf me-2"></i>RelatÃ³rio 14.1
            </a>
            <a href="../planilhas/relatorio_imprimir_alteracao.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-file-earmark-diff me-2"></i>RelatÃ³rio de AlteraÃ§Ãµes
            </a>
            <?php if (!$modo_publico): ?>
                <div class="list-group-item bg-light text-muted small fw-semibold">
                    <i class="bi bi-three-dots me-1"></i> OUTROS
                </div>
                <a href="../planilhas/produto_copiar_etiquetas.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i>Copiar Etiquetas
                </a>
                <?php if (isAdmin()): ?>
                <a href="../planilhas/configuracao_importacao_editar.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-pencil me-2"></i>Editar Planilha
                </a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($contexto === 'relatorio' && $id_planilha): ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-compass me-1"></i> NAVEGAÃ‡ÃƒO
            </div>
            <a href="../planilhas/planilha_visualizar.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-eye me-2"></i>Ver Planilha
            </a>
            <?php if (isAdmin()): ?>
            <a href="../planilhas/configuracao_importacao_editar.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-pencil me-2"></i>Editar Planilha
            </a>
            <?php endif; ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-pen me-1"></i> ASSINATURAS
            </div>
            <a href="../planilhas/relatorio141_assinatura.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-pen me-2"></i>Assinar Documentos
            </a>
            <?php if (!$modo_publico): ?>
                <div class="list-group-item bg-light text-muted small fw-semibold">
                    <i class="bi bi-box-seam me-1"></i> PRODUTOS
                </div>
                <a href="../produtos/produtos_listar.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-plus-circle me-2"></i>Cadastrar Produto
                </a>
            <?php endif; ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-printer me-1"></i> IMPRESSÃ•ES
            </div>
            <?php if (!$modo_publico): ?>
                <a href="../planilhas/produto_copiar_etiquetas.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i>Copiar Etiquetas
                </a>
            <?php endif; ?>
            <a href="../planilhas/relatorio_imprimir_alteracao.php?id=<?php echo $id_planilha; ?>" class="list-group-item list-group-item-action">
                <i class="bi bi-file-earmark-diff me-2"></i>Imprimir AlteraÃ§Ãµes
            </a>
        <?php endif; ?>
        
        <?php if ($modo_publico): ?>
            <div class="list-group-item bg-light text-muted small fw-semibold">
                <i class="bi bi-gear me-1"></i> SISTEMA
            </div>
            <a href="../../../public/logout_publico.php" class="list-group-item list-group-item-action text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>Sair
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_menu_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>


