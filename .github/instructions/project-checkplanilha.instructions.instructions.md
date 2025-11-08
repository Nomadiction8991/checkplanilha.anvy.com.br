---
description: "Regras do projeto checkplanilha.anvy.com.br"
applyTo: "app/**,CRUD/**,relatorios/**,public/**,composer.json"
---
# Arquitetura (resumo)
- **PHP** (Bootstrap 5 nas views em `app/views/` via `layouts/app-wrapper.php`).
- **CRUD/** dividido em CREATE/READ/UPDATE/DELETE.
- **MySQL**: tabelas `comuns` (igrejas), `dependencias` (departamentos), `planilhas` (spreadsheets), `produtos` (itens).
- **Relatórios**: HTML em `relatorios/`, preenchidos em `app/views/planilhas/relatorio-14-1.php` (DOM helper).
- **Bibliotecas**: FPDF (PDF), PHPSpreadsheet (Excel), Composer (vendor).

# Padrões de domínio
- **Estados do produto (tabela `produtos`)**:
  - `checado` (0/1), `ativo` (0/1), `imprimir_etiqueta` (0/1), `editado` (0/1), `observacao` (TEXT).
  - Atualizações via AJAX em `CRUD/UPDATE/check-produto.php` → **sempre** responder JSON `{success, message, data?}`.
- **Assinaturas base64**:
  - Campos: `doador_assinatura`, `doador_assinatura_conjuge`, `administrador_assinatura`.
  - Aceitar **PNG base64** com/sem prefixo `"data:image/png;base64,"`; **persistir sem prefixo** e **validar tamanho**.
  - Função utilitária para normalizar e checar base64 antes de salvar.
- **Endereço do doador**:
  - Montagem: concatene `doador_endereco_*` (logradouro, número, bairro, cidade, UF) com vírgulas; CEP no final.
  - Remover hífens/virgulas sobrando; normalizar espaços.
- **Fallback RG/CPF**:
  - Se `doador_rg` vazio **e** `doador_rg_igual_cpf=1` → use `doador_cpf` no campo RG **somente na exibição/relatório**, não sobrescreva no BD.
- **Relatório 14-1**:
  - Usar helper `r141_fillFieldById(id, valor)` para inputs/textareas por `id`.
  - Garantir IDs únicos nos templates de relatório.

# Fluxos principais
- **Importar planilha**:
  - Upload CSV/Excel → `app/functions/produto_parser.php`.
  - Inserir em `produtos` com **transação**; estratégia de **upsert** definida por `chave_negocio` (defina e documente).
  - Validar cabeçalhos esperados e tipos; coletar erros por linha.
- **Check de produto (AJAX)**:
  - Endpoint `CRUD/UPDATE/check-produto.php` com PDO + transação (quando múltiplos campos).
  - Campos possíveis no payload: `checado`, `ativo`, `observacao`, `imprimir_etiqueta`, `editado`.
  - Resposta JSON padrão; HTTP codes: 200 ok, 400 validação, 500 erro inesperado.
- **Gerar Relatório 14-1**:
  - `CRUD/READ/relatorio-14-1.php` coleta; `app/views/planilhas/relatorio-14-1.php` preenche.
  - Regras de formatação de endereço e fallback RG/CPF **antes** do preenchimento.
- **Assinar/Desfazer**:
  - Botões alternam “Assinar”/“Desfazer assinatura” conforme presença do base64 no BD.
  - Ao desfazer → limpar campo correspondente e auditar (log mínimo: quem/quando).

# Regras de implementação
- **PDO preparado** sempre, com `try/catch`, rollback em falha; erros mapeados em mensagens claras.
- **Funções utilitárias**:
  - `normalize_base64_signature(string $raw): string` → remove prefixo, valida MIME/size.
  - `format_doador_endereco(array $campos): string` → monta endereço canônico.
  - `json_response(bool $ok, string $msg, ?array $data=null, int $http=200)` → padroniza saída AJAX.
- **Views Bootstrap 5**:
  - Não inline CSS; classes utilitárias do Bootstrap; acessibilidade básica (labels/aria).
- **FPDF/PHPSpreadsheet**:
  - Isolar em serviços (`app/services/pdf/`, `app/services/xlsx/`); nada de lógica pesada nas views.

# Definition of Done (para cada tarefa)
- Código seguindo PSR-12; arquivos **com caminho completo**.
- Lógica de negócio fora do Controller/View.
- Teste mínimo cobrindo caminho feliz + 1 falha (ex.: validação).
- Comandos para rodar (composer, scripts, etc.).
- **Checklist** concluído e **rollback** possível.
- Commit no padrão (Conventional Commits PT-BR).

# Checklist de qualidade (marque ao responder)
- [ ] Entrada validada (tipos, limites, required).
- [ ] SQL preparado; transação quando multi-update.
- [ ] Logs úteis (sem dados sensíveis/base64 completo).
- [ ] Performance ok (sem N+1 visível).
- [ ] Tempo/locale coerente; CEP/CPF/RG formatados quando exibidos.
- [ ] Teste mínimo incluso e instruções para rodar.
