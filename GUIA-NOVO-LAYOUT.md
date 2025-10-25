# 🚀 NOVO SISTEMA - GUIA DE USO

## 📱 Características

✅ **Visual de Celular (400px)** em todas as telas
✅ **Bootstrap 5** para design moderno
✅ **Componentes reutilizáveis**
✅ **Fácil manutenção**
✅ **Responsivo e performático**

## 🎨 Como Usar o Novo Layout

### 1. Estrutura Básica de uma Página

```php
<?php
// Configurações da página
$pageTitle = "Título da Página";
$backUrl = "index.php"; // URL do botão voltar (opcional)
$headerActions = '
    <a href="criar.php" class="btn-header-action" title="Adicionar">
        <i class="bi bi-plus-lg fs-5"></i>
    </a>
'; // Ações no header (opcional)

// Arquivo com o conteúdo da página
$contentFile = __DIR__ . '/meu-conteudo.php';

// Renderizar o layout
include __DIR__ . '/app/views/layouts/app-wrapper.php';
?>
```

### 2. Criar o Arquivo de Conteúdo

```php
<!-- meu-conteudo.php -->

<!-- Card de exemplo -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-file-text me-2"></i>
        Meu Card
    </div>
    <div class="card-body">
        <p>Conteúdo aqui...</p>
    </div>
</div>

<!-- Tabela de exemplo -->
<div class="card mt-3">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Coluna 1</th>
                    <th>Coluna 2</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Dado 1</td>
                    <td>Dado 2</td>
                    <td>
                        <button class="btn btn-sm btn-primary">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

## 📦 Componentes Bootstrap Disponíveis

### Botões
```html
<button class="btn btn-primary">Primário</button>
<button class="btn btn-secondary">Secundário</button>
<button class="btn btn-success">Sucesso</button>
<button class="btn btn-danger">Perigo</button>
<button class="btn btn-warning">Aviso</button>
<button class="btn btn-info">Info</button>

<!-- Tamanhos -->
<button class="btn btn-primary btn-sm">Pequeno</button>
<button class="btn btn-primary">Normal</button>
<button class="btn btn-primary btn-lg">Grande</button>

<!-- Largura total -->
<button class="btn btn-primary w-100">Largura Total</button>
```

### Cards
```html
<div class="card">
    <div class="card-header">
        Cabeçalho do Card
    </div>
    <div class="card-body">
        <h5 class="card-title">Título</h5>
        <p class="card-text">Texto do card.</p>
        <a href="#" class="btn btn-primary">Ação</a>
    </div>
    <div class="card-footer text-muted">
        Rodapé
    </div>
</div>
```

### Formulários
```html
<form>
    <div class="mb-3">
        <label for="campo1" class="form-label">
            <i class="bi bi-person me-1"></i>
            Nome
        </label>
        <input type="text" class="form-control" id="campo1" placeholder="Digite o nome">
    </div>
    
    <div class="mb-3">
        <label for="campo2" class="form-label">Email</label>
        <input type="email" class="form-control" id="campo2">
    </div>
    
    <div class="mb-3">
        <label for="campo3" class="form-label">Selecione</label>
        <select class="form-select" id="campo3">
            <option>Opção 1</option>
            <option>Opção 2</option>
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        Enviar
    </button>
</form>
```

### Badges
```html
<span class="badge bg-primary">Primário</span>
<span class="badge bg-secondary">Secundário</span>
<span class="badge bg-success">Sucesso</span>
<span class="badge bg-danger">Perigo</span>
<span class="badge bg-warning">Aviso</span>
<span class="badge bg-info">Info</span>
```

### Alertas
```html
<div class="alert alert-success" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    Operação realizada com sucesso!
</div>

<div class="alert alert-danger" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Erro ao realizar operação!
</div>

<div class="alert alert-warning" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    Atenção: Esta ação não pode ser desfeita!
</div>
```

### Tabelas
```html
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Status</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Item 1</td>
                <td><span class="badge bg-success">Ativo</span></td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### Paginação
```html
<nav>
    <ul class="pagination justify-content-center">
        <li class="page-item disabled">
            <a class="page-link" href="#">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item">
            <a class="page-link" href="#">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
```

