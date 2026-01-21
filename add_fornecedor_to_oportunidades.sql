ALTER TABLE oportunidades ADD COLUMN fornecedor_id INT(11) DEFAULT NULL AFTER organizacao_id;
ALTER TABLE oportunidades ADD CONSTRAINT fk_oportunidades_fornecedor FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id);
