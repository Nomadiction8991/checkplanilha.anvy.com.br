<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // AutenticaÃ§Ã£o
// Menu principal (sem necessidade de ID de planilha)

// ConfiguraÃ§Ãµes da pÃ¡gina
$pageTitle = "Menu";
$backUrl = '../../../index.php';

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
    <a href="../planilhas/planilha_importar.php" class="text-decoration-none">
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
    
    <div class="card menu-card disabled">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-gear-fill me-2" style="color: #6c757d;"></i>
                ConfiguraÃ§Ãµes
            </h5>
            <p class="card-text small text-muted">Funcionalidade em breve</p>
        </div>
    </div>
</div>

<?php
// Capturar o conteÃºdo
$contentHtml = ob_get_clean();

// Criar arquivo temporÃ¡rio com o conteÃºdo
$tempFile = __DIR__ . '/../../../temp_menu_principal_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

// Renderizar o layout
include __DIR__ . '/../layouts/app_wrapper.php';

// Limpar arquivo temporÃ¡rio
unlink($tempFile);
?>


