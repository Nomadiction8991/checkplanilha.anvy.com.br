---
description: "Views Bootstrap + AJAX JSON"
applyTo: "**/*.php,**/*.html,public/**/*.js,app/views/**/*.php"
---
# Views
- Usar layout `app/views/layouts/app-wrapper.php`; componentes Bootstrap 5.
- Inputs com `id` estável para o helper `r141_fillFieldById`.
- Acessibilidade básica: `label for=`, `aria-*`, foco visível.

# AJAX
- `fetch` com `method: POST`, `Content-Type: application/json`, `body: JSON.stringify(payload)`.
- Tratar `{success, message, data?}`; fallback de erro genérico + log no console em dev.
- Timeout e `try/catch` com mensagem amigável ao usuário.
