<?php
declare(strict_types=1);

// Tenta localizar o autoload do Composer (tenta caminhos relativos comuns)
$possibleAutoloads = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
$found = false;
foreach ($possibleAutoloads as $p) {
    if (file_exists($p)) {
        require_once $p;
        $found = true;
        break;
    }
}

header('Content-Type: application/json; charset=utf-8');

if (! $found) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Composer autoload not found; expected one of: ' . implode(', ', $possibleAutoloads)]);
    exit;
}

try {
    // Verifica se a classe VAPID está disponível
    if (!class_exists('\\Minishlink\\WebPush\\VAPID')) {
        throw new \RuntimeException('Classe Minishlink\\WebPush\\VAPID não encontrada via autoload. Verifique composer install.');
    }

    // Gera um par de chaves VAPID (público/privado)
    $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
    echo json_encode(['success' => true, 'keys' => $keys], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
