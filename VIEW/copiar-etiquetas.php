<?php
require_once __DIR__ . '/../auth.php'; // Autenticação
// Redirect para nova view integrada ao layout
header('Location: ../app/views/planilhas/copiar-etiquetas.php?' . http_build_query($_GET));
exit;
?>