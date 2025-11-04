-- SQL para remover a coluna 'token' da tabela assinaturas_14_1
-- Esta coluna era usada para compartilhamento público de links de assinatura
-- Execute este comando no seu banco de dados MySQL/MariaDB

ALTER TABLE assinaturas_14_1 DROP COLUMN token;
