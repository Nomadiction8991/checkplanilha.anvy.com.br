<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AutenticaÃ§Ã£o
$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../../index.php');
    exit;
}

// ConfiguraÃ§Ãµes da pÃ¡gina
$pageTitle = "Menu";
$backUrl = '../planilhas/planilha_visualizar.php?id=' . $id_planilha;

// Iniciar buffer para capturar o conteÃºdo
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
</style>

<div class="menu-grid">
    <a href="../produtos/produtos_listar.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
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
    
    <a href="../planilhas/planilha_importar.php" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark-arrow-up-fill me-2" style="color: #28a745;"></i>
                    Importar Nova Planilha
                </h5>
                <p class="card-text small text-muted">Importar uma nova planilha CSV</p>
            </div>
        </div>
    </a>
    
    <a href="../planilhas/relatorio141_view.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-printer-fill me-2" style="color: #17a2b8;"></i>
                    Imprimir 14.1
                </h5>
                <p class="card-text small text-muted">Gerar relatÃ³rio 14.1</p>
            </div>
        </div>
    </a>
    
    <a href="../planilhas/produto_copiar_etiquetas.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
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
    
    <a href="../planilhas/relatorio_imprimir_alteracao.php?id=<?php echo $id_planilha; ?>" class="text-decoration-none">
        <div class="card menu-card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-file-earmark-diff-fill me-2" style="color: #9c27b0;"></i>
                    Imprimir AlteraÃ§Ãµes
                </h5>
                <p class="card-text small text-muted">RelatÃ³rio de alteraÃ§Ãµes realizadas</p>
            </div>
        </div>
    </a>
    
    <div class="card menu-card disabled">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-gear-fill me-2" style="color: #6c757d;"></i>
                Em Desenvolvimento
            </h5>
            <p class="card-text small text-muted">Funcionalidade em breve</p>
        </div>
    </div>
</div>

<?php
// Capturar o conteÃºdo
$contentHtml = ob_get_clean();

// Criar arquivo temporÃ¡rio com o conteÃºdo
$tempFile = __DIR__ . '/../../../temp_menu_content_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

// Renderizar o layout
include __DIR__ . '/../layouts/app_wrapper.php';

// Limpar arquivo temporÃ¡rio
unlink($tempFile);
?>


