# Scripts de Utilidade

## 📁 Estrutura

```
scripts/
├── reprocessar-produtos.php   # Reprocessa produtos com parser atualizado
└── README.md                   # Este arquivo
```

## 🔧 Scripts Disponíveis

### `reprocessar-produtos.php`

Reprocessa produtos existentes aplicando as melhorias do parser atualizado.

**Documentação completa:** Ver `../REPROCESSAMENTO-GUIA.md`

**Uso básico:**
```bash
# Simular (recomendado primeiro)
php scripts/reprocessar-produtos.php --dry-run

# Executar
php scripts/reprocessar-produtos.php
```

**Opções:**
- `--dry-run` - Simula sem salvar
- `--limit=N` - Limita quantidade de produtos
- `--planilha-id=N` - Processa apenas uma planilha
- `--verbose` - Mostra detalhes de todos os produtos

**Exemplo:**
```bash
php scripts/reprocessar-produtos.php --planilha-id=15 --limit=100 --dry-run --verbose
```

## ⚠️ Importante

1. **SEMPRE faça backup do banco antes de executar scripts de migração**
2. **Execute com `--dry-run` primeiro para revisar mudanças**
3. **Teste em uma planilha pequena antes de processar tudo**

## 📝 Como Adicionar Novo Script

1. Crie o arquivo PHP na pasta `scripts/`
2. Adicione documentação de uso no topo do arquivo
3. Implemente opções de linha de comando
4. Adicione modo `--dry-run` se for fazer alterações
5. Documente aqui no README

## 🔗 Links Úteis

- [Guia de Reprocessamento](../REPROCESSAMENTO-GUIA.md)
- [Melhorias Implementadas](../MELHORIAS-IMPLEMENTADAS.md)
- [Test Parser](../test-parser.php)
