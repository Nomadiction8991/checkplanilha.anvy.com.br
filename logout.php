<?php
require_once __DIR__ . '/auth.php';
session_start();

// Destruir sessão
session_destroy();

// Redirecionar para login
header('Location: ' . getLoginUrl());
exit;
