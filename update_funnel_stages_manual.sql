-- update_funnel_stages_manual.sql
-- Script para atualização manual das etapas do Funil Licitações

SET @funil_nome = 'Funil Licitações';

-- 0. Adicionar coluna 'cor' se não existir (O erro indicou que falta)
-- Execute esta linha separadamente se o seu banco não suportar 'IF NOT EXISTS' no alter dentro de bloco, 
-- mas normalmente o ALTER ignora ou falha. Vamos tentar adicionar.
ALTER TABLE etapas_funil ADD COLUMN cor VARCHAR(20) DEFAULT '#cccccc';

-- 1. Obter ID do Funil
SELECT @funil_id := id FROM funis WHERE nome = @funil_nome LIMIT 1;

-- Verifica se encontrou o funil (se rodar em cliente compativel pode parar, senão segue com null e falha sql)

-- 2. Atualizar Nomes das Etapas Existentes (Mapeamento para preservar IDs e Histórico)
-- CAPTAÇÃO -> Captação de Edital
UPDATE etapas_funil SET nome = 'Captação de Edital', ordem = 1, cor = '#3498db' 
WHERE funil_id = @funil_id AND nome LIKE '%CAPTAÇÃO%';

-- ANÁLISE TÉCNICA -> Em análise Técnica
UPDATE etapas_funil SET nome = 'Em análise Técnica', ordem = 3, cor = '#9b59b6' 
WHERE funil_id = @funil_id AND nome LIKE '%ANÁLISE TÉCNICA%';

-- HOMOLOGADO/ATA -> Homologado
UPDATE etapas_funil SET nome = 'Homologado', ordem = 5, cor = '#27ae60' 
WHERE funil_id = @funil_id AND nome LIKE '%HOMOLOGADO%';

-- EMPENHADO -> Empenhado
UPDATE etapas_funil SET nome = 'Empenhado', ordem = 6, cor = '#2c3e50' 
WHERE funil_id = @funil_id AND nome LIKE '%EMPENHADO%';

-- CONTRATO -> Contrato
UPDATE etapas_funil SET nome = 'Contrato', ordem = 7, cor = '#c0392b' 
WHERE funil_id = @funil_id AND nome LIKE '%CONTRATO%';

-- CARONA -> Ata/Carona
UPDATE etapas_funil SET nome = 'Ata/Carona', ordem = 8, cor = '#1abc9c' 
WHERE funil_id = @funil_id AND nome LIKE '%CARONA%';

-- EM ANÁLISE (Antigo) -> Acolhimento de propostas
UPDATE etapas_funil SET nome = 'Acolhimento de propostas', ordem = 2, cor = '#f1c40f' 
WHERE funil_id = @funil_id AND nome = 'EM ANÁLISE';

-- REMOVER DISPUTA (Se existir)
-- Primeiro movemos as oportunidades para 'Em análise Técnica' para não ficarem órfãs (Opcional, mas recomendado)
-- UPDATE oportunidades SET etapa_id = (SELECT id FROM etapas_funil WHERE funil_id = @funil_id AND nome = 'Em análise Técnica' LIMIT 1) 
-- WHERE etapa_id IN (SELECT id FROM etapas_funil WHERE funil_id = @funil_id AND nome LIKE '%DISPUTA%');

-- Deletar a etapa DISPUTA
DELETE FROM etapas_funil WHERE funil_id = @funil_id AND nome LIKE '%DISPUTA%';

-- 3. Inserir Novas Etapas (se não existirem após os updates)
-- Usa INSERT IGNORE ou verificação manual em procedures, mas em script solto:

INSERT INTO etapas_funil (funil_id, nome, ordem, cor)
SELECT @funil_id, 'Acolhimento de propostas', 2, '#f1c40f'
WHERE NOT EXISTS (SELECT 1 FROM etapas_funil WHERE funil_id = @funil_id AND nome = 'Acolhimento de propostas');

INSERT INTO etapas_funil (funil_id, nome, ordem, cor)
SELECT @funil_id, 'Desclassificado', 4, '#7f8c8d'
WHERE NOT EXISTS (SELECT 1 FROM etapas_funil WHERE funil_id = @funil_id AND nome = 'Desclassificado');

INSERT INTO etapas_funil (funil_id, nome, ordem, cor)
SELECT @funil_id, 'Revogado', 9, '#e74c3c'
WHERE NOT EXISTS (SELECT 1 FROM etapas_funil WHERE funil_id = @funil_id AND nome = 'Revogado');

INSERT INTO etapas_funil (funil_id, nome, ordem, cor)
SELECT @funil_id, 'Fracassado', 10, '#95a5a6'
WHERE NOT EXISTS (SELECT 1 FROM etapas_funil WHERE funil_id = @funil_id AND nome = 'Fracassado');

INSERT INTO etapas_funil (funil_id, nome, ordem, cor)
SELECT @funil_id, 'Anulado', 11, '#34495e'
WHERE NOT EXISTS (SELECT 1 FROM etapas_funil WHERE funil_id = @funil_id AND nome = 'Anulado');

INSERT INTO etapas_funil (funil_id, nome, ordem, cor)
SELECT @funil_id, 'Suspenso', 12, '#e67e22'
WHERE NOT EXISTS (SELECT 1 FROM etapas_funil WHERE funil_id = @funil_id AND nome = 'Suspenso');

-- 4. Garantir Ordem Correta Final (Update em massa para garantir)
UPDATE etapas_funil SET ordem = 1 WHERE funil_id = @funil_id AND nome = 'Captação de Edital';
UPDATE etapas_funil SET ordem = 2 WHERE funil_id = @funil_id AND nome = 'Acolhimento de propostas';
UPDATE etapas_funil SET ordem = 3 WHERE funil_id = @funil_id AND nome = 'Em análise Técnica';
UPDATE etapas_funil SET ordem = 4 WHERE funil_id = @funil_id AND nome = 'Desclassificado';
UPDATE etapas_funil SET ordem = 5 WHERE funil_id = @funil_id AND nome = 'Homologado';
UPDATE etapas_funil SET ordem = 6 WHERE funil_id = @funil_id AND nome = 'Empenhado';
UPDATE etapas_funil SET ordem = 7 WHERE funil_id = @funil_id AND nome = 'Contrato';
UPDATE etapas_funil SET ordem = 8 WHERE funil_id = @funil_id AND nome = 'Ata/Carona';
UPDATE etapas_funil SET ordem = 9 WHERE funil_id = @funil_id AND nome = 'Revogado';
UPDATE etapas_funil SET ordem = 10 WHERE funil_id = @funil_id AND nome = 'Fracassado';
UPDATE etapas_funil SET ordem = 11 WHERE funil_id = @funil_id AND nome = 'Anulado';
UPDATE etapas_funil SET ordem = 12 WHERE funil_id = @funil_id AND nome = 'Suspenso';

-- 5. Consulta para Verificar Resultado
SELECT * FROM etapas_funil WHERE funil_id = @funil_id ORDER BY ordem;
