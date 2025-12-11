---
description: "Views Bootstrap + AJAX JSON"
applyTo: "**/*.php,**/*.html,public/**/*.js,app/views/**/*.php"
---
# Views
- Usar layout `app/views/layouts/app_wrapper.php`; componentes Bootstrap 5.
- Inputs com `id` estÃ¡vel para o helper `r141_fillFieldById`.
- Acessibilidade bÃ¡sica: `label for=`, `aria-*`, foco visÃ­vel.

# AJAX
- `fetch` com `method: POST`, `Content-Type: application/json`, `body: JSON.stringify(payload)`.
- Tratar `{success, message, data?}`; fallback de erro genÃ©rico + log no console em dev.
- Timeout e `try/catch` com mensagem amigÃ¡vel ao usuÃ¡rio.

