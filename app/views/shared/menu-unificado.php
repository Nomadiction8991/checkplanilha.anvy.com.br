<?php
/**
 * Menu Unificado - Mostra opções baseadas no contexto
 * 
 * Parâmetros aceitos via GET:
 * - id: ID da planilha (opcional, usado em contextos de planilha específica)
 * - contexto: 'principal', 'planilha', 'relatorio' (auto-detectado se não fornecido)
 * - origem: URL da página que chamou o menu (para voltar)
 */

$id_planilha = $_GET['id'] ?? null;
$contexto = $_GET['contexto'] ?? 'auto';
$origem = $_GET['origem'] ?? null;

// Auto-detectar contexto baseado no referer se não foi especificado
if ($contexto === 'auto') {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    if (strpos($referer, 'relatorio-14-1.php') !== false) {
        $contexto = 'relatorio';
    } elseif (strpos($referer, 'view-planilha.php') !== false || 
              strpos($referer, 'editar-planilha.php') !== false ||
              strpos($referer, 'copiar-etiquetas.php') !== false ||
              strpos($referer, 'imprimir-alteracao.php') !== false ||
              strpos($referer, 'read-produto.php') !== false) {
        $contexto = 'planilha';
    } else {
        $contexto = 'principal';
    }
}

// Determinar URL de retorno
// - Se origem informada, usa origem
// - Se contexto de planilha/relatório, voltar para view-planilha
// - Se contexto principal (menu do index), voltar para index
if ($origem) {
    $backUrl = $origem;
} elseif (($contexto === 'planilha' || $contexto === 'relatorio') && $id_planilha) {
    $backUrl = '../planilhas/view-planilha.php?id=' . urlencode($id_planilha);
} else {
    // Menu principal
    $backUrl = '../../../index.php';
}

// Configurações da página
$pageTitle = "Menu";
$headerActions = '';

// Iniciar buffer para capturar o conteúdo
ob_start();
?>

<style>
.menu-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.menu-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.menu-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.menu-card.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.menu-card.disabled:hover {
    transform: none;
}

.menu-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 1.5rem 0 0.75rem 0;
    padding-left: 0.25rem;
}

.menu-section-title:first-child {
    margin-top: 0;
}
</style>

