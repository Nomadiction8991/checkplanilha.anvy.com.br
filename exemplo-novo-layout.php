<?php
// Exemplo de uso do layout base
$pageTitle = "Lista de Planilhas";
$backUrl = null; // Sem botão voltar na home
$headerActions = '
    <a href="exemplo-criar.php" class="btn-header-action" title="Adicionar">
        <i class="bi bi-plus-lg fs-5"></i>
    </a>
';

// Incluir o conteúdo da página
$contentFile = __DIR__ . '/content-exemplo.php';

// Renderizar o layout
include __DIR__ . '/../app/views/layouts/app-wrapper.php';
?>
