# 📱 PWA - Instalação Dual (Produção + Desenvolvimento)

## 🎯 Como Funciona

Você pode instalar **DOIS aplicativos** separados:
- 🚀 **Produção** - Ambiente estável para uso real
- 🔧 **Desenvolvimento** - Ambiente de testes

Ambos podem coexistir no mesmo dispositivo!

---

## 📋 Arquivos Criados

### Manifests:
- `manifest-prod.json` → Aponta para `/prod/index.php`
- `manifest-dev.json` → Aponta para `/dev/index.php`

### Páginas de Instalação:
- `install-prod.html` → Página para instalar versão de produção (azul)
- `install-dev.html` → Página para instalar versão de desenvolvimento (amarelo)

---

## 🚀 Como Instalar

### 1️⃣ **Produção**
1. Acesse: `https://checkplanilha.anvy.com.br/install-prod.html`
2. Clique em **"Instalar Aplicativo"**
3. Confirme a instalação
4. Ícone **"CheckPlanilha Prod"** aparecerá na tela inicial

### 2️⃣ **Desenvolvimento**
1. Acesse: `https://checkplanilha.anvy.com.br/install-dev.html`
2. Clique em **"Instalar Aplicativo DEV"**
3. Confirme a instalação
4. Ícone **"CheckPlanilha Dev"** aparecerá na tela inicial

---

## 🎨 Diferenças Visuais

| Ambiente | Cor Tema | Ícone | Nome |
|----------|----------|-------|------|
| **Produção** | Azul (#0d6efd) | 📊 | CheckPlanilha Prod |
| **Desenvolvimento** | Amarelo (#ffc107) | 🔧 | CheckPlanilha Dev |

---

## ⚙️ Configuração no Servidor

### Estrutura de Pastas:
```
checkplanilha.anvy.com.br/
├── manifest-prod.json
├── manifest-dev.json
├── install-prod.html
├── install-dev.html
├── logo.png (192x192 e 512x512)
├── logo-dev.png (opcional - versão amarela)
├── prod/
│   └── index.php (produção)
└── dev/
    └── index.php (desenvolvimento)
```

### Configuração de index.php:

**Opção 1:** Copiar o código atual para ambas as pastas:
```bash
cp -r /caminho/atual/* prod/
cp -r /caminho/atual/* dev/
```

**Opção 2:** Usar o index.php raiz como prod e criar dev separado:
```bash
mkdir dev
cp index.php dev/
# Configurar dev/index.php com banco de dev
```

---

## 🔧 Diferenças Técnicas

### manifest-prod.json
```json
{
  "name": "Check Planilha - Produção",
  "start_url": "/prod/index.php",
  "scope": "/prod/",
  "theme_color": "#0d6efd"
}
```

### manifest-dev.json
```json
{
  "name": "Check Planilha - Desenvolvimento",
  "start_url": "/dev/index.php",
  "scope": "/dev/",
  "theme_color": "#ffc107"
}
```

**Importante:** O `scope` diferente permite instalar ambos simultaneamente!

---

## 📱 Testando no Celular

### Android (Chrome):
1. Acesse `install-prod.html`
2. Menu (⋮) → **"Instalar aplicativo"** ou **"Adicionar à tela inicial"**
3. Repita para `install-dev.html`
4. Dois ícones aparecerão na tela inicial

### iOS (Safari):
1. Acesse `install-prod.html`
2. Botão Compartilhar → **"Adicionar à Tela de Início"**
3. Repita para `install-dev.html`

---

## 🎯 Fluxo do Usuário

```
Primeira Visita:
└─ install-prod.html
   ├─ [Instalar] → Adiciona "CheckPlanilha Prod" à tela inicial
   ├─ [Acessar Sistema] → /prod/index.php
   └─ [Versão Desenvolvimento] → install-dev.html
      ├─ [Instalar] → Adiciona "CheckPlanilha Dev" à tela inicial
      └─ [Acessar Desenvolvimento] → /dev/index.php
```

---

## 🔒 Segurança

Para evitar acesso indevido ao ambiente DEV em produção:

### Adicionar no `/dev/.htaccess`:
```apache
# Bloquear acesso não autorizado
AuthType Basic
AuthName "Área de Desenvolvimento"
AuthUserFile /caminho/para/.htpasswd
Require valid-user
```

### Ou no PHP (`dev/index.php`):
```php
<?php
// Verificar IP ou autenticação
$ips_permitidos = ['127.0.0.1', '192.168.1.100'];
if (!in_array($_SERVER['REMOTE_ADDR'], $ips_permitidos)) {
    die('Acesso negado ao ambiente de desenvolvimento.');
}
?>
```

---

## 📊 Logs e Debugging

Para diferenciar qual ambiente está sendo usado:

### No PHP:
```php
<?php
$ambiente = (strpos($_SERVER['REQUEST_URI'], '/dev/') !== false) ? 'DEV' : 'PROD';
error_log("Acesso ao ambiente: $ambiente");
?>
```

### No JavaScript (console):
```javascript
console.log('Ambiente:', window.location.pathname.includes('/dev/') ? 'DEV' : 'PROD');
```

---

## 🎨 Personalizando Ícones

### Logo de Produção (logo.png):
- Fundo azul (#0d6efd)
- Ícone branco
- 512x512px

### Logo de Desenvolvimento (logo-dev.png):
- Fundo amarelo (#ffc107)
- Ícone escuro/preto
- Adicionar badge "DEV" no canto
- 512x512px

---

## ✅ Checklist de Deploy

- [ ] Criar pastas `prod/` e `dev/`
- [ ] Copiar código para ambas as pastas
- [ ] Configurar `conexao.php` com bancos diferentes
- [ ] Upload dos manifests (`manifest-prod.json`, `manifest-dev.json`)
- [ ] Upload das páginas de instalação (`install-prod.html`, `install-dev.html`)
- [ ] Upload das logos (`logo.png`, `logo-dev.png`)
- [ ] Testar instalação de ambos no celular
- [ ] Verificar se ambos aparecem como apps separados
- [ ] Configurar segurança no ambiente DEV

---

## 🐛 Troubleshooting

### Problema: "Botão Instalar não aparece"
**Solução:** 
- Verificar se está usando HTTPS
- Limpar cache do navegador
- Verificar console (F12) para erros no manifest

### Problema: "Só consigo instalar um"
**Solução:**
- Verificar se `scope` está diferente em cada manifest
- Limpar dados do site e tentar novamente

### Problema: "Ambos apontam para o mesmo lugar"
**Solução:**
- Verificar `start_url` nos manifests
- Confirmar estrutura de pastas `/prod/` e `/dev/`

---

## 📚 Referências

- [Web App Manifest - MDN](https://developer.mozilla.org/en-US/docs/Web/Manifest)
- [PWA Install - Google](https://web.dev/install-criteria/)
- [Service Workers - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
