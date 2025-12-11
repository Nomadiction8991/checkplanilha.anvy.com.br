# Sistema de Assinatura de Produtos - Implementação

## Visão Geral
Sistema que permite administradores e doadores assinarem produtos diretamente, sem formulários. As assinaturas são armazenadas nos campos `administrador_acessor_id` e `doador_conjugue_id` da tabela `produtos`.

## Estrutura do Banco de Dados

### Tabela: `produtos`
```sql
- administrador_acessor_id INT NOT NULL  -- ID do administrador que assinou
- doador_conjugue_id INT NOT NULL       -- ID do doador que assinou
```

### Tabela: `usuarios`
Contém todos os dados necessários:
- nome, cpf, rg, assinatura (base64)
- dados do cônjuge: nome_conjuge, cpf_conjuge, rg_conjuge, assinatura_conjuge
- casado (boolean)

## Arquivos Criados/Modificados

### 1. CRUD/UPDATE/assinar-produtos.php
**Função:** Backend para assinar/desassinar produtos
**Recursos:**
- Identifica tipo de usuário logado (Admin ou Doador)
- Atualiza campo correspondente na tabela produtos
- Suporta múltiplos produtos em batch
- Ações: `assinar` (define ID) ou `desassinar` (limpa para 0)

### 2. app/views/produtos/assinar-produtos.php
**Função:** Interface de seleção de produtos
**Recursos:**
- Lista produtos da planilha
- Checkbox para seleção múltipla
- Indica produtos já assinados pelo usuário
- Botões: Assinar / Remover Assinatura
- Visual diferenciado para produtos assinados
- Botões "Selecionar Todos" / "Nenhum"

### 3. CRUD/READ/relatorio-14-1.php (atualizado)
**Modificações:**
- Query agora faz JOIN com tabela `usuarios`
- Busca dados do administrador via `administrador_acessor_id`
- Busca dados do doador via `doador_conjugue_id`
- Retorna: nomes, CPF, RG, assinaturas (incluindo cônjuge)

## Fluxo de Uso

### Para Administrador/Acessor:
1. Acessa planilha
2. Clica em "Assinar Produtos"
3. Seleciona produtos desejados
4. Clica em "Assinar Selecionados"
5. Sistema atualiza `administrador_acessor_id` com seu ID

### Para Doador/Cônjuge:
1. Mesmo fluxo do administrador
2. Sistema atualiza `doador_conjugue_id` com seu ID

### Desassinar:
1. Seleciona produtos já assinados por você
2. Clica em "Remover Assinatura"
3. Sistema limpa o campo (seta 0)

## Integração com Relatório 14.1

O relatório agora busca automaticamente:
- **Administrador:** nome, CPF, RG, assinatura
- **Doador:** nome, CPF, RG, assinatura
- **Cônjuge do doador** (se casado): nome, CPF, RG, assinatura

Todos os dados vêm da tabela `usuarios` via JOIN, eliminando necessidade de formulários separados.

## Vantagens da Nova Abordagem

1. **Dados Centralizados:** Tudo na tabela usuarios
2. **Sem Duplicação:** Não precisa preencher dados múltiplas vezes
3. **Rastreabilidade:** Sabe exatamente quem assinou cada produto
4. **Flexibilidade:** Pode assinar/desassinar a qualquer momento
5. **Segurança:** Sistema identifica automaticamente o usuário logado

## Próximos Passos

### Adicionar link no menu da planilha:
```php
<a href="./produtos/assinar-produtos.php?id=<?php echo $id_planilha; ?>" class="btn btn-primary">
    <i class="bi bi-pen"></i> Assinar Produtos
</a>
```

### Exibir assinaturas no relatório 14.1:
O relatório já recebe os dados, basta usar no template:
```php
<?php echo htmlspecialchars($produto['administrador_nome']); ?>
<img src="<?php echo $produto['administrador_assinatura']; ?>" alt="Assinatura">
```

## Segurança

- ✅ Autenticação obrigatória
- ✅ Identifica tipo de usuário via sessão
- ✅ Permite desassinar apenas próprias assinaturas
- ✅ Validação de dados no backend
- ✅ Transações SQL para integridade

## Status: ✅ Implementado e Pronto para Uso
