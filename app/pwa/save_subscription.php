<?php
declare(strict_types=1);

// Endpoint simples para salvar Push Subscription (JSON) em arquivo local
// POST JSON: { subscription: { endpoint: ..., keys: { p256dh: ..., auth: ... } } }

// Procura autoload
$possibleAutoloads = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
foreach ($possibleAutoloads as $p) {
    if (file_exists($p)) { require_once $p; break; }
}

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Empty body']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data) || empty($data['subscription']) || empty($data['subscription']['endpoint'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid subscription payload']);
    exit;
}

$subsFile = __DIR__ . '/../../var/pwa_subscriptions.json';
$subs = [];
if (file_exists($subsFile)) {
    $content = file_get_contents($subsFile);
    $subs = json_decode($content, true) ?: [];
}

$incoming = $data['subscription'];

// dedupe by endpoint
$exists = false;
foreach ($subs as &$s) {
    if (isset($s['endpoint']) && $s['endpoint'] === $incoming['endpoint']) {
        $s = $incoming; // replace
        $exists = true;
        break;
    }
}
unset($s);

if (!$exists) {
    $subs[] = $incoming;
}

// ensure dir exists
@mkdir(dirname($subsFile), 0755, true);
file_put_contents($subsFile, json_encode($subs, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

echo json_encode(['success' => true, 'saved' => true, 'count' => count($subs)]);
