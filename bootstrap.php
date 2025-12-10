<?php
// Bootstrap global para o projeto
// Define PROJECT_ROOT e inclui dependências essenciais

define('PROJECT_ROOT', __DIR__);

// Incluir autoload do Composer
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Incluir configurações centrais
require_once PROJECT_ROOT . '/config.php';
