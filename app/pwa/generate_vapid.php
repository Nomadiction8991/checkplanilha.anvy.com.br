<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Minishlink\WebPush\VAPID;

header('Content-Type: application/json; charset=utf-8');

try {
    // Gera um par de chaves VAPID (público/privado)
    $keys = VAPID::createVapidKeys();
    echo json_encode(['success' => true, 'keys' => $keys], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