### Spinners (Loading)
```html
<!-- Loading inline -->
<button class="btn btn-primary" disabled>
    <span class="spinner-border spinner-border-sm me-2"></span>
    Carregando...
</button>

<!-- Loading centralizado -->
<div class="text-center">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Carregando...</span>
    </div>
</div>
```

## 🎨 Ícones Bootstrap Icons

Todos os ícones disponíveis em: https://icons.getbootstrap.com

Exemplos:
```html
<i class="bi bi-house"></i> <!-- Casa -->
<i class="bi bi-search"></i> <!-- Busca -->
<i class="bi bi-plus-lg"></i> <!-- Mais -->
<i class="bi bi-pencil"></i> <!-- Editar -->
<i class="bi bi-trash"></i> <!-- Lixeira -->
<i class="bi bi-eye"></i> <!-- Ver -->
<i class="bi bi-check-lg"></i> <!-- Check -->
<i class="bi bi-x-lg"></i> <!-- X -->
<i class="bi bi-arrow-left"></i> <!-- Seta esquerda -->
<i class="bi bi-menu"></i> <!-- Menu -->
```

## 🔧 Classes Utilitárias Bootstrap

### Espaçamentos
```html
<!-- Margin -->
<div class="m-0">Margin 0</div>
<div class="m-1">Margin 1</div>
<div class="m-2">Margin 2</div>
<div class="m-3">Margin 3</div>
<div class="mt-3">Margin Top 3</div>
<div class="mb-3">Margin Bottom 3</div>
<div class="mx-3">Margin Horizontal 3</div>
<div class="my-3">Margin Vertical 3</div>

<!-- Padding -->
<div class="p-0">Padding 0</div>
<div class="p-1">Padding 1</div>
<div class="p-2">Padding 2</div>
<div class="p-3">Padding 3</div>
<div class="pt-3">Padding Top 3</div>
<div class="pb-3">Padding Bottom 3</div>
<div class="px-3">Padding Horizontal 3</div>
<div class="py-3">Padding Vertical 3</div>
```

### Texto
```html
<p class="text-start">Texto alinhado à esquerda</p>
<p class="text-center">Texto centralizado</p>
<p class="text-end">Texto alinhado à direita</p>

<p class="fw-bold">Texto negrito</p>
<p class="fw-normal">Texto normal</p>
<p class="fw-light">Texto leve</p>

<p class="fs-1">Tamanho 1 (maior)</p>
<p class="fs-6">Tamanho 6 (menor)</p>

<p class="text-primary">Texto azul</p>
<p class="text-success">Texto verde</p>
<p class="text-danger">Texto vermelho</p>
<p class="text-muted">Texto cinza claro</p>
```

### Flex e Grid
```html
<!-- Flex -->
<div class="d-flex justify-content-between align-items-center">
    <span>Item 1</span>
    <span>Item 2</span>
</div>

<div class="d-flex flex-column gap-2">
    <div>Item 1</div>
    <div>Item 2</div>
</div>

<!-- Grid -->
<div class="row g-2">
    <div class="col-6">Coluna 50%</div>
    <div class="col-6">Coluna 50%</div>
</div>

<div class="row g-3">
    <div class="col-4">Coluna 33%</div>
    <div class="col-4">Coluna 33%</div>
    <div class="col-4">Coluna 33%</div>
</div>
```

## 📱 Exemplo Completo de Página

Veja o arquivo `exemplo-novo-layout.php` para um exemplo funcional completo!

## 🎯 Vantagens do Novo Sistema

1. **Consistência Visual** - Todas as páginas seguem o mesmo padrão
2. **Fácil Manutenção** - Alterar o layout em um único lugar
3. **Componentes Prontos** - Bootstrap tem tudo pronto
4. **Responsivo** - Funciona em qualquer tamanho de tela
5. **Moderno** - Design atual e profissional
6. **Rápido** - Menos CSS customizado
7. **Acessível** - Bootstrap segue padrões de acessibilidade

## 🔄 Próximos Passos

1. Testar o exemplo: `exemplo-novo-layout.php`
2. Migrar página principal (index.php)
3. Migrar view-planilha.php
4. Migrar formulários
5. Migrar relatórios

**Quer que eu migre alguma página específica agora?** 🚀
