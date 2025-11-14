---
description: "Padrões PHP gerais"
applyTo: "**/*.php,**/composer.json,**/phpunit.xml,**/.php-cs-fixer.php"
---
# PHP – padrões
- `declare(strict_types=1);`, PSR-12, tipos em propriedades/retornos; `DateTimeImmutable`.
- Estruture em camadas: Controller → Service → Repository; nada de regra de negócio em Controller/View.
- Erros: exceções específicas; converta para respostas HTTP/JSON padronizadas quando AJAX.

# MySQL (PDO)
- Sempre usar `prepare`/`bindValue`; **nunca** concatenar valores.
- Transação para operações atômicas; `commit`/`rollback` explícitos.

# Testes
- Use `phpunit`; pelo menos 1 teste por serviço novo ou regra de negócio.
- Fakes/stubs para DB quando possível; testes de integração podem usar schema de teste.

# Ferramentas sugeridas
- Lint/format: PHP-CS-Fixer (PSR-12).
- Análise estática: PHPStan (nível 5+).
- Logs: Monolog (formato JSON opcional).
