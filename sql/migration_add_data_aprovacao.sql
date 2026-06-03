-- Migração: Adiciona coluna data_aprovacao na tabela propostas
-- Data: 2026-06-03
-- Descrição: Permite rastrear QUANDO uma proposta foi aprovada (não apenas quando foi criada),
--             corrigindo os relatórios "Aprovado no Mês" e "Performance por Vendedor" no Dashboard BI.

ALTER TABLE `propostas`
    ADD COLUMN `data_aprovacao` timestamp NULL DEFAULT NULL AFTER `frete_valor`;

-- Atualiza propostas já aprovadas com a data de criação como fallback
UPDATE `propostas` SET `data_aprovacao` = `data_criacao` WHERE `status` = 'Aprovada';
