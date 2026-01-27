ALTER TABLE propostas ADD COLUMN atualizado_por_id INT NULL;
ALTER TABLE propostas ADD CONSTRAINT fk_propostas_atualizado_por FOREIGN KEY (atualizado_por_id) REFERENCES usuarios(id) ON DELETE SET NULL;
