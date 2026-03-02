-- SCRIPT PARA ADICIONAR NOVAS ETAPAS (Controle de Entrega e Faturado) NO FUNIL DE VENDAS (ID 1)

SET @funil_id = 1;

-- Descobre a maior ordem atual para colocar no final
SELECT @max_ordem := IFNULL(MAX(ordem), 0) FROM etapas_funil WHERE funil_id = @funil_id;

-- Insere "Controle de Entrega"
INSERT INTO etapas_funil (funil_id, nome, ordem, cor) 
VALUES (@funil_id, 'Controle de Entrega', @max_ordem + 1, '#8e44ad')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Insere "Faturado"
INSERT INTO etapas_funil (funil_id, nome, ordem, cor) 
VALUES (@funil_id, 'Faturado', @max_ordem + 2, '#2c3e50')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);
