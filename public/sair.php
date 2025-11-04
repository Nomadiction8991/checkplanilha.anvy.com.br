<?php
session_start();
// Limpar somente dados do modo público
unset($_SESSION['public_acesso'], $_SESSION['public_planilha_id'], $_SESSION['public_comum']);
header('Location: ../login.php');
exit;