<div class="menu-grid">
    
    <?php if ($contexto === 'principal' || $contexto === 'planilha'): ?>
        <!-- Seção: Planilhas -->
        <?php if ($contexto === 'principal'): ?>
            <div class="menu-section-title">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                Planilhas
            </div>
        <?php endif; ?>
        
        <a href="../planilhas/importar-planilha.php" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-file-earmark-arrow-up-fill me-2" style="color: #28a745;"></i>
                        Importar Planilha
                    </h5>
                    <p class="card-text small text-muted">Importar nova planilha CSV</p>
                </div>
            </div>
        </a>
    <?php endif; ?>
    
    <?php if ($contexto === 'planilha' && $id_planilha): ?>
        <!-- Seção: Produtos -->
        <div class="menu-section-title">
            <i class="bi bi-box-seam me-1"></i>
            Produtos
        </div>
        
        <a href="../produtos/read-produto.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-plus-circle-fill me-2" style="color: #28a745;"></i>
                        Cadastrar Produto
                    </h5>
                    <p class="card-text small text-muted">Adicionar novo produto manualmente</p>
                </div>
            </div>
        </a>
        
        <!-- Seção: Relatórios e Impressões -->
        <div class="menu-section-title">
            <i class="bi bi-printer me-1"></i>
            Relatórios e Impressões
        </div>
        
        <a href="../planilhas/relatorio-14-1.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-printer-fill me-2" style="color: #17a2b8;"></i>
                        Imprimir 14.1
                    </h5>
                    <p class="card-text small text-muted">Gerar relatório 14.1</p>
                </div>
            </div>
        </a>
        
        <a href="../planilhas/copiar-etiquetas.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-tags-fill me-2" style="color: #ff9800;"></i>
                        Copiar Etiquetas
                    </h5>
                    <p class="card-text small text-muted">Copiar etiquetas selecionadas</p>
                </div>
            </div>
        </a>
        
        <a href="../planilhas/imprimir-alteracao.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-file-earmark-diff-fill me-2" style="color: #9c27b0;"></i>
                        Imprimir Alterações
                    </h5>
                    <p class="card-text small text-muted">Relatório de alterações realizadas</p>
                </div>
            </div>
        </a>
    <?php endif; ?>
    
    <?php if ($contexto === 'relatorio' && $id_planilha): ?>
        <!-- Seção: Navegação -->
        <div class="menu-section-title">
            <i class="bi bi-compass me-1"></i>
            Navegação
        </div>
        
        <a href="../planilhas/view-planilha.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-eye-fill me-2" style="color: #007bff;"></i>
                        Ver Planilha
                    </h5>
                    <p class="card-text small text-muted">Visualizar produtos da planilha</p>
                </div>
            </div>
        </a>
        
        <a href="../planilhas/editar-planilha.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-pencil-fill me-2" style="color: #6c757d;"></i>
                        Editar Planilha
                    </h5>
                    <p class="card-text small text-muted">Editar configurações da planilha</p>
                </div>
            </div>
        </a>
        
        <!-- Seção: Assinaturas -->
        <div class="menu-section-title">
            <i class="bi bi-pen me-1"></i>
            Assinaturas Digitais
        </div>
        
        <a href="../planilhas/assinatura-14-1.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-pen-fill me-2" style="color: #6610f2;"></i>
                        Assinar Documentos
                    </h5>
                    <p class="card-text small text-muted">Gerenciar assinaturas do relatório 14.1</p>
                </div>
            </div>
        </a>
        
        <!-- Seção: Produtos -->
        <div class="menu-section-title">
            <i class="bi bi-box-seam me-1"></i>
            Produtos
        </div>
        
        <a href="../produtos/read-produto.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-plus-circle-fill me-2" style="color: #28a745;"></i>
                        Cadastrar Produto
                    </h5>
                    <p class="card-text small text-muted">Adicionar novo produto manualmente</p>
                </div>
            </div>
        </a>
        
        <!-- Seção: Outras Impressões -->
        <div class="menu-section-title">
            <i class="bi bi-printer me-1"></i>
            Outras Impressões
        </div>
        
        <a href="../planilhas/copiar-etiquetas.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-tags-fill me-2" style="color: #ff9800;"></i>
                        Copiar Etiquetas
                    </h5>
                    <p class="card-text small text-muted">Copiar etiquetas selecionadas</p>
                </div>
            </div>
        </a>
        
        <a href="../planilhas/imprimir-alteracao.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
            <div class="card menu-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-file-earmark-diff-fill me-2" style="color: #9c27b0;"></i>
                        Imprimir Alterações
                    </h5>
                    <p class="card-text small text-muted">Relatório de alterações realizadas</p>
                </div>
            </div>
        </a>
    <?php endif; ?>
    
    <?php if ($contexto === 'principal'): ?>
        <!-- Seção Sistema removida conforme solicitação -->
    <?php endif; ?>
    
    <?php if (($contexto === 'planilha' || $contexto === 'relatorio') && !$id_planilha): ?>
        <!-- Aviso quando não há ID de planilha -->
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Algumas opções requerem uma planilha selecionada.
        </div>
    <?php endif; ?>
</div>

<?php
// Capturar o conteúdo
$contentHtml = ob_get_clean();

// Criar arquivo temporário com o conteúdo
$tempFile = __DIR__ . '/../../../temp_menu_unificado_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

// Renderizar o layout
include __DIR__ . '/../layouts/app-wrapper.php';

// Limpar arquivo temporário
unlink($tempFile);
?>
