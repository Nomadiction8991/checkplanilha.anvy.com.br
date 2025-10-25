# 🔄 PLANO DE REFATORAÇÃO COMPLETO

## 📌 Objetivos
1. ✅ Visual "celular" em todas as telas (400px centralizado)
2. ✅ Bootstrap 5 para padronização
3. ✅ Estrutura de pastas organizada
4. ✅ Layout base reutilizável
5. ✅ Código limpo e manutenível

## 📂 Nova Estrutura de Pastas

```
checkplanilha.anvy.com.br/
├── public/                          # 🌐 Arquivos públicos acessíveis
│   ├── index.php                    # Homepage
│   ├── css/
│   │   ├── bootstrap.min.css        # Bootstrap 5
│   │   └── custom.css               # Estilos personalizados
│   ├── js/
│   │   ├── bootstrap.bundle.min.js  # Bootstrap JS
│   │   └── app.js                   # Scripts personalizados
│   └── img/
│       └── logo.png
│
├── app/                             # 🎯 Lógica da aplicação
│   ├── config/
│   │   └── database.php             # Configuração do banco
│   │
│   ├── controllers/                 # 🎮 Controladores
│   │   ├── PlanilhaController.php
│   │   ├── ProdutoController.php
│   │   └── RelatorioController.php
│   │
│   ├── models/                      # 📊 Modelos de dados
│   │   ├── Planilha.php
│   │   ├── Produto.php
│   │   └── ProdutoCheck.php
│   │
│   ├── views/                       # 👁️ Templates HTML
│   │   ├── layouts/
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   └── app-wrapper.php      # Wrapper de 400px
│   │   │
│   │   ├── planilhas/
│   │   │   ├── index.php
│   │   │   ├── view.php
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   │
│   │   ├── produtos/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   │
│   │   └── relatorios/
│   │       ├── relatorio-14-1.php
│   │       └── alteracoes.php
│   │
│   └── helpers/                     # 🛠️ Funções auxiliares
│       ├── functions.php
│       └── validation.php
│
├── vendor/                          # 📦 Dependências Composer
├── composer.json
└── .htaccess                        # Configuração Apache

```

## 🎨 Tecnologias

### Frontend
- **Bootstrap 5.3** - Framework CSS
- **Bootstrap Icons** - Ícones
- **JavaScript Vanilla** - Interatividade

### Backend
- **PHP 8.x** - Linguagem
- **PDO** - Banco de dados
- **PhpSpreadsheet** - Manipulação de planilhas

## 🔧 Componentes Principais

### 1. Layout Base com Wrapper 400px
```php
<!-- app/views/layouts/app-wrapper.php -->
<div class="app-container">
    <div class="mobile-wrapper">
        <!-- Todo conteúdo aqui -->
    </div>
</div>

<style>
.app-container {
    display: flex;
    justify-content: center;
    min-height: 100vh;
    background: #f5f5f5;
}
.mobile-wrapper {
    width: 100%;
    max-width: 400px;
    background: white;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}
</style>
```

### 2. Header Padronizado
- Logo/Título
- Botão voltar (quando necessário)
- Ações/Menu

### 3. Cards Bootstrap
- Filtros
- Listagens
- Formulários

## 📝 Padrões de Código

### Nomenclatura
- **Classes PHP**: PascalCase (PlanilhaController)
- **Métodos**: camelCase (listarPlanilhas)
- **Arquivos**: kebab-case (view-planilha.php)
- **CSS**: kebab-case (.btn-primary)

### Estrutura MVC
```
Requisição → Controller → Model → Database
                ↓
              View (Template)
```

## 🚀 Migração Gradual

### Fase 1: Preparação
- [x] Criar nova estrutura de pastas
- [ ] Instalar Bootstrap via CDN ou local
- [ ] Criar layout base (app-wrapper)

### Fase 2: Migração Core
- [ ] Migrar configuração de banco
- [ ] Criar Models base
- [ ] Criar Controllers base
- [ ] Migrar página principal (index)

### Fase 3: Migração Views
- [ ] Migrar view-planilha
- [ ] Migrar formulários
- [ ] Migrar relatórios

### Fase 4: Ajustes Finais
- [ ] Testar todas as funcionalidades
- [ ] Ajustar responsividade
- [ ] Otimizar performance

## 💡 Melhorias Adicionais

1. **Validação de Formulários**
   - Client-side (JS)
   - Server-side (PHP)

2. **Mensagens de Feedback**
   - Toast Bootstrap
   - Alerts contextuais

3. **Loading States**
   - Spinners Bootstrap
   - Skeleton screens

4. **Acessibilidade**
   - ARIA labels
   - Navegação por teclado

## 🔒 Segurança

- [ ] Sanitização de inputs
- [ ] Prepared statements (já existe)
- [ ] CSRF tokens
- [ ] Validação server-side

## 📱 Responsividade

Embora o foco seja 400px, garantir que funcione em:
- Mobile: < 400px (scroll horizontal mínimo)
- Tablet: 400px fixo centralizado
- Desktop: 400px fixo centralizado

## 🎯 Resultado Esperado

✅ Visual de celular em todas as telas
✅ Design moderno e profissional
✅ Código organizado e manutenível
✅ Facilidade para adicionar novos recursos
✅ Performance otimizada
