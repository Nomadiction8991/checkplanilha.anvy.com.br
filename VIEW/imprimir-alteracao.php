<?php
require_once __DIR__ . '/../auth.php'; // Autenticação
// Redirecionamento simples para a nova página de impressão
header('Location: ../app/views/planilhas/imprimir-alteracao.php?' . http_build_query($_GET));
exit;
?>