<?php
$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: ../../index.php');
    exit;
}

// Configurações da página
$pageTitle = "Menu";
$backUrl = '../planilhas/view-planilha.php?id=' . $id_planilha;

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
</style>

<div class="menu-grid">
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
// Capturar o conteúdo
$contentHtml = ob_get_clean();

// Criar arquivo temporário com o conteúdo
$tempFile = __DIR__ . '/../../../temp_menu_content_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

// Renderizar o layout
include __DIR__ . '/../layouts/app-wrapper.php';

// Limpar arquivo temporário
unlink($tempFile);
?>
