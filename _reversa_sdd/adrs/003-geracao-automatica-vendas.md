# ADR 003: Geração Automática de Vendas a partir de Propostas

## Status
Aceito (Retroativo)

## Contexto
Quando uma proposta é aprovada, o setor de compras/financeiro/fornecedores precisa registrar a "Venda ao Fornecedor" para comissionamento e faturamento, um passo que antes era manual e suscetível a esquecimento.

## Decisão
A rotina de `update` da Proposta (`proposal_handler`) intercepta a transição para o status `Aprovada` e invoca imediatamente a função `create_vendas_fornecedores_from_proposal`, inserindo a venda consolidada no módulo de fornecedores.

## Consequências
- **Positivas:** Reduz fricção operacional e erro humano. O Dashboard unifica a visualização via UNION ALL sem o risco de discrepância.
- **Negativas:** Acoplamento forte entre o módulo de Propostas e o módulo de Vendas/Fornecedores no backend. Uma falha ao salvar a venda impede a atualização da proposta (se estiver em transação).

## Alternativas Consideradas
- **Job Assíncrono / Fila:** Descartado por complexidade de infraestrutura num ambiente PHP monolítico e tradicional.
- **Trigger no Banco de Dados:** Descartado porque a lógica exige ler os itens da proposta e resolver o ID da organização para o mapeamento correto.
