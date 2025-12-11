<?php
session_start();

// Destruir sessão
session_destroy();

// Redirecionar para login
header('Location: login.php');
exit;
