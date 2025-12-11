
# Regras para Agentes Automáticos (commits e push)

Objetivo: garantir que toda alteração automatizada feita por um agente neste repositório seja registrada com um commit claro, detalhado e enviado ao repositório remoto (branch `dev`).

Regras obrigatórias ao finalizar uma alteração:

- **Usar a branch `dev`**: para este projeto o agente deve usar sempre a branch `dev` para commits e push. Não criar branches adicionais automaticamente.
- **Incluir apenas mudanças relacionadas**: evite agrupar mudanças não relacionadas no mesmo commit.
- **Executar checagens mínimas**: se existirem scripts de teste/linters, execute-os. Caso não haja, execute `php -l` nos arquivos `.php` modificados para checar sintaxe.

- **Mensagem de commit detalhada (obrigatória)**:
  - Primeira linha (resumo): tipo + escopo curto + ação (ex.: `fix(parser): normaliza acentos em pp_normaliza`) — objetivo: claro e curto (≈72 chars).
  - Linha em branco.
  - Corpo: 2–6 linhas explicando *por que* a mudança foi feita e *o que* foi alterado. Liste arquivos principais modificados.
  - Se aplicável, adicione uma seção `Testes:` descrevendo brevemente os comandos executados e o resultado.
  - Se a alteração corrige um problema rastreável, inclua `Ref:` seguido do número da issue/bug/PR quando disponível.

  Exemplo de mensagem:

  ```
  fix(produto-parser): evitar perda de acentos na normalização

  O parser usava iconv sem fallback e perdia caracteres acentuados em alguns ambientes.
  Atualizei `app/services/produto_parser_service.php` para tratar falha de transliteração
  e adicionei testes manuais com entradas acentuadas.

  Testes: `php -l` em arquivos alterados; importação manual simulada em `scripts/test-parser.php`.
  Ref: #42
  ```

- **Conteúdo do commit**: prefira commits atômicos (um propósito por commit). Se necessário, crie múltiplos commits na mesma branch (`dev`) antes do push.

- **Checklist antes do push** (obrigatório):
  1. Revisar arquivos modificados (`git status`, `git diff --staged`).
  2. Confirmar mensagem de commit conforme modelo.
  3. Executar checagens mínimas (tests/linters/sintaxe).

- **Push**: após commit(s), execute `git push origin dev`.

- **Descrição do push/PR**: este agente fará push direto para `dev`. Se seu fluxo exigir PRs, crie um PR manualmente a partir de `dev` ou siga a política de revisão do repositório. Ao abrir um PR, copie a mensagem de commit na descrição e adicione passos para reproduzir, decisões tomadas e riscos conhecidos.

Comportamento esperado do agente (resumo operacional):

1. Atualizar `dev` localmente (ex.: `git checkout dev && git pull origin dev`) -> 2. Realizar alterações -> 3. Rodar checagens locais -> 4. Fazer commit(s) com mensagens detalhadas -> 5. `git push origin dev` -> 6. (Opcional) Abrir PR para revisão se necessário.

Notas:
- Essas regras são obrigatórias para mudanças automatizadas por agentes; humanos podem adaptar o fluxo mas devem seguir o mesmo padrão de clareza e detalhamento.
- Se o agente não tiver permissão para push direto para `dev` (política do repositório), gerar um patch (ex.: `git format-patch`) e notificar o responsável, ou abrir um PR manualmente a partir de `dev`.

---

Arquivo mantido em `.github/AGENT.md` — ajuste via PR se desejar mudanças na política.
