-- Adicionar coluna dr na tabela produtos
ALTER TABLE produtos ADD COLUMN dr TINYINT(1) NOT NULL DEFAULT 0 AFTER observacao;
