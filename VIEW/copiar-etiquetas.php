<?php
require_once '../auth.php'; // Autenticação
// Redirect para nova view integrada ao layout
header('Location: ../app/views/planilhas/copiar-etiquetas.php?' . http_build_query($_GET));
exit;
?>