// pwa-client.js
// Snippet para registrar SW, pedir permissão de notificações e criar a subscription
(function(){
  async function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  async function registerAndSubscribe(publicVapidKey) {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      console.warn('PWA Push não suportado neste navegador');
      return;
    }

    const registration = await navigator.serviceWorker.ready;

    // pedir permissão
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
      console.warn('Permissão para notificações negada');
      return;
    }

    // verificar se já existe subscription
    const existing = await registration.pushManager.getSubscription();
    if (existing) {
      console.log('Subscription já existente', existing);
      // opcional: enviar ao servidor novamente para garantir persistência
      await fetch('/app/pwa/save_subscription.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({subscription: existing}) });
      return existing;
    }

    // cria nova subscription
    const applicationServerKey = urlBase64ToUint8Array(publicVapidKey);
    const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey });

    // envia ao servidor
    await fetch('/app/pwa/save_subscription.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({subscription}) });
    console.log('Subscription criada e enviada', subscription);
    return subscription;
  }

  // Expor função global para uso em páginas
  window.PWAClient = {
    registerAndSubscribe
  };
})();
