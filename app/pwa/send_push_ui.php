<?php
declare(strict_types=1);
$vapidFile = __DIR__ . '/vapid.json';
$adminToken = '';
if (file_exists($vapidFile)) {
    $v = json_decode(file_get_contents($vapidFile), true) ?: [];
    $adminToken = $v['adminToken'] ?? '';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Enviar Push - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h3>Enviar notificação para todas subscriptions</h3>
    <p class="text-muted">Insira o token admin (preenchido automaticamente se presente em app/pwa/vapid.json).</p>
    <form id="pushForm">
      <div class="mb-3">
        <label class="form-label">Token admin</label>
        <input type="text" id="token" class="form-control" value="<?php echo htmlspecialchars($adminToken); ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Título</label>
        <input type="text" id="title" class="form-control" value="Teste de notificação">
      </div>
      <div class="mb-3">
        <label class="form-label">Mensagem</label>
        <textarea id="body" class="form-control">Olá do servidor</textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">URL (abertura)</label>
        <input type="text" id="url" class="form-control" value="/index.php">
      </div>
      <button type="submit" class="btn btn-primary">Enviar para todas</button>
    </form>

    <hr>
    <pre id="result" style="white-space:pre-wrap; background:#f8f9fa; padding:12px; border-radius:8px; margin-top:12px;"></pre>
  </div>

  <script>
    document.getElementById('pushForm').addEventListener('submit', async function(e){
      e.preventDefault();
      const token = document.getElementById('token').value.trim();
      const title = document.getElementById('title').value;
      const body = document.getElementById('body').value;
      const url = document.getElementById('url').value;

      const res = await fetch('/app/pwa/send_push_all.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ token, title, body, url })
      });
      const json = await res.json().catch(()=>({error:'invalid json'}));
      document.getElementById('result').textContent = JSON.stringify(json, null, 2);
    });
  </script>
</body>
</html>
