# Copilot Instructions for checkplanilha.anvy.com.br

## Project Overview

**Check Planilha** is a PHP 8.1+ application for managing spreadsheet imports and product verification ("bens imóveis"). Users can import assets, mark items as checked, assign signatures, and generate compliance reports (Form 14.1).

### Architecture: MVC-lite with PHP (No Framework)
- **Backend**: Pure PHP with PDO (MySQL/MariaDB)
- **Frontend**: Bootstrap 5 + Vanilla JS (AJAX requests)
- **Entry Points**: Top-level `.php` files + `app/` directory
- **Controllers**: Located in `app/controllers/{create,read,update,delete}/`
- **Views**: Located in `app/views/{planilhas,produtos,usuarios,dependencias}/`

---

## Critical Design Patterns

### 1. UTF-8 Enforcement (Mandatory)
The application requires strict UTF-8 encoding end-to-end:
- `config/bootstrap.php` forces UTF-8 headers, charset, and `mb_*` functions
- PDO uses `utf8mb4` collation with `SET NAMES utf8mb4`
- **When modifying database queries**: Always ensure charset configuration in `Database::getConnection()`
- **When outputting HTML**: Use `htmlspecialchars()` with `ENT_QUOTES, 'UTF-8'`
- Encoding issues appear as garbled accents—validate with sample Portuguese text ("Cuiabá", "Bens Imóveis")

### 2. ID Mapping Convention (Comum vs. Planilha)
Critical distinction in parameter naming:
- `comum_id`: References the `comuns` table (primary semantic unit)
- `planilha_id` / `id_planilha`: Legacy alias for `comum_id`
- **When creating URLs**: Use `?id=<comum_id>&comum_id=<comum_id>` to support both parameter names (backward compatibility)
- **In database queries**: Join via `comuns` table using its primary key
- See `app/views/planilhas/planilha_visualizar.php:5-6` for pattern

### 3. AJAX Response Pattern
All controllers support both HTML redirects and JSON responses:
```php
if (is_ajax_request()) {
    json_response(['success' => true, 'message' => 'Done'], 200);
}
header('Location: ...');
```
- Detect AJAX via `is_ajax_request()` (checks `X-Requested-With` or `Accept: application/json`)
- Return JSON with `json_response(array $payload, int $statusCode)` from `config/bootstrap.php`
- Frontend preserves filter parameters (`?nome=X&dependencia=Y&pagina=Z`) across AJAX calls
- **Example**: `app/controllers/update/ProdutoCheckController.php`

### 4. Product Parser Service (Fuzzy Matching)
The `app/services/produto_parser_service.php` implements intelligent asset type matching:
- `pp_normaliza($str)`: Normalizes to uppercase ASCII, strips accents
- `pp_gerar_variacoes($str)`: Generates plural/singular variations (e.g., "CADEIRA" ↔ "CADEIRAS")
- `pp_match_fuzzy($str1, $str2)`: Compares considering variations
- **Detection of repeated aliases**: If an alias appears 2+ times in product description, it gets priority
- Used during CSV import to auto-detect product types and aliases

---

## Authentication & Authorization

### Session-Based Security
- Sessions initialized in `config/bootstrap.php` with `HttpOnly`, `SameSite=Lax` cookies
- **Auth checks**: Include `app/helpers/auth_helper.php` in protected pages
- Middleware allows public access to specific pages via `$_SESSION['public_acesso']` flag (see form signature endpoints)

### User Roles
1. **Administrador/Acessor**: Full permissions (import, edit, sign, generate reports)
2. **Doador/Cônjuge**: View-only + sign products (can access report endpoints)
3. **Detection**: Use `isAdmin()` and `isDoador()` helpers from `auth_helper.php`

### Password Handling
- Stored as salted SHA-256 in `usuarios.senha`
- ⚠️ **Known limitation**: No bcrypt used; consider upgrading in future refactors

---

## Data Flow: Product Import → Signature → Report

### Import Workflow
1. **Route**: `app/views/planilhas/planilha_importar.php` (form)
2. **Controller**: `app/controllers/create/` (expects POST with CSV file + configuration)
3. **Parser**: `app/services/produto_parser_service.php` parses CSV, matches types
4. **Storage**: Insert into `produtos` table with `comum_id` FK

### Product Actions (AJAX-enabled)
- **Mark as checked**: `app/controllers/update/ProdutoCheckController.php` → toggles `checado`
- **Add label/etiqueta**: `app/controllers/update/ProdutoEtiquetaController.php`
- **Sign (14.1)**: `app/controllers/update/ProdutoAssinar141Controller.php` → sets `administrador_acessor_id` / `doador_conjugue_id`

