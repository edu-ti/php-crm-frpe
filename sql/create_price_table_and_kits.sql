-- ============================================================
-- Script: create_price_table_and_kits.sql  (v2 – Master-Detail)
-- Data: 2026-03-11
-- ============================================================

-- Cabeçalho da Tabela de Preço (ex: "AGAMENON PROCESSO 1723/2024")
CREATE TABLE IF NOT EXISTS `tabela_preco` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `codigo`      VARCHAR(50)  NOT NULL,
  `nome_tabela` VARCHAR(255) NOT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens de cada Tabela de Preço (linhas da grade)
CREATE TABLE IF NOT EXISTS `tabela_preco_itens` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `tabela_preco_id` INT           NOT NULL,
  `referencia`      VARCHAR(100)  DEFAULT NULL,
  `descricao`       VARCHAR(500)  NOT NULL,
  `valor_unitario`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `fabricante`      VARCHAR(255)  DEFAULT NULL,
  `observacoes`     TEXT          DEFAULT NULL,
  CONSTRAINT `fk_tpi_tabela` FOREIGN KEY (`tabela_preco_id`) REFERENCES `tabela_preco`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Kits (conjuntos)
CREATE TABLE IF NOT EXISTS `kits` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `codigo`      VARCHAR(50)  DEFAULT NULL,
  `nome`        VARCHAR(255) NOT NULL,
  `descricao`   TEXT         DEFAULT NULL,
  `valor_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens de cada Kit (aponta para linhas da tabela_preco_itens)
CREATE TABLE IF NOT EXISTS `kit_itens` (
  `id`                      INT AUTO_INCREMENT PRIMARY KEY,
  `kit_id`                  INT           NOT NULL,
  `tabela_preco_item_id`    INT           NOT NULL,
  `quantidade`              INT           NOT NULL DEFAULT 1,
  `valor_unitario_snapshot` DECIMAL(15,2) NOT NULL,
  CONSTRAINT `fk_ki_kit`  FOREIGN KEY (`kit_id`)               REFERENCES `kits`(`id`)               ON DELETE CASCADE,
  CONSTRAINT `fk_ki_item` FOREIGN KEY (`tabela_preco_item_id`) REFERENCES `tabela_preco_itens`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
