# Check Planilha - Guia Rápido

Projeto PHP focado em gestão de planilhas com importação e conferência de produtos. Este guia resume como configurar o ambiente, garantir UTF-8 de ponta a ponta e aproveitar as melhorias adicionadas.

## Requisitos
- PHP 8.1+ com extensões `pdo_mysql`, `mbstring` e `intl`
- Composer
- MySQL/MariaDB

## Configuração
1. **Instale dependências**
   ```bash
   composer install
   ```
2. **Configuração de ambiente**
   - Copie as credenciais para variáveis de ambiente (opcional, caso não queira usar o default do `CRUD/conexao.php`):
     - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - Ajuste o timezone no `bootstrap.php` se necessário (padrão `America/Sao_Paulo`).
3. **Servidor local**
   ```bash
   php -S localhost:8000 -t .
   ```
   Acesse `http://localhost:8000/login.php`.

## Notas de implementação
- **UTF-8 garantido**: `bootstrap.php` força cabeçalhos, charset padrão e logging; `PDO` usa `utf8mb4` com `SET NAMES`.
- **Sessões seguras**: cookies `HttpOnly` e `SameSite=Lax`.
- **Mapeamento correto de IDs**: telas agora usam `planilha_id` (tabela `planilhas`) para ler/atualizar produtos, evitando confusão com `comum_id`.
- **Ações assíncronas**: marcar check, etiqueta e DR agora funciona via AJAX com feedback visual imediato. As rotas em `CRUD/UPDATE/*.php` retornam JSON quando a requisição aceita `application/json`.
- **Logs**: erros são gravados em `storage/logs/app.log` (criado automaticamente).

## Fluxo principal
1. Login em `login.php`.
2. Escolha uma comum em `index.php`.
3. Visualize a planilha (`app/views/planilhas/view-planilha.php`) e interaja com produtos sem recarregar a página.
4. Listagem detalhada em `app/views/produtos/read-produto.php`.

## Dicas de uso
- Mantenha os filtros nas telas de planilha/produtos; eles são preservados nas ações via AJAX.
- Se alterar o schema, atualize os IDs de planilha/comum nas páginas relacionadas e nos scripts em `CRUD/`.

## Suporte e próximos passos
- Revisar textos estáticos para corrigir eventuais resíduos de encoding legado.
- Adicionar testes automatizados para os fluxos de importação e atualizações AJAX.

