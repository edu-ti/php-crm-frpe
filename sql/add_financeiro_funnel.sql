-- Criação do Funil Financeiro
INSERT INTO `funis` (`nome`, `descricao`) VALUES ('Contratos', 'Funil Financeiro para acompanhamento de contratos e faturamentos');

-- Precisamos do ID do funil recém-criado para associar as etapas.
-- Como normalmente não temos "LAST_INSERT_ID()" de forma fácil num script solto se não executado no momento,
-- podemos assumir que será o próximo ID disponível ou o script precisará ser adaptado de acordo.
-- Vamos usar uma variável no script SQL:
SET @funil_financeiro_id = LAST_INSERT_ID();

-- Criação das etapas do Funil Financeiro associando ao funil criado
INSERT INTO `etapas_funil` (`funil_id`, `nome`, `ordem`, `cor`, `probabilidade`) VALUES 
(@funil_financeiro_id, 'Clientes', 1, '#1e40af', 10), -- blue-800
(@funil_financeiro_id, 'Aguardando Faturamento', 2, '#ca8a04', 50), -- yellow-600
(@funil_financeiro_id, 'Faturado', 3, '#166534', 100); -- green-800
