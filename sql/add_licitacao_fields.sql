-- add_licitacao_fields.sql
-- Adiciona colunas para Licitações na tabela oportunidades

ALTER TABLE oportunidades ADD COLUMN local_disputa VARCHAR(255) DEFAULT NULL;
ALTER TABLE oportunidades ADD COLUMN uasg VARCHAR(50) DEFAULT NULL;
ALTER TABLE oportunidades ADD COLUMN data_abertura DATE DEFAULT NULL;
ALTER TABLE oportunidades ADD COLUMN hora_disputa TIME DEFAULT NULL;
ALTER TABLE oportunidades ADD COLUMN modalidade VARCHAR(100) DEFAULT NULL;
ALTER TABLE oportunidades ADD COLUMN objeto TEXT DEFAULT NULL;

-- Verifica se as colunas foram criadas
DESCRIBE oportunidades;
