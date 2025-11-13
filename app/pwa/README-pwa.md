Guia rápido PWA / Web Push (CheckPlanilha)
=======================================

Este diretório contém utilitários e instruções para integrar Push Notifications (Web Push / VAPID) ao PWA.

1) Gerar chaves VAPID (server)
--------------------------------
- Endpoint de exemplo: `app/pwa/generate_vapid.php`
- Acesse via navegador: `https://seu-host/app/pwa/generate_vapid.php` para obter um par `publicKey` / `privateKey`.
 
Armazene essas chaves com segurança. Duas opções:

- Variáveis de ambiente (recomendado):
  - VAPID_PUBLIC e VAPID_PRIVATE
- Arquivo local (apenas para desenvolvimento):
  - Crie `app/pwa/vapid.json` com o formato:

```json
{
  "publicKey": "SUA_PUBLIC_KEY",
  "privateKey": "SUA_PRIVATE_KEY"
}
```

> Importante: nunca commite `app/pwa/vapid.json` com chaves reais em um repositório público.

2) Fluxo cliente (JS)
----------------------
- Registre o service worker (já acontece em `app/views/layouts/app-wrapper.php`).
- Depois que o SW estiver pronto, solicite permissão e faça a subscribe:

```javascript
const publicVapidKey = 'SUA_PUBLIC_KEY_AQUI'; // pegue do servidor (VAPID public)

if ('serviceWorker' in navigator && 'PushManager' in window) {
  navigator.serviceWorker.ready.then(async registration => {
    try {
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(publicVapidKey)
      });
      // Envie `subscription` ao seu servidor (ex: /app/pwa/save_subscription.php)
      await fetch('/app/pwa/save_subscription.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(subscription)
      });
    } catch (err) {
      console.error('Erro ao registrar subscription:', err);
    }
  });
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}
```

3) Envio (server) — breve exemplo usando minishlink/web-push
-----------------------------------------------------------
- Use as chaves VAPID geradas anteriormente. Exemplo conceitual (não commitado automaticamente):

```php
use Minishlink\\WebPush\\WebPush;
use Minishlink\\WebPush\\Subscription;

$vapid = [
  'VAPID' => [
    'subject' => 'mailto:seu@dominio.com',
    'publicKey' => getenv('VAPID_PUBLIC'),
    'privateKey' => getenv('VAPID_PRIVATE')
  ]
];

$webPush = new WebPush($vapid);
$subscription = Subscription::create($subscriptionArray); // recebido do cliente
$report = $webPush->sendOneNotification($subscription, json_encode(['title'=>'Teste','body'=>'Olá do servidor']));

// Verifique $report para sucesso/falhas
```

4) Service Worker
-----------------
- `sw.js` já contém handlers para `push` e `notificationclick` (exibição básica de notificações). Ajuste ícones, actions e comportamento conforme necessário.

5) Testes e debugging
---------------------
- Use `chrome://inspect` (com Android via USB) para inspecionar logs e Service Worker.
- Verifique Application → Service Workers e Application → Push Subscriptions.

Se quiser, eu crio endpoints adicionais para salvar a subscription no banco e um exemplo seguro de envio de notificação de teste. Solicite "Enviar endpoints" e eu gero os arquivos com exemplos prontos.
