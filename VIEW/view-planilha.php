<?php
require_once __DIR__ . '/../auth.php'; // Autenticação
// Redirecionar para a nova localização
header('Location: ../app/views/planilhas/view-planilha.php?' . http_build_query($_GET));
exit;
?>
