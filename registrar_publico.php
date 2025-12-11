<?php
define('SKIP_AUTH', true);
require_once __DIR__ . '/app/bootstrap.php';

// Redireciona para o create-usuario.php com parâmetro indicando registro público
header('Location: app/views/usuarios/usuario_criar.php?public=1');
exit;

