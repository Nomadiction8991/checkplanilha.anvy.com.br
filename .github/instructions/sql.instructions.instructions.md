---
description: "SQL seguro e performático"
applyTo: "**/*.sql"
---
# SQL
- Evitar comandos sem WHERE restritivo; sempre indicar chave/índice.
- Preferir `EXPLAIN` para checar plano quando otimizar consultas pesadas.
- Scripts de correção em massa: idempotentes e com `BEGIN; ... COMMIT;`/`ROLLBACK;`.
