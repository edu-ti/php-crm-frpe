# Domínio e Regras de Negócio

## Glossário

- **Lead**: Contato inicial capturado, que ainda não foi qualificado.
- **Oportunidade (Pré-Proposta/Negociação/etc)**: Negócio em andamento que passa pelas etapas de um funil de vendas.
- **Proposta**: Documento formalizado com itens (produtos) e valores, enviado ao cliente.
- **Venda Fornecedor**: Registro consolidado de uma venda para efeito de comissionamento ou fechamento com fornecedor externo.
- **Kit**: Agrupamento de produtos vendidos em conjunto, cujo preço tira um "snapshot" (foto) do valor unitário no momento da criação.
- **Etapa de Funil**: Estágio em que a oportunidade se encontra no Kanban.
- **Multiplicador de Locação**: Parâmetro `meses_locacao` usado para multiplicar o valor unitário dos itens em modelos de locação.

## Regras de Negócio Globais

1. **Sincronização Proposta-Oportunidade**: Ao aprovar uma Proposta, a Oportunidade vinculada avança automaticamente para a etapa correspondente a "Ganho" ou "Fechado".
2. **Geração Automática de Vendas**: Quando uma Proposta transita para 'Aprovada', o sistema cria de forma automatizada o registro na tabela `vendas_fornecedores`.
3. **Visibilidade por Papel (Scoped Data)**: Vendedores enxergam e operam apenas sobre os próprios Leads, Oportunidades e Propostas. Gestores e Analistas possuem visão global.
4. **Cálculo de Valor de Oportunidade/Proposta**: Computado somando os itens, aplicando a quantidade e o multiplicador de locação se houver, além de eventuais descontos. Os parâmetros específicos ficam no JSON `parametros` de `proposta_itens`.
5. **Soft Delete Seguro**: Por causa de restrições de integridade (PDO 23000), o sistema aplica Soft Delete (status='Inativo' e preenche `deleted_at`) em vez de deletar registros que possuem histórico no CRM (ex: Usuários com agendamentos).
