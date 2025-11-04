-- Adiciona a coluna para armazenar a condição escolhida do Relatório 14.1 no cadastro de produtos
-- 1: mais de 5 anos com documento fiscal anexo
-- 2: mais de 5 anos com documento fiscal extraviado
-- 3: até 5 anos com documento fiscal anexo

ALTER TABLE produtos_cadastro
  ADD COLUMN condicao_141 TINYINT NULL COMMENT '1: >5 anos com nota; 2: >5 anos sem nota (extraviado); 3: <=5 anos com nota' AFTER imprimir_14_1;