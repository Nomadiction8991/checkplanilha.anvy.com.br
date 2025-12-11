# Copilot Instructions for checkplanilha.anvy.com.br

## Architecture Overview
This is a PHP-based inventory management system for church assets ("planilhas" = spreadsheets). Key components:
- **CRUD Operations**: Organized in `CRUD/` with CREATE, READ, UPDATE, DELETE folders for products, users, etc.
- **Views**: Located in `app/views/`, using Bootstrap 5 layouts from `app/views/layouts/app_wrapper.php`.
- **Data Layer**: MySQL with tables: `comuns` (churches), `dependencias` (departments), `planilhas` (spreadsheets), `produtos` (products).
- **Reports**: HTML templates in `relatorios/`, filled via DOM manipulation in `app/views/planilhas/relatorio141_view.php`.

## Key Patterns
- **Product States**: Use fields like `checado` (checked), `ativo` (active), `imprimir_etiqueta` (print label), `editado` (edited) in `produtos` table.
- **Signatures**: Store base64 images in `doador_assinatura`, `doador_assinatura_conjuge`, `administrador_assinatura`.
- **Address Formatting**: Combine `doador_endereco_*` fields with commas, add CEP at end, remove trailing dashes.
- **RG/CPF Fallback**: If `doador_rg` empty and `doador_rg_igual_cpf` true, use `doador_cpf` for RG field.
- **Report Filling**: Use `r141_fillFieldById()` function to populate HTML inputs/textareas by ID.

## Workflows
- **Import Spreadsheet**: Upload CSV, parse with `app/functions/produto_parser.php`, insert into `produtos`.
- **Product Check**: Update `checado`, `ativo`, `observacao` via AJAX in `check-produto.php`.
- **Generate Report 14-1**: Fetch data in `CRUD/READ/relatorio-14-1.php`, fill template in `relatorio-14-1.php`.
- **Signatures**: Toggle between "Assinar" and "Desfazer assinatura" based on signature presence.

## Dependencies & Integrations
- **FPDF**: For PDF generation in reports.
- **PHPSpreadsheet**: For Excel file handling.
- **Composer**: Manage PHP dependencies in `vendor/`.
- **Bootstrap 5**: UI framework, custom CSS in `public/assets/css/`.

## Conventions
- **File Naming**: Use hyphens for views (e.g., `assinatura-14-1-form.php`), underscores for functions.
- **Database**: Use PDO prepared statements, bind values with `:param`.
- **Error Handling**: Use try-catch, redirect on errors.
- **AJAX**: Return JSON with `success` and `message` keys.

## Key Files
- `app/views/planilhas/planilha_visualizar.php`: Main product listing and actions.
- `CRUD/UPDATE/check-produto.php`: Update product status.
- `app/views/planilhas/relatorio141_view.php`: Report generation logic.
- `app/functions/comum_functions.php`: Shared utilities.
