-- Adiciona campos de Nota Fiscal à tabela de cadastro de produtos
ALTER TABLE produtos_cadastro
  ADD COLUMN possui_nota TINYINT(1) NOT NULL DEFAULT 0 AFTER quantidade,
  ADD COLUMN numero_nota VARCHAR(50) NULL AFTER possui_nota,
  ADD COLUMN data_emissao DATE NULL AFTER numero_nota,
  ADD COLUMN valor_nota DECIMAL(10,2) NULL AFTER data_emissao,
  ADD COLUMN fornecedor_nota VARCHAR(255) NULL AFTER valor_nota;

-- Observações:
-- - Os campos de nota permanecem NULL quando possui_nota = 0
-- - Ajuste os nomes das tabelas/colunas se divergirem do seu esquema atual
