---
description: "Regras do projeto checkplanilha.anvy.com.br"
applyTo: "app/**,CRUD/**,relatorios/**,public/**,composer.json"
---
# Arquitetura (resumo)
- **PHP** (Bootstrap 5 nas views em `app/views/` via `layouts/app_wrapper.php`).
- **CRUD/** dividido em CREATE/READ/UPDATE/DELETE.
- **MySQL**: tabelas `comuns` (igrejas), `dependencias` (departamentos), `planilhas` (spreadsheets), `produtos` (itens).
- **RelatÃ³rios**: HTML em `relatorios/`, preenchidos em `app/views/planilhas/relatorio141_view.php` (DOM helper).
- **Bibliotecas**: FPDF (PDF), PHPSpreadsheet (Excel), Composer (vendor).

# PadrÃµes de domÃ­nio
- **Estados do produto (tabela `produtos`)**:
  - `checado` (0/1), `ativo` (0/1), `imprimir_etiqueta` (0/1), `editado` (0/1), `observacao` (TEXT).
  - AtualizaÃ§Ãµes via AJAX em `CRUD/UPDATE/check-produto.php` â†’ **sempre** responder JSON `{success, message, data?}`.
- **Assinaturas base64**:
  - Campos: `doador_assinatura`, `doador_assinatura_conjuge`, `administrador_assinatura`.
  - Aceitar **PNG base64** com/sem prefixo `"data:image/png;base64,"`; **persistir sem prefixo** e **validar tamanho**.
  - FunÃ§Ã£o utilitÃ¡ria para normalizar e checar base64 antes de salvar.
- **EndereÃ§o do doador**:
  - Montagem: concatene `doador_endereco_*` (logradouro, nÃºmero, bairro, cidade, UF) com vÃ­rgulas; CEP no final.
  - Remover hÃ­fens/virgulas sobrando; normalizar espaÃ§os.
- **Fallback RG/CPF**:
  - Se `doador_rg` vazio **e** `doador_rg_igual_cpf=1` â†’ use `doador_cpf` no campo RG **somente na exibiÃ§Ã£o/relatÃ³rio**, nÃ£o sobrescreva no BD.
- **RelatÃ³rio 14-1**:
  - Usar helper `r141_fillFieldById(id, valor)` para inputs/textareas por `id`.
  - Garantir IDs Ãºnicos nos templates de relatÃ³rio.

# Fluxos principais
- **Importar planilha**:
  - Upload CSV/Excel â†’ `app/functions/produto_parser.php`.
  - Inserir em `produtos` com **transaÃ§Ã£o**; estratÃ©gia de **upsert** definida por `chave_negocio` (defina e documente).
  - Validar cabeÃ§alhos esperados e tipos; coletar erros por linha.
- **Check de produto (AJAX)**:
  - Endpoint `CRUD/UPDATE/check-produto.php` com PDO + transaÃ§Ã£o (quando mÃºltiplos campos).
  - Campos possÃ­veis no payload: `checado`, `ativo`, `observacao`, `imprimir_etiqueta`, `editado`.
  - Resposta JSON padrÃ£o; HTTP codes: 200 ok, 400 validaÃ§Ã£o, 500 erro inesperado.
- **Gerar RelatÃ³rio 14-1**:
  - `CRUD/READ/relatorio-14-1.php` coleta; `app/views/planilhas/relatorio141_view.php` preenche.
  - Regras de formataÃ§Ã£o de endereÃ§o e fallback RG/CPF **antes** do preenchimento.
- **Assinar/Desfazer**:
  - BotÃµes alternam â€œAssinarâ€/â€œDesfazer assinaturaâ€ conforme presenÃ§a do base64 no BD.
  - Ao desfazer â†’ limpar campo correspondente e auditar (log mÃ­nimo: quem/quando).

# Regras de implementaÃ§Ã£o
- **PDO preparado** sempre, com `try/catch`, rollback em falha; erros mapeados em mensagens claras.
- **FunÃ§Ãµes utilitÃ¡rias**:
  - `normalize_base64_signature(string $raw): string` â†’ remove prefixo, valida MIME/size.
  - `format_doador_endereco(array $campos): string` â†’ monta endereÃ§o canÃ´nico.
  - `json_response(bool $ok, string $msg, ?array $data=null, int $http=200)` â†’ padroniza saÃ­da AJAX.
- **Views Bootstrap 5**:
  - NÃ£o inline CSS; classes utilitÃ¡rias do Bootstrap; acessibilidade bÃ¡sica (labels/aria).
- **FPDF/PHPSpreadsheet**:
  - Isolar em serviÃ§os (`app/services/pdf/`, `app/services/xlsx/`); nada de lÃ³gica pesada nas views.

# Definition of Done (para cada tarefa)
- CÃ³digo seguindo PSR-12; arquivos **com caminho completo**.
- LÃ³gica de negÃ³cio fora do Controller/View.
- Teste mÃ­nimo cobrindo caminho feliz + 1 falha (ex.: validaÃ§Ã£o).
- Comandos para rodar (composer, scripts, etc.).
- **Checklist** concluÃ­do e **rollback** possÃ­vel.
- Commit no padrÃ£o (Conventional Commits PT-BR).

# Checklist de qualidade (marque ao responder)
- [ ] Entrada validada (tipos, limites, required).
- [ ] SQL preparado; transaÃ§Ã£o quando multi-update.
- [ ] Logs Ãºteis (sem dados sensÃ­veis/base64 completo).
- [ ] Performance ok (sem N+1 visÃ­vel).
- [ ] Tempo/locale coerente; CEP/CPF/RG formatados quando exibidos.
- [ ] Teste mÃ­nimo incluso e instruÃ§Ãµes para rodar.


