<?php
declare(strict_types=1);

// Envia uma notificação Web Push para uma subscription fornecida no POST.
// POST JSON: { subscription: {...}, title: 'Título', body: 'Mensagem', url: '/index.php' }

// Procura autoload
$possibleAutoloads = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
$found = false;
foreach ($possibleAutoloads as $p) {
    if (file_exists($p)) { require_once $p; $found = true; break; }
}

header('Content-Type: application/json; charset=utf-8');

if (! $found) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Composer autoload not found']);
    exit;
}

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

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

$subscriptionArray = $data['subscription'];
$title = $data['title'] ?? 'Notificação';
$body = $data['body'] ?? '';
$url = $data['url'] ?? '/';

// Carregar chaves VAPID: prioriza variáveis de ambiente, depois arquivo app/pwa/vapid.json
$vapidPublic = getenv('VAPID_PUBLIC') ?: null;
$vapidPrivate = getenv('VAPID_PRIVATE') ?: null;
$vapidFile = __DIR__ . '/vapid.json';
if ((!$vapidPublic || !$vapidPrivate) && file_exists($vapidFile)) {
    $v = json_decode(file_get_contents($vapidFile), true) ?: [];
    $vapidPublic = $v['publicKey'] ?? $vapidPublic;
    $vapidPrivate = $v['privateKey'] ?? $vapidPrivate;
}

if (!$vapidPublic || !$vapidPrivate) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'VAPID keys not set. Set VAPID_PUBLIC and VAPID_PRIVATE env vars or create app/pwa/vapid.json']);
    exit;
}

$auth = [
    'VAPID' => [
        'subject' => 'mailto:seu@dominio.com',
        'publicKey' => $vapidPublic,
        'privateKey' => $vapidPrivate,
    ],
];

$payload = ['title' => $title, 'body' => $body, 'url' => $url];

$webPush = new WebPush($auth);
$subscription = Subscription::create($subscriptionArray);

$report = $webPush->sendOneNotification($subscription, json_encode($payload));

$result = [];
foreach ($report as $r) {
    $result[] = [
        'success' => $r->isSuccess(),
        'statusCode' => $r->getStatusCode(),
        'reason' => $r->getReason() ? $r->getReason()->getReasonPhrase() : null,
    ];
}

echo json_encode(['success' => true, 'report' => $result]);
