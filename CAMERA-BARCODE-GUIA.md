# 📷 Guia: Scanner de Código de Barras

## 📋 Visão Geral

O sistema possui um **scanner de código de barras integrado** que usa a câmera do dispositivo para ler códigos automaticamente e preencher o campo de busca.

## ✅ Funcionalidades

- ✅ **Leitura automática** de códigos de barras via câmera
- ✅ **Preenchimento automático** do campo "Código do Produto"
- ✅ **Envio automático** do formulário após detectar o código
- ✅ **Suporte a múltiplos formatos** de código de barras

## 📱 Requisitos Técnicos

### 1. HTTPS Obrigatório
- ⚠️ **O scanner só funciona em conexões seguras (HTTPS)**
- Em desenvolvimento local, use: `http://localhost` ou `http://127.0.0.1` (navegadores permitem câmera nesses casos)
- Em produção, **sempre use HTTPS**

### 2. Permissão de Câmera
- O navegador solicitará permissão para acessar a câmera na primeira vez
- Você precisa **autorizar o acesso**
- Se negar, o scanner não funcionará (mostrará alerta de erro)

### 3. Navegadores Compatíveis
- ✅ Chrome/Edge (Android/Desktop)
- ✅ Safari (iOS/MacOS)
- ✅ Firefox (Android/Desktop)
- ⚠️ Alguns navegadores antigos podem não suportar

## 📦 Formatos de Código de Barras Suportados

O scanner utiliza **Quagga2** e suporta os seguintes formatos:

| Formato | Descrição | Uso Comum |
|---------|-----------|-----------|
| **EAN-13** | European Article Number (13 dígitos) | Produtos de varejo internacional |
| **EAN-8** | European Article Number (8 dígitos) | Produtos pequenos |
| **UPC-A** | Universal Product Code (12 dígitos) | Produtos nos EUA/Canadá |
| **UPC-E** | UPC compacto (6 dígitos) | Embalagens pequenas |
| **CODE-128** | Alfanumérico de alta densidade | Logística, etiquetas industriais |
| **CODE-39** | Alfanumérico básico | Inventário, identificação |

## 🚀 Como Usar

### Passo a Passo

1. **Abra a página da planilha** (`app/views/planilhas/view-planilha.php`)
2. **Localize o campo "Código do Produto"**
3. **Clique no botão de câmera** 📹 (ao lado do botão de microfone)
4. **Autorize o acesso à câmera** quando solicitado
5. **Aponte a câmera para o código de barras**
6. **Aguarde a detecção automática** (leva 1-3 segundos)
7. ✅ **O código é preenchido e a busca é enviada automaticamente**

### Dicas para Melhor Leitura

- 💡 **Boa iluminação**: evite sombras sobre o código
- 💡 **Estabilize a câmera**: mantenha firme por 2-3 segundos
- 💡 **Distância adequada**: nem muito perto, nem muito longe (15-30cm ideal)
- 💡 **Foco no código**: centralize o código de barras na tela
- 💡 **Códigos limpos**: códigos borrados ou danificados podem falhar

## 🔧 Implementação Técnica

### Biblioteca Utilizada
- **Quagga2** (v1.x): [https://github.com/ericblade/quagga2](https://github.com/ericblade/quagga2)
- CDN: `https://unpkg.com/@ericblade/quagga2/dist/quagga.min.js`

### Arquivo com a Implementação
- `app/views/planilhas/view-planilha.php` (linhas 560-640 aprox.)

### Fluxo de Funcionamento

```javascript
// 1. Usuário clica no botão de câmera
btnCam.click()
  ↓
// 2. Modal Bootstrap é aberto
bsModal.show()
  ↓
// 3. Quagga2 inicia o stream de vídeo
Quagga.init({ facingMode: 'environment' })
  ↓
// 4. Scanner detecta o código de barras
Quagga.onDetected(result)
  ↓
// 5. Modal fecha e código é extraído
bsModal.hide()
code = result.codeResult.code
  ↓
// 6. Campo é preenchido automaticamente
codigoInput.value = code
  ↓
// 7. Formulário é enviado
form.submit()
```

## ❌ Solução de Problemas

### "Não foi possível acessar a câmera"

**Causas comuns:**
- ❌ Navegador não tem permissão de câmera
- ❌ Site não está em HTTPS (exceto localhost)
- ❌ Câmera já em uso por outro aplicativo
- ❌ Navegador muito antigo

**Soluções:**
1. Verifique as configurações de privacidade do navegador
2. Certifique-se de que o site está em HTTPS
3. Feche outros apps que usam a câmera
4. Atualize o navegador para a versão mais recente

### Scanner não detecta o código

**Causas comuns:**
- ❌ Iluminação ruim
- ❌ Código de barras danificado ou borrado
- ❌ Câmera tremendo muito
- ❌ Formato de código não suportado (ex: QR Code)

**Soluções:**
1. Melhore a iluminação do ambiente
2. Use um código de barras nítido
3. Estabilize a câmera por 2-3 segundos
4. Verifique se o formato está na lista suportada (acima)

### Modal não abre

**Causas comuns:**
- ❌ Bootstrap JS não carregado
- ❌ Erro de JavaScript anterior

**Soluções:**
1. Abra o console do navegador (F12) e verifique erros
2. Confirme que o Bootstrap está sendo carregado
3. Recarregue a página (Ctrl+F5)

## 🔒 Segurança e Privacidade

- ✅ **Nenhuma imagem é enviada para servidor**: tudo acontece no navegador
- ✅ **Acesso à câmera é temporário**: desliga ao fechar o modal
- ✅ **Permissão controlada pelo usuário**: pode ser revogada a qualquer momento
- ✅ **Sem gravação de vídeo**: apenas frame-by-frame para detecção

## 📞 Suporte

Se encontrar problemas:
1. Verifique esta documentação
2. Teste em outro navegador
3. Confirme que está usando HTTPS
4. Verifique as permissões de câmera

---

**Última atualização:** 25 de outubro de 2025  
**Versão do sistema:** 2.0 (migração Bootstrap completa)
