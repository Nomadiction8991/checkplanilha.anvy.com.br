<?php
/**
 * Inicializa as dependencias principais para controllers e views.
 * Carrega bootstrap, conexao com banco e helpers compartilhados.
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers/auth_helper.php';
require_once __DIR__ . '/helpers/comum_helper.php';

