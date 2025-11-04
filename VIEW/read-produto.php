<?php
require_once __DIR__ . '/../auth.php'; // Autenticação
// Redirect para nova view Bootstrap
header('Location: ../app/views/produtos/read-produto.php?' . http_build_query($_GET));
exit;
?>