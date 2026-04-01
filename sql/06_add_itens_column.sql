-- Adicionar coluna 'itens' (JSON) às tabelas de empenhos e notas_fiscais
-- Essa coluna é usada pelo finance_handler.php mas pode não existir na criação original

ALTER TABLE `empenhos` ADD COLUMN IF NOT EXISTS `itens` JSON NULL DEFAULT NULL AFTER `documento_tipo`;
ALTER TABLE `notas_fiscais` ADD COLUMN IF NOT EXISTS `itens` JSON NULL DEFAULT NULL AFTER `documento_tipo`;
