# Execução de Prompts: Refinar e Pedir Confirmação

Objetivo: o agente deve melhorar/redigir melhor o prompt do usuário e mostrar a versão refinada. O agente só executa mudanças no repositório se o usuário responder explicitamente `SIM` à pergunta de confirmação.

Regras obrigatórias e fluxo:

1. Receber o prompt original.
2. Gerar uma versão refinada do prompt (melhor redação, clarificação de escopo e passos concretos). Apresentar essa versão ao usuário.
3. Perguntar ao usuário: "Deseja prosseguir com a execução dessas ações? Responda `SIM` para executar." (exigir exatamente `SIM`, em maiúsculas, para proceder).
   - Se o usuário NÃO responder `SIM` exatamente: o agente deve abortar qualquer execução e não alterar o repositório.
   - Se o usuário responder `SIM`: o agente pode prosseguir com a execução conforme as regras abaixo.

Regras de execução (aplicáveis somente após confirmação `SIM`):

- O agente pode editar arquivos, executar checagens, commitar e push para a branch `dev`.
- Antes do commit/push, executar checagens mínimas: `php -l` nos arquivos modificados; rodar quaisquer scripts de teste/linters existentes.
- Gerar mensagem de commit detalhada seguindo o padrão em `.github/AGENT.md`.

Comportamento de commit/push (após `SIM`):

- Commits devem ir para a branch `dev` (`git push origin dev`).
- A mensagem de commit deve conter resumo, corpo explicativo, seção `Testes:` e `Ref:` quando aplicável.
- Após push bem-sucedido, confirmar sucesso internamente e preparar o resumo final ao usuário.

Relato final ao usuário (após execução):

- Enviar ao usuário um relatório conciso contendo apenas:
  - Resultado final (sucesso/erro)
  - Resumo curto das mudanças (arquivos principais modificados)
  - Mensagem de commit e SHA do commit

Exemplo de interação esperada:

Usuário envia: "Corrigir normalização do parser de produtos para não perder acentos"

Agente responde (exemplo):
"Versão refinada do prompt:
 - Objetivo: Ajustar `pp_normaliza()` para não perder acentos em ambientes sem transliteração.
 - Passos: 1) Adicionar fallback em `pp_normaliza()`; 2) Rodar `php -l`; 3) Testar importação com `scripts/test-parser.php`.
Deseja prosseguir com a execução dessas ações? Responda `SIM` para executar."

Se o usuário responder `SIM`, o agente executa; caso contrário, aborta.

Notas de segurança:

- O agente exige confirmação explícita `SIM` para executar mudanças; sem essa confirmação, nenhuma alteração é feita.
- O agente não deve expor dados sensíveis em mensagens de commit.
- Se o repositório proibir push direto para `dev`, o agente deve gerar um patch (`git format-patch`) e notificar o responsável.

Arquivo mantido em `.github/AUTOMATED_PROMPT_EXECUTION.md`. Ajustes requerem PR/edição manual.
# Execução Automática de Prompts (refinar e executar)

Objetivo: definir regras claras para quando um agente receber um prompt do usuário, refiná-lo automaticamente e executar as tarefas solicitadas sem exibir todos os passos ao usuário (modo "silencioso").
# Execução Automática de Prompts (refinar e executar)

AVISO DE SEGURANÇA (obrigatório):
Antes de executar qualquer mudança que altere o repositório (commit/push), o prompt deve incluir explicitamente o token de autorização `AUTORIZAR_EXECUCAO: true` e uma breve justificativa. Sem esse token o agente deve apenas responder com a versão refinada do prompt e aguardar confirmação.

Fluxo padrão do agente ao receber um prompt do usuário:

1. Receber o prompt original.
2. Gerar uma versão refinada do prompt (melhor redação, clarificação de escopo e passos concretos). Apresentar essa versão ao usuário.
3. Perguntar ao usuário: "Deseja prosseguir com a execução dessas ações? Responda `SIM` para executar." (exigir exatamente `SIM`, em maiúsculas, para proceder).
   - Se o usuário NÃO responder `SIM` exatamente: o agente deve abortar qualquer execução e não alterar o repositório.
   - Se o usuário responder `SIM`: o agente pode prosseguir com a execução conforme as regras abaixo.

Regras de execução (aplicáveis somente após confirmação `SIM`):


- O agente pode editar arquivos, executar checagens, commitar e push para a branch `dev`.
- Antes do commit/push, executar checagens mínimas: `php -l` nos arquivos modificados; rodar quaisquer scripts de teste/linters existentes.
- Gerar mensagem de commit detalhada seguindo o padrão em `.github/AGENT.md`.

Comportamento de commit/push (após `SIM`):

- Commits devem ir para a branch `dev` (`git push origin dev`).
- A mensagem de commit deve conter resumo, corpo explicativo, seção `Testes:` e `Ref:` quando aplicável.

-- Após push bem-sucedido, confirmar sucesso internamente e preparar o resumo final ao usuário.

Relato final ao usuário (após execução):


- Enviar ao usuário um relatório conciso contendo apenas:
  - Resultado final (sucesso/erro)
  - Resumo curto das mudanças (arquivos principais modificados)
  - Mensagem de commit e SHA do commit

Exemplo de interação esperada:

Usuário envia: "Corrigir normalização do parser de produtos para não perder acentos"

Agente responde (exemplo):
"Versão refinada do prompt:
 - Objetivo: Ajustar `pp_normaliza()` para não perder acentos em ambientes sem transliteração.
 - Passos: 1) Adicionar fallback em `pp_normaliza()`; 2) Rodar `php -l`; 3) Testar importação com `scripts/test-parser.php`.
Deseja prosseguir com a execução dessas ações? Responda `SIM` para executar."

Se o usuário responder `SIM`, o agente executa; caso contrário, aborta.

Notas de segurança:

- O agente exige confirmação explícita `SIM` para executar mudanças; sem essa confirmação, nenhuma alteração é feita.
- O agente não deve expor dados sensíveis em mensagens de commit ou no log público.
-- Se o repositório proibir push direto para `dev`, o agente deve gerar um patch (`git format-patch`) e notificar o responsável (sem gravar logs de auditoria automaticamente).

Arquivo mantido em `.github/AUTOMATED_PROMPT_EXECUTION.md`. Ajustes requerem PR/edição manual.

Arquivo mantido em `.github/AUTOMATED_PROMPT_EXECUTION.md`. Ajustes requerem PR/edição manual.
