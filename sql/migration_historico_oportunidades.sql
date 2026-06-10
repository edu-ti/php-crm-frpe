CREATE TABLE IF NOT EXISTS `oportunidade_historico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oportunidade_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL DEFAULT 'nota',
  `descricao` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
