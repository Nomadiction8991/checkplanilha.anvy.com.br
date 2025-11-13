-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: anvy.com.br    Database: anvycomb_checkplanilha
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `comums`
--

DROP TABLE IF EXISTS `comums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comums` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` int NOT NULL,
  `cnpj` varchar(255) NOT NULL,
  `descricao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `administracao` varchar(255) NOT NULL,
  `cidade` varchar(255) NOT NULL,
  `setor` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dependencias`
--

DROP TABLE IF EXISTS `dependencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dependencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` int DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `planilhas`
--

DROP TABLE IF EXISTS `planilhas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planilhas` (
  `comum_id` int NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `posicao_cnpj` varchar(255) NOT NULL DEFAULT 'U5',
  `posicao_comum` varchar(255) NOT NULL DEFAULT 'D16',
  `posicao_data` varchar(255) NOT NULL DEFAULT 'D13',
  `pulo_linhas` varchar(255) NOT NULL DEFAULT '25',
  `mapeamento_colunas` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'codigo=A;complemento=D;dependencia=P',
  `data_posicao` date DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produtos` (
  `planilha_id` int NOT NULL,
  `id_produto` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) DEFAULT NULL,
  `descricao_completa` varchar(255) NOT NULL,
  `editado_descricao_completa` varchar(255) NOT NULL,
  `tipo_bem_id` int NOT NULL,
  `editado_tipo_bem_id` int NOT NULL,
  `bem` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `editado_bem` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `complemento` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `editado_complemento` varchar(255) NOT NULL,
  `dependencia_id` int NOT NULL,
  `editado_dependencia_id` int NOT NULL,
  `novo` int NOT NULL,
  `checado` int NOT NULL,
  `editado` int NOT NULL,
  `imprimir_etiqueta` int NOT NULL,
  `imprimir_14_1` int NOT NULL,
  `condicao_14_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `observacao` varchar(255) NOT NULL,
  `nota_numero` int DEFAULT NULL,
  `nota_data` date DEFAULT NULL,
  `nota_valor` varchar(255) DEFAULT NULL,
  `nota_fornecedor` varchar(255) DEFAULT NULL,
  `administrador_acessor_id` int DEFAULT NULL,
  `doador_conjugue_id` int DEFAULT NULL,
  `ativo` int NOT NULL,
  PRIMARY KEY (`id_produto`)
) ENGINE=InnoDB AUTO_INCREMENT=271 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipos_bens`
--

DROP TABLE IF EXISTS `tipos_bens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_bens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` int NOT NULL,
  `descricao` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `assinatura` text COLLATE utf8mb4_unicode_ci,
  `casado` tinyint(1) NOT NULL DEFAULT '0',
  `nome_conjuge` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf_conjuge` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg_conjuge` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg_conjuge_igual_cpf` tinyint(1) NOT NULL DEFAULT '0',
  `telefone_conjuge` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assinatura_conjuge` text COLLATE utf8mb4_unicode_ci,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rg_igual_cpf` tinyint(1) NOT NULL DEFAULT '0',
  `endereco_cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_logradouro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_numero` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Administrador/Acessor',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_usuarios_rg` (`rg`),
  KEY `idx_usuarios_cpf_conjuge` (`cpf_conjuge`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-13 21:44:28
