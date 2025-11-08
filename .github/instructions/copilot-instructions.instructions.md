---
applyTo: "**"
---
# Forma obrigatória de responder (este projeto)
1) **Resumo (≤3 linhas)** do que entendeu e do resultado esperado.
2) **3–5 alternativas** com prós/contras e riscos.
3) **Escolha 1** e **justifique em 1 linha**.
4) **Plano em 5–7 passos**.
5) **Entregáveis SEM PLACEHOLDERS**:
   - Arquivos **com caminho completo** e bloco único por arquivo.
   - SQLs/rotas/DTOs com nomes reais.
   - Comandos para rodar/lintar/testar.
   - Testes mínimos (como rodar).
   - **Checklist de qualidade** marcado.
   - **Rollback** simples.
   - **Commit sugerido** (Conventional Commits PT-BR).
6) Se faltar algo, **assuma com segurança** e **siga** (não bloqueie com perguntas).

## Regras gerais
- PHP ≥ 8.1, `declare(strict_types=1);`, PSR-12, Composer autoload, namespaces claros.
- Segurança: validação de entrada, SQL **parametrizado** (PDO `:param`), segredos fora do repo.
- Tempo/locale: UTC internamente; converter na borda. Logs úteis sem vazar dados sensíveis.
- Performance: evite N+1, I/O bloqueante desnecessária, reaproveite conexões/consultas.

## Quando responder com ARQUIVOS COMPLETOS
- Sempre que criar/alterar arquivo do projeto. Mostrar o arquivo inteiro, não só diffs.
- Se o arquivo for grande demais, entregue a seção afetada + instruções de patch reproduzíveis.

## Estilo da explicação
- Seja preciso e curto; explique só decisões não triviais; nada de prosa desnecessária.
