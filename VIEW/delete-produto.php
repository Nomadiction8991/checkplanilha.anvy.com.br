<?php
// Redirect para nova view Bootstrap
header('Location: ../app/views/produtos/delete-produto.php?' . http_build_query($_GET));
exit;
?>