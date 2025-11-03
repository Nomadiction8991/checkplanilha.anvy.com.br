-- Adicionar coluna 'setor' na tabela 'planilhas'
-- Campo opcional, numérico (INT)
-- Executar este SQL no banco de dados

ALTER TABLE planilhas 
ADD COLUMN setor INT NULL DEFAULT NULL 
COMMENT 'Número do setor (opcional)';
