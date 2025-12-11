# Check Planilha - Guia RÃ¡pido

Projeto PHP focado em gestÃ£o de planilhas com importaÃ§Ã£o e conferÃªncia de produtos. Este guia resume como configurar o ambiente, garantir UTF-8 de ponta a ponta e aproveitar as melhorias adicionadas.

## Requisitos
- PHP 8.1+ com extensÃµes `pdo_mysql`, `mbstring` e `intl`
- Composer
- MySQL/MariaDB

## ConfiguraÃ§Ã£o
1. **Instale dependÃªncias**
   ```bash
   composer install
   ```
2. **ConfiguraÃ§Ã£o de ambiente**
   - Copie as credenciais para variÃ¡veis de ambiente (opcional, caso nÃ£o queira usar o default do `config/database.php`):
     - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - Ajuste o timezone no `bootstrap.php` se necessÃ¡rio (padrÃ£o `America/Sao_Paulo`).
3. **Servidor local**
   ```bash
   php -S localhost:8000 -t .
   ```
   Acesse `http://localhost:8000/login.php`.

## Notas de implementaÃ§Ã£o
- **UTF-8 garantido**: `bootstrap.php` forÃ§a cabeÃ§alhos, charset padrÃ£o e logging; `PDO` usa `utf8mb4` com `SET NAMES`.
- **SessÃµes seguras**: cookies `HttpOnly` e `SameSite=Lax`.
- **Mapeamento correto de IDs**: telas agora usam `planilha_id` (tabela `planilhas`) para ler/atualizar produtos, evitando confusÃ£o com `comum_id`.
- **AÃ§Ãµes assÃ­ncronas**: marcar check, etiqueta e DR agora funciona via AJAX com feedback visual imediato. As rotas em `app/controllers/update/*.php` retornam JSON quando a requisiÃ§Ã£o aceita `application/json`.
- **Logs**: erros sÃ£o gravados em `storage/logs/app.log` (criado automaticamente).

## Fluxo principal
1. Login em `login.php`.
2. Escolha uma comum em `index.php`.
3. Visualize a planilha (`app/views/planilhas/planilha_visualizar.php`) e interaja com produtos sem recarregar a pÃ¡gina.
4. Listagem detalhada em `app/views/produtos/produtos_listar.php`.

## Dicas de uso
- Mantenha os filtros nas telas de planilha/produtos; eles sÃ£o preservados nas aÃ§Ãµes via AJAX.
- Se alterar o schema, atualize os IDs de planilha/comum nas pÃ¡ginas relacionadas e nos scripts em `app/controllers/`.

## Suporte e prÃ³ximos passos
- Revisar textos estÃ¡ticos para corrigir eventuais resÃ­duos de encoding legado.
- Adicionar testes automatizados para os fluxos de importaÃ§Ã£o e atualizaÃ§Ãµes AJAX.

