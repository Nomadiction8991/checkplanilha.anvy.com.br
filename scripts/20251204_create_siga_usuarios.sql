-- Criação da tabela siga_usuarios (integração SIGA - preferências)
CREATE TABLE IF NOT EXISTS `siga_usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `siga_login` VARCHAR(150) NOT NULL,
  `nome` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `ddd` VARCHAR(10) NULL,
  `telefone` VARCHAR(50) NULL,
  `operadora` VARCHAR(100) NULL,
  `idioma` VARCHAR(100) NULL,
  `registros_por_pagina` VARCHAR(50) NULL,
  `tema` VARCHAR(50) NULL,
  `tempo_limite_sessao` VARCHAR(50) NULL,
  `preferencia_hash` VARCHAR(64) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_siga_login` (`siga_login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
