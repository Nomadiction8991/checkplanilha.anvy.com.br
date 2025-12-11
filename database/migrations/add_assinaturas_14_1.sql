-- Migration: Sistema de Assinaturas para Relatório 14.1
-- Data: 2025-11-03
-- Descrição: Remove campos de nome e assinatura do responsável da planilha e cria tabela de assinaturas por produto

-- 1. Remover apenas colunas de nome e assinatura do responsável da tabela planilhas
-- (Mantém os campos administracao e cidade)
ALTER TABLE planilhas 
DROP COLUMN IF EXISTS nome_responsavel,
DROP COLUMN IF EXISTS assinatura_responsavel;

-- 2. Criar tabela de assinaturas para o relatório 14.1
CREATE TABLE IF NOT EXISTS assinaturas_14_1 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    id_planilha INT NOT NULL,
    
    -- Token único para compartilhamento
    token VARCHAR(64) UNIQUE NOT NULL,
    
    -- Dados do Administrador/Acessor
    nome_administrador VARCHAR(255),
    assinatura_administrador LONGTEXT,
    
    -- Dados do Doador
    nome_doador VARCHAR(255),
    endereco_doador TEXT,
    cpf_doador VARCHAR(14),
    rg_doador VARCHAR(20),
    assinatura_doador LONGTEXT,
    
    -- Dados do Cônjuge
    nome_conjuge VARCHAR(255),
    endereco_conjuge TEXT,
    cpf_conjuge VARCHAR(14),
    rg_conjuge VARCHAR(20),
    assinatura_conjuge LONGTEXT,
    
    -- Metadados
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('pendente', 'assinado', 'cancelado') DEFAULT 'pendente',
    ip_assinatura VARCHAR(45),
    
    -- Chaves estrangeiras
    FOREIGN KEY (id_produto) REFERENCES produtos_cadastro(id) ON DELETE CASCADE,
    FOREIGN KEY (id_planilha) REFERENCES planilhas(id) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_token (token),
    INDEX idx_produto (id_produto),
    INDEX idx_planilha (id_planilha),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Comentários nas colunas
ALTER TABLE assinaturas_14_1 
MODIFY COLUMN token VARCHAR(64) COMMENT 'Token único para compartilhamento público',
MODIFY COLUMN assinatura_administrador LONGTEXT COMMENT 'Base64 da assinatura do administrador',
MODIFY COLUMN assinatura_doador LONGTEXT COMMENT 'Base64 da assinatura do doador',
MODIFY COLUMN assinatura_conjuge LONGTEXT COMMENT 'Base64 da assinatura do cônjuge';
