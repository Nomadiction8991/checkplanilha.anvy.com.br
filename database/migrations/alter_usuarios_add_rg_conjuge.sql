-- Migration: Add RG and spouse (conjuge) fields to usuarios table
-- Run: mysql -u user -p database < alter_usuarios_add_rg_conjuge.sql

ALTER TABLE usuarios
    ADD COLUMN rg VARCHAR(20) NULL AFTER cpf,
    ADD COLUMN rg_igual_cpf TINYINT(1) NOT NULL DEFAULT 0 AFTER rg,
    ADD COLUMN casado TINYINT(1) NOT NULL DEFAULT 0 AFTER assinatura,
    ADD COLUMN nome_conjuge VARCHAR(150) NULL AFTER casado,
    ADD COLUMN cpf_conjuge VARCHAR(14) NULL AFTER nome_conjuge,
    ADD COLUMN rg_conjuge VARCHAR(20) NULL AFTER cpf_conjuge,
    ADD COLUMN telefone_conjuge VARCHAR(20) NULL AFTER rg_conjuge,
    ADD COLUMN assinatura_conjuge TEXT NULL AFTER telefone_conjuge;

-- Indexes (optional for search / reporting)
CREATE INDEX idx_usuarios_rg ON usuarios (rg);
CREATE INDEX idx_usuarios_cpf_conjuge ON usuarios (cpf_conjuge);