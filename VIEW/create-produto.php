<?php
require_once '../auth.php'; // Autenticação
// Redirect para nova view Bootstrap
header('Location: ../app/views/produtos/create-produto.php?' . http_build_query($_GET));
exit;
?>