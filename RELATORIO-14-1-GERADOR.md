# Gerador de Relatório 14.1

Sistema para gerar Relatórios 14.1 automaticamente a partir de templates reutilizáveis.

## 📋 Estrutura Criada

```
checkplanilha.anvy.com.br/
├── app/views/planilhas/
│   └── relatorio-14-1-template.php    # Template HTML reutilizável
├── CRUD/READ/
│   ├── Relatorio141Generator.php      # Classe geradora
│   └── gerar-relatorio-14-1.php       # Página de geração
└── public/assets/css/
    └── relatorio-14-1.css             # Estilos (já existente)
```

## 🚀 Como Usar

### 1. Gerar Relatório Preenchido Automaticamente

```
http://seu-dominio/CRUD/READ/gerar-relatorio-14-1.php?id_planilha=123
```

- Busca dados da planilha ID 123
- Preenche CNPJ, Nº Relatório, Casa de Oração automaticamente
- Cria uma página A4 para cada produto da planilha
- Preenche código, descrição e observações dos produtos

### 2. Gerar Template em Branco (1 página)

```
http://seu-dominio/CRUD/READ/gerar-relatorio-14-1.php?em_branco=1
```

### 3. Gerar Múltiplas Páginas em Branco

```
http://seu-dominio/CRUD/READ/gerar-relatorio-14-1.php?em_branco=5
```

Cria 5 páginas A4 em branco prontas para preencher manualmente.

## 🔧 Funcionalidades

### Template Reutilizável (`relatorio-14-1-template.php`)

- **Estrutura limpa**: HTML puro com marcadores `data-field`
- **Loop automático**: Gera uma página A4 por produto
- **Campos preenchíveis**: Todos os inputs têm `data-field` para identificação
- **Responsivo**: Mantém proporções A4 perfeitas na tela e impressão
- **Print-ready**: CSS de impressão embutido

### Classe Geradora (`Relatorio141Generator.php`)

```php
$gerador = new Relatorio141Generator($pdo);

// Gerar relatório de uma planilha
$dados = $gerador->gerarRelatorio(123);

// Gerar template em branco
$dados = $gerador->gerarEmBranco(5);

// Renderizar HTML
$html = $gerador->renderizar(123);
```

**Métodos:**
- `gerarRelatorio($id_planilha)` - Busca e formata dados
- `buscarPlanilha($id)` - Query da planilha
- `buscarProdutos($id)` - Query dos produtos
- `renderizar($id)` - Retorna HTML pronto
- `gerarEmBranco($num)` - Template vazio

## 📊 Dados Preenchidos Automaticamente

### Da Planilha:
- ✅ CNPJ
- ✅ Número do Relatório
- ✅ Casa de Oração

### De Cada Produto:
- ✅ Código
- ✅ Descrição
- ✅ Observações
- ⚪ Marca (se tiver no BD)
- ⚪ Modelo (se tiver no BD)
- ⚪ Número de Série (se tiver no BD)
- ⚪ Ano Fabricação (se tiver no BD)

### Campos para Preencher Manualmente:
- Tipo / Regional / Comum
- Checkboxes (Conforme / Não Conforme / Baixa)
- Responsáveis (Nome, Função, Data) - 5 linhas
- Observações da Comissão
- Membros da Comissão - 2 linhas

## 🎨 Marcadores de Campo

Todos os campos têm atributo `data-field` para fácil integração:

```html
<input data-field="cnpj" value="...">
<input data-field="codigo" value="...">
<textarea data-field="observacoes">...</textarea>
<input type="checkbox" data-field="check_conforme">
```

## 🔄 Próximas Melhorias Possíveis

### 1. Exportar para PDF (mPDF)

```bash
composer require mpdf/mpdf
```

```php
$gerador = new Relatorio141Generator($pdo);
$html = $gerador->renderizar(123);

$mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output('relatorio-14-1.pdf', 'D'); // Download
```

### 2. Salvar Preenchimento no Banco

Criar tabela `relatorios_preenchidos`:
- id_planilha
- id_produto
- dados_json (campos preenchidos manualmente)
- data_preenchimento

### 3. API REST para Preenchimento

```javascript
// Enviar dados preenchidos
fetch('/api/relatorio-14-1/salvar', {
    method: 'POST',
    body: JSON.stringify({
        id_planilha: 123,
        id_produto: 456,
        campos: {
            tipo: 'CO',
            marca: 'Samsung',
            check_conforme: true,
            resp1_nome: 'João Silva'
        }
    })
});
```

### 4. Integrar no Menu Principal

Adicionar link no `menu.php`:

```php
<a href="/CRUD/READ/gerar-relatorio-14-1.php?id_planilha=<?= $id ?>">
    <i class="bi bi-file-earmark-text"></i> Gerar Relatório 14.1
</a>
```

## 📝 Exemplo de Uso Programático

```php
<?php
require_once 'CRUD/conexao.php';
require_once 'CRUD/READ/Relatorio141Generator.php';

$gerador = new Relatorio141Generator($pdo);

// Cenário 1: Usuário clica em "Gerar Relatório" na planilha
$id_planilha = $_GET['id'];
$dados = $gerador->gerarRelatorio($id_planilha);
extract($dados);
include 'app/views/planilhas/relatorio-14-1-template.php';

// Cenário 2: Criar formulário em branco
$dados = $gerador->gerarEmBranco(10); // 10 páginas
extract($dados);
include 'app/views/planilhas/relatorio-14-1-template.php';

// Cenário 3: Baixar como PDF
$html = $gerador->renderizar($id_planilha);
$mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output("relatorio-{$id_planilha}.pdf", 'D');
?>
```

## 🎯 Vantagens desta Estrutura

1. **Reutilizável**: Template separado, classe independente
2. **Flexível**: Gera preenchido ou em branco
3. **Escalável**: Fácil adicionar PDF, salvar dados, API
4. **Manutenível**: Lógica separada da apresentação
5. **Print-ready**: CSS A4 perfeito já integrado
6. **Multi-página**: Loop automático por produto

## 📞 Rotas Disponíveis

| Rota | Descrição |
|------|-----------|
| `?id_planilha=123` | Relatório preenchido da planilha 123 |
| `?em_branco=1` | 1 página em branco |
| `?em_branco=10` | 10 páginas em branco |
| *(padrão)* | 1 página em branco |

---

**Status**: ✅ Estrutura completa e funcional  
**Próximo passo**: Testar gerando um relatório real!
