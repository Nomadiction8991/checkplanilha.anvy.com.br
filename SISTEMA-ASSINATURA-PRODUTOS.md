# Sistema de Assinatura de Produtos - ImplementaÃ§Ã£o

## VisÃ£o Geral
Sistema que permite administradores e doadores assinarem produtos diretamente, sem formulÃ¡rios. As assinaturas sÃ£o armazenadas nos campos `administrador_acessor_id` e `doador_conjugue_id` da tabela `produtos`.

## Estrutura do Banco de Dados

### Tabela: `produtos`
```sql
- administrador_acessor_id INT NOT NULL  -- ID do administrador que assinou
- doador_conjugue_id INT NOT NULL       -- ID do doador que assinou
```

### Tabela: `usuarios`
ContÃ©m todos os dados necessÃ¡rios:
- nome, cpf, rg, assinatura (base64)
- dados do cÃ´njuge: nome_conjuge, cpf_conjuge, rg_conjuge, assinatura_conjuge
- casado (boolean)

## Arquivos Criados/Modificados

### 1. app/controllers/update/ProdutosAssinarController.php
**FunÃ§Ã£o:** Backend para assinar/desassinar produtos
**Recursos:**
- Identifica tipo de usuÃ¡rio logado (Admin ou Doador)
- Atualiza campo correspondente na tabela produtos
- Suporta mÃºltiplos produtos em batch
- AÃ§Ãµes: `assinar` (define ID) ou `desassinar` (limpa para 0)

### 2. app/views/produtos/produtos_assinar.php
**FunÃ§Ã£o:** Interface de seleÃ§Ã£o de produtos
**Recursos:**
- Lista produtos da planilha
- Checkbox para seleÃ§Ã£o mÃºltipla
- Indica produtos jÃ¡ assinados pelo usuÃ¡rio
- BotÃµes: Assinar / Remover Assinatura
- Visual diferenciado para produtos assinados
- BotÃµes "Selecionar Todos" / "Nenhum"

### 3. app/controllers/read/Relatorio141DataController.php (atualizado)
**ModificaÃ§Ãµes:**
- Query agora faz JOIN com tabela `usuarios`
- Busca dados do administrador via `administrador_acessor_id`
- Busca dados do doador via `doador_conjugue_id`
- Retorna: nomes, CPF, RG, assinaturas (incluindo cÃ´njuge)

## Fluxo de Uso

### Para Administrador/Acessor:
1. Acessa planilha
2. Clica em "Assinar Produtos"
3. Seleciona produtos desejados
4. Clica em "Assinar Selecionados"
5. Sistema atualiza `administrador_acessor_id` com seu ID

### Para Doador/CÃ´njuge:
1. Mesmo fluxo do administrador
2. Sistema atualiza `doador_conjugue_id` com seu ID

### Desassinar:
1. Seleciona produtos jÃ¡ assinados por vocÃª
2. Clica em "Remover Assinatura"
3. Sistema limpa o campo (seta 0)

## IntegraÃ§Ã£o com RelatÃ³rio 14.1

O relatÃ³rio agora busca automaticamente:
- **Administrador:** nome, CPF, RG, assinatura
- **Doador:** nome, CPF, RG, assinatura
- **CÃ´njuge do doador** (se casado): nome, CPF, RG, assinatura

Todos os dados vÃªm da tabela `usuarios` via JOIN, eliminando necessidade de formulÃ¡rios separados.

## Vantagens da Nova Abordagem

1. **Dados Centralizados:** Tudo na tabela usuarios
2. **Sem DuplicaÃ§Ã£o:** NÃ£o precisa preencher dados mÃºltiplas vezes
3. **Rastreabilidade:** Sabe exatamente quem assinou cada produto
4. **Flexibilidade:** Pode assinar/desassinar a qualquer momento
5. **SeguranÃ§a:** Sistema identifica automaticamente o usuÃ¡rio logado

## PrÃ³ximos Passos

### Adicionar link no menu da planilha:
```php
<a href="./produtos/produtos_assinar.php?id=<?php echo $id_planilha; ?>" class="btn btn-primary">
    <i class="bi bi-pen"></i> Assinar Produtos
</a>
```

### Exibir assinaturas no relatÃ³rio 14.1:
O relatÃ³rio jÃ¡ recebe os dados, basta usar no template:
```php
<?php echo htmlspecialchars($produto['administrador_nome']); ?>
<img src="<?php echo $produto['administrador_assinatura']; ?>" alt="Assinatura">
```

## SeguranÃ§a

- âœ… AutenticaÃ§Ã£o obrigatÃ³ria
- âœ… Identifica tipo de usuÃ¡rio via sessÃ£o
- âœ… Permite desassinar apenas prÃ³prias assinaturas
- âœ… ValidaÃ§Ã£o de dados no backend
- âœ… TransaÃ§Ãµes SQL para integridade

## Status: âœ… Implementado e Pronto para Uso