### Report Generation
- **Form 14.1**: `app/controllers/read/Relatorio141GenerateController.php`
- Uses FPDF/FPDI to fill PDF forms with product + signature data
- Query joins `produtos` → `usuarios` (admin) + `usuarios` (donor)
- See `Relatorio141DataController.php` for data retrieval pattern

---

## Directory Structure & File Purposes

```
config/           # Initialization: bootstrap.php (UTF-8, sessions), database.php (PDO), app_config.php (URLs)
app/
  bootstrap.php   # Loader (requires all config files)
  controllers/
    {create,read,update,delete}/  # One file per action, stateless logic
  helpers/        # auth_helper.php (middleware), comum_helper.php (domain logic)
  models/         # (mostly empty; inline query logic in controllers)
  services/       # produto_parser_service.php (import parsing), Relatorio141Generator.php (PDF)
  views/          # HTML templates organized by domain (planilhas/, produtos/, usuarios/)
public/           # Public-facing pages (form signatures, logout)
relatorios/       # Generated report assets (14-1.html template)
database/         # SQL migrations (add new fields here)
scripts/          # CLI utilities for testing/debugging imports
```

---

## Common Tasks

### Add a Database Field
1. Create migration in `database/migrations/`
2. Run manually or via script
3. Update relevant controller queries in `app/controllers/`
4. Add form input in corresponding view

### Add a Product Action (AJAX)
1. Create `app/controllers/update/Produto{Action}Controller.php`
2. Check `REQUEST_METHOD === 'POST'`
3. Call `json_response()` for AJAX; `header('Location:')` for non-AJAX
4. Link from view with `<form method="POST" action="..." accept-application/json">`

### Fix Encoding Issues
- Check `config/bootstrap.php` is included first
- Verify `<meta charset="UTF-8">` in HTML
- Ensure PDO has `utf8mb4` charset in DSN and `SET NAMES`
- Test with accented characters: "Cuiabá", "Bens Imóveis", "Cônjuge"

### Debug Product Import
- Test scripts in `scripts/`: `simular-importacao.php`, `test-parser.php`
- Parser normalizes via `pp_normaliza()` before matching

---

## Environment & Deployment

### Local Development
```bash
composer install
php -S localhost:8000 -t .
```
Navigate to `http://localhost:8000/login.php`

### Configuration
- **Timezone**: Set in `config/bootstrap.php` (currently `America/Cuiaba`)
- **Database**: Defaults in `config/database.php`; override via env vars: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- **URLs**: `config/app_config.php` supports `/dev` and `/prod` prefixes for multi-environment routing

### Build/Run
- No build step required (PHP is interpreted)
- Use `msbuild` task defined in `.vscode/tasks.json` for CI/integration (if needed)

---

## Key Files to Know

| File | Purpose |
|------|---------|
| `config/bootstrap.php` | UTF-8, sessions, AJAX detection, error logging |
| `config/database.php` | PDO initialization, connection pooling |
| `app/helpers/auth_helper.php` | Authentication middleware, role checks |
| `app/services/produto_parser_service.php` | CSV import parsing, fuzzy type matching |
| `app/views/planilhas/planilha_visualizar.php` | Main product grid + AJAX interactions |
| `app/controllers/read/Relatorio141DataController.php` | Query pattern for report data (joins + signatures) |
| `app/controllers/update/ProdutoCheckController.php` | AJAX update pattern (check, label, etc.) |

---

## Known Limitations & Future Work

- ⚠️ No framework (tightly coupled, limited DRY)
- ⚠️ Password hashing uses SHA-256 (upgrade to bcrypt)
- ⚠️ No automated tests
- ✅ Recent improvements: UTF-8 guarantees, AJAX actions, fuzzy parser, product signing
- 📋 Enhancements documented in `MELHORIAS-IMPLEMENTADAS.md`, `SISTEMA-ASSINATURA-PRODUTOS.md`

---

## Questions to Ask When Unsure

1. **Which `comum_id` param should I use?** → Check URL pattern in similar views; use `?id=X&comum_id=X`
2. **Will this affect UTF-8?** → Check if raw SQL, PDO charset, headers, or `htmlspecialchars()` involved
3. **Should this return JSON or redirect?** → Use `is_ajax_request()` to detect
4. **Where do I store logs?** → Nenhum. Por política atual, não gravar logs do agente no repositório.
5. **Is there a helper for this domain logic?** → Check `app/helpers/` before writing inline
