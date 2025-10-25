-- Adicionar coluna 'codigo' na tabela produtos_cadastro
-- Execute este script no banco de dados

ALTER TABLE `produtos_cadastro` 
ADD COLUMN `codigo` VARCHAR(100) NULL DEFAULT NULL AFTER `id_planilha`;

-- A coluna codigo é opcional e será usada para armazenar códigos externos
-- que não fazem parte da descrição completa do produto
