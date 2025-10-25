# 🚀 Comandos Git para Commitar a Migração

## Status Atual
Você está na branch: **dev**

## O que foi alterado:

### Arquivos Novos Criados
- `app/views/layouts/app-wrapper.php`
- `app/views/planilhas/view-planilha.php`
- `app/views/planilhas/importar-planilha.php`
- `app/views/planilhas/editar-planilha.php`
- `app/views/produtos/editar-produto.php`
- `app/views/produtos/observacao-produto.php`
- `app/views/shared/menu.php`
- `public/assets/css/*.css` (13 arquivos movidos)
- `RESUMO-COMPLETO.md`
- `MIGRACAO-STATUS.md`

### Arquivos Modificados
- `index.php` (reescrito com Bootstrap)
- `VIEW/view-planilha.php` (redirect)
- `VIEW/importar-planilha.php` (redirect)
- `VIEW/editar-planilha.php` (redirect)
- `VIEW/editar-produto.php` (redirect)
- `VIEW/observacao-produto.php` (redirect)
- `VIEW/menu.php` (redirect)

### Arquivos Backup (NÃO commitar)
- `*.backup`

---

## Comandos para Commitar

```bash
# Ver o status dos arquivos
git status

# Adicionar novos arquivos e pastas
git add app/
git add public/assets/css/
git add RESUMO-COMPLETO.md
git add MIGRACAO-STATUS.md

# Adicionar arquivos modificados
git add index.php
git add VIEW/view-planilha.php
git add VIEW/importar-planilha.php
git add VIEW/editar-planilha.php
git add VIEW/editar-produto.php
git add VIEW/observacao-produto.php
git add VIEW/menu.php

# NÃO adicionar backups (opcional: adicionar ao .gitignore)
echo "*.backup" >> .gitignore
git add .gitignore

# Commitar com mensagem descritiva
git commit -m "feat: Migração Bootstrap 5 - 50% concluído

- Criada estrutura app/views/ (layouts, planilhas, produtos, shared)
- Migradas 7 páginas principais para Bootstrap 5.3
- Wrapper 400px centralizado em todas as páginas
- Todos os CSS movidos para public/assets/css/
- Redirects criados para compatibilidade
- Mantidas todas as funcionalidades (AJAX, mic, filtros)
- Layout mestre reutilizável (app-wrapper.php)

Páginas migradas:
- index.php (homepage)
- view-planilha.php (visualização com microfone)
- importar-planilha.php (formulário de importação)
- editar-planilha.php (formulário de edição)
- menu.php (menu com cards)
- editar-produto.php (edição de produto)
- observacao-produto.php (observações)

Próximos: create-produto, read-produto, relatórios"

# Push para o repositório remoto
git push origin dev
```

---

## Comando Alternativo (Adicionar Tudo de Uma Vez)

```bash
# Se quiser adicionar tudo (exceto backups)
git add .
git reset -- "*.backup"  # Remove backups se foram adicionados
git commit -m "feat: Migração Bootstrap 5 - Estrutura e páginas principais"
git push origin dev
```

---

## Verificar antes de commitar

```bash
# Ver o que será commitado
git diff --cached

# Ver lista de arquivos
git diff --cached --name-only

# Ver status resumido
git status -s
```

---

## Se quiser criar uma branch específica

```bash
# Criar branch para esta feature
git checkout -b feature/bootstrap-migration

# Fazer o commit
git add .
git reset -- "*.backup"
git commit -m "feat: Migração Bootstrap 5 - 50% concluído"

# Push da nova branch
git push origin feature/bootstrap-migration
```

---

## Mensagem de Commit Recomendada

```
feat: Migração Bootstrap 5 - Estrutura MVC e páginas principais

ESTRUTURA:
- Criada pasta app/views/ com estrutura MVC
- Layout mestre app-wrapper.php com Bootstrap 5.3
- Wrapper 400px centralizado em todas as telas
- Reorganizados 13 arquivos CSS para public/assets/

PÁGINAS MIGRADAS (7):
✅ index.php - Homepage com filtros e paginação
✅ view-planilha.php - Lista de produtos + microfone
✅ importar-planilha.php - Upload e config de CSV
✅ editar-planilha.php - Edição de planilha
✅ menu.php - Menu com cards Bootstrap
✅ editar-produto.php - Edição de produto
✅ observacao-produto.php - Gerenciamento de obs

FEATURES:
- Bootstrap 5.3 + Bootstrap Icons via CDN
- Material Icons para microfone
- Gradiente roxo/azul no header
- Responsivo com 400px max-width
- AJAX search mantido
- SpeechRecognition API mantida
- Filtros em accordion
- Badges coloridos por status

COMPATIBILIDADE:
- Redirects criados em VIEW/ para app/views/
- Todas as funcionalidades mantidas
- Zero breaking changes

PROGRESSO: 50% (7 de 14 páginas)
```

---

## Após o Commit

Para mesclar com a main (quando estiver pronto):

```bash
# Voltar para main
git checkout main

# Mesclar dev
git merge dev

# Push
git push origin main
```

---

## ⚠️ IMPORTANTE

**NÃO commite os arquivos `.backup`!**

Se já foram adicionados por engano:
```bash
git rm --cached "*.backup"
echo "*.backup" >> .gitignore
git add .gitignore
```
