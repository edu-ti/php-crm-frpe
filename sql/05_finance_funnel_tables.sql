CREATE TABLE IF NOT EXISTS `empenhos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `oportunidade_id` INT(11) NOT NULL,
  `numero` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  `data_emissao` DATE NULL DEFAULT NULL,
  `data_prevista` DATE NULL DEFAULT NULL,
  `documento_url` VARCHAR(512) NULL DEFAULT NULL,
  `documento_nome` VARCHAR(255) NULL DEFAULT NULL,
  `documento_tipo` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`oportunidade_id`) REFERENCES `oportunidades`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notas_fiscais` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empenho_id` INT(11) NULL DEFAULT NULL,
  `oportunidade_id` INT(11) NOT NULL,
  `numero` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  `data_faturamento` DATE NULL DEFAULT NULL,
  `data_prevista` DATE NULL DEFAULT NULL,
  `documento_url` VARCHAR(512) NULL DEFAULT NULL,
  `documento_nome` VARCHAR(255) NULL DEFAULT NULL,
  `documento_tipo` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`empenho_id`) REFERENCES `empenhos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`oportunidade_id`) REFERENCES `oportunidades`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
