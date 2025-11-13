<?php
declare(strict_types=1);

// Envia push para todas as subscriptions salvas em var/pwa_subscriptions.json
// Protegido por token simples: envie POST { title, body, url, token }

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
if (! $found) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Composer autoload not found']); exit; }

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$token = $data['token'] ?? ($_SERVER['HTTP_X_PWA_TOKEN'] ?? null);

// load admin token from env or vapid.json
$adminToken = getenv('PWA_ADMIN_TOKEN') ?: null;
$vapidFile = __DIR__ . '/vapid.json';
if (file_exists($vapidFile)) {
    $v = json_decode(file_get_contents($vapidFile), true) ?: [];
    $adminToken = $adminToken ?: ($v['adminToken'] ?? null);
}

if (!$adminToken || !$token || !hash_equals($adminToken, $token)) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Invalid token']);
    exit;
}

$title = $data['title'] ?? 'Notificação em massa';
$body = $data['body'] ?? '';
$url = $data['url'] ?? '/';

// load vapid keys
$vapidPublic = getenv('VAPID_PUBLIC') ?: null;
$vapidPrivate = getenv('VAPID_PRIVATE') ?: null;
if ((!$vapidPublic || !$vapidPrivate) && file_exists($vapidFile)) {
    $v = json_decode(file_get_contents($vapidFile), true) ?: [];
    $vapidPublic = $vapidPublic ?: ($v['publicKey'] ?? null);
    $vapidPrivate = $vapidPrivate ?: ($v['privateKey'] ?? null);
}
if (!$vapidPublic || !$vapidPrivate) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'VAPID keys not set']); exit; }

$subsFile = __DIR__ . '/../../var/pwa_subscriptions.json';
if (!file_exists($subsFile)) { echo json_encode(['success'=>true,'sent'=>0,'message'=>'No subscriptions']); exit; }
$subs = json_decode(file_get_contents($subsFile), true) ?: [];

$auth = ['VAPID'=>['subject'=>'mailto:seu@dominio.com','publicKey'=>$vapidPublic,'privateKey'=>$vapidPrivate]];
$webPush = new WebPush($auth);

$payload = json_encode(['title'=>$title,'body'=>$body,'url'=>$url]);

$reports = [];
foreach ($subs as $s) {
    try {
        $subscription = Subscription::create($s);
        $reportIterable = $webPush->sendOneNotification($subscription, $payload);
        foreach ($reportIterable as $r) {
            $reports[] = [
                'endpoint' => $s['endpoint'] ?? null,
                'success' => $r->isSuccess(),
                'status' => method_exists($r, 'getStatusCode') ? $r->getStatusCode() : null,
                'reason' => ($r->getReason() ? $r->getReason()->getReasonPhrase() : null),
            ];
        }
    } catch (\Throwable $e) {
        $reports[] = ['endpoint'=> $s['endpoint'] ?? null, 'success'=>false, 'error'=>$e->getMessage()];
    }
}

echo json_encode(['success'=>true,'sent'=>count($reports),'reports'=>$reports]);
