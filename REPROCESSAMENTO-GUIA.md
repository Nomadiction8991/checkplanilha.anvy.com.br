# Guia de Reprocessamento de Produtos

Este guia explica como usar o script de reprocessamento para aplicar o parser atualizado em produtos já existentes no banco de dados.

## 📋 O que o script faz?

O script `scripts/reprocessar-produtos.php` reprocessa produtos existentes aplicando as melhorias do parser:

- ✅ Detecção inteligente de BEN (detecta repetições como "ESTANTE ESTANTE")
- ✅ Fuzzy matching para plural/singular (EQUIPAMENTO ↔ EQUIPAMENTOS)
- ✅ Extração precisa de complemento (preserva acentos e espaços)
- ✅ Remoção inteligente do tipo desc quando necessário

## 🚀 Como usar

### 1. Modo Dry-Run (Simulação - RECOMENDADO PRIMEIRO)

Execute primeiro em modo simulação para ver o que será alterado SEM salvar no banco:

```bash
php scripts/reprocessar-produtos.php --dry-run
```

Este modo mostra:
- Quais produtos serão alterados
- O que mudará em cada campo (BEN, complemento, descrição)
- Quantos produtos serão afetados

### 2. Modo Produção (Salva no Banco)

Após verificar o dry-run, execute sem a flag para aplicar as mudanças:

```bash
php scripts/reprocessar-produtos.php
```

⚠️ **ATENÇÃO**: Este modo ALTERA o banco de dados! Faça backup antes.

## 🎯 Opções Avançadas

### Processar apenas uma planilha específica

```bash
php scripts/reprocessar-produtos.php --planilha-id=15 --dry-run
```

### Limitar número de produtos processados

```bash
php scripts/reprocessar-produtos.php --limit=100 --dry-run
```

### Modo verbose (detalhes de todos os produtos)

```bash
php scripts/reprocessar-produtos.php --verbose --dry-run
```

### Combinar opções

```bash
php scripts/reprocessar-produtos.php --planilha-id=15 --limit=50 --verbose --dry-run
```

## 📊 Exemplo de Saída

```
=== REPROCESSAMENTO DE PRODUTOS ===
Modo: DRY-RUN (simulação)
Limite: 100 produtos

✓ Carregados 68 tipos de bens
✓ Aliases construídos

Produtos a processar: 100
================================================================================

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Produto ID: 1234
Tipo: [58] ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL

BEN:
  Antes: 'ESTANTES MUSICAIS E DE PARTITURAS'
  Depois: 'QUADRO MUSICAL'

COMPLEMENTO:
  Antes: 'PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA'
  Depois: 'LOUSA BRANCA'

DESCRIÇÃO:
  Antes: 1x [58 - ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL] ESTANTES MUSICAIS E DE PARTITURAS - PARTITURAS / QUADRO MUSICAL QUADRO MUSICAL LOUSA BRANCA (SALA DE MÚSICA)
  Depois: 1x [58 - ESTANTES MUSICAIS E DE PARTITURAS / QUADRO MUSICAL] QUADRO MUSICAL - LOUSA BRANCA (SALA DE MÚSICA)

⊘ Não salvo (modo dry-run)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

================================================================================
=== RELATÓRIO FINAL ===
================================================================================
Total processados: 100
Alterados: 23
Sem mudança: 77
Erros: 0

⚠ MODO DRY-RUN - Nenhuma alteração foi salva no banco!
Execute sem --dry-run para aplicar as mudanças.
```

## 🔧 Solução de Problemas

### Erro: "Table 'produtos' doesn't exist"

Verifique a conexão com o banco em `config/database.php`.

### Erro: "Call to undefined function pp_extrair_ben_complemento"

Certifique-se de que `app/functions/produto_parser.php` está no lugar correto.

### Script muito lento

Use `--limit=N` para processar em lotes menores:

```bash
php scripts/reprocessar-produtos.php --limit=1000
```

## ⚠️ Importante

1. **SEMPRE faça backup do banco antes de executar em modo produção**
2. **Execute primeiro com --dry-run para revisar as mudanças**
3. **Teste em uma planilha pequena primeiro** (use --planilha-id)
4. O script preserva os valores originais nos campos `editado_*` para auditoria

## 📝 Campos Alterados

O script atualiza os seguintes campos na tabela `produtos`:

- `ben` - Nome do bem extraído
- `complemento` - Descrição complementar
- `descricao` - Descrição final formatada
- `editado_tipo_ben_id` - Backup do tipo original
- `editado_ben` - Backup do BEN original
- `editado_complemento` - Backup do complemento original
- `editado_dependencia_id` - Backup da dependência original

## 🎯 Casos de Uso

### Corrigir produtos de uma importação específica

```bash
# 1. Ver o que será alterado
php scripts/reprocessar-produtos.php --planilha-id=15 --dry-run

# 2. Aplicar as correções
php scripts/reprocessar-produtos.php --planilha-id=15
```

### Reprocessar todo o banco de dados

```bash
# 1. Fazer backup do banco
mysqldump -u usuario -p banco > backup_antes_reprocessamento.sql

# 2. Testar com amostra
php scripts/reprocessar-produtos.php --limit=10 --dry-run

# 3. Executar em lotes (se banco grande)
php scripts/reprocessar-produtos.php --limit=1000

# 4. Verificar resultados e continuar se OK
php scripts/reprocessar-produtos.php
```

## 📞 Suporte

Em caso de problemas, verifique:
1. Logs de erro do PHP
2. Saída do modo `--verbose --dry-run`
3. Relatório final de estatísticas
