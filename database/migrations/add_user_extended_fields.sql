-- Migration: Adicionar campos estendidos à tabela usuarios
-- Data: 2025-11-07
-- Descrição: Adiciona assinatura, telefone, CPF, endereço completo e tipo de usuário

ALTER TABLE usuarios
ADD COLUMN assinatura TEXT DEFAULT NULL COMMENT 'Assinatura digital em base64',
ADD COLUMN telefone VARCHAR(20) DEFAULT NULL COMMENT 'Telefone com máscara (99) 99999-9999',
ADD COLUMN cpf VARCHAR(14) DEFAULT NULL COMMENT 'CPF com máscara 999.999.999-99',
ADD COLUMN endereco_cep VARCHAR(10) DEFAULT NULL COMMENT 'CEP com máscara 99999-999',
ADD COLUMN endereco_logradouro VARCHAR(255) DEFAULT NULL COMMENT 'Rua/Avenida',
ADD COLUMN endereco_numero VARCHAR(10) DEFAULT NULL COMMENT 'Número do endereço',
ADD COLUMN endereco_complemento VARCHAR(100) DEFAULT NULL COMMENT 'Complemento (apto, bloco, etc)',
ADD COLUMN endereco_bairro VARCHAR(100) DEFAULT NULL COMMENT 'Bairro',
ADD COLUMN endereco_cidade VARCHAR(100) DEFAULT NULL COMMENT 'Cidade',
ADD COLUMN endereco_estado VARCHAR(2) DEFAULT NULL COMMENT 'UF (sigla do estado)',
ADD COLUMN tipo VARCHAR(50) DEFAULT 'Administrador/Acessor' NOT NULL COMMENT 'Tipo de usuário: Administrador/Acessor, etc';
