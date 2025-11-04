<?php
require_once '../auth.php'; // Autenticação
// Redirect para nova página standalone de impressão
header('Location: ../app/views/planilhas/relatorio-14-1.php?' . http_build_query($_GET));
exit;
?>