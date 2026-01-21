CREATE TABLE IF NOT EXISTS vendas_objetivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fornecedor_id INT NOT NULL,
    ano INT NOT NULL,
    mes INT NOT NULL,
    valor_meta DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_meta (usuario_id, fornecedor_id, ano, mes),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
