-- Remove a coluna possui_nota da tabela produtos_cadastro
-- Essa coluna foi substituída pela lógica de condicao_141
-- Agora os campos de nota só aparecem quando condicao_141 = 3

ALTER TABLE produtos_cadastro
  DROP COLUMN possui_nota;
