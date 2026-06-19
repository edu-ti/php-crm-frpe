# Opportunity (Gestão de Oportunidades e Funil)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo core do CRM, responsável por gerenciar o ciclo de vida de uma venda ("Oportunidade" ou "Pré-proposta"). Ele controla a adição de itens/produtos, o cálculo automático do valor total do negócio baseado em regras complexas de locação e parâmetros, e a movimentação restrita através das etapas do funil de vendas.

## Responsabilidades
- Criar e editar dados base da Oportunidade.
- Adicionar, atualizar e remover itens (produtos/serviços) da Oportunidade.
- Recalcular dinamicamente o valor total da Oportunidade sempre que os itens mudam.
- Mover a Oportunidade entre as etapas do funil de vendas (ex: Contato -> Negociação -> Ganho).
- Auditar a movimentação no funil através de histórico.

## Regras de Negócio
- **Cálculo de Valor**: O valor total da oportunidade é estritamente controlado pelo backend. É a soma de `(valor_unitario + custos_parametros) * quantidade * meses_locacao` para todos os itens atrelados. 🟢
- **Gatekeeper de Negociação**: Uma Oportunidade não pode ser movida para a etapa de "Negociação" se não existir ao menos uma Proposta comercial gerada e vinculada a ela. 🟢
- **Gatekeeper de Ganho**: Uma Oportunidade não pode ser movida para a etapa de "Fechado/Ganho" se não existir ao menos uma Proposta vinculada com o status "Aprovada". 🟢
- **Sincronia Reversa**: Se o status da Oportunidade mudar, ações colaterais podem ocorrer nas propostas vinculadas (ex: reprovar propostas abertas se a oportunidade for dada como Perdida). 🟡

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | CRUD de Oportunidade | Must | O sistema deve permitir criar e editar oportunidades atreladas a clientes. |
| RF-02 | Gestão de Itens | Must | O sistema deve permitir adicionar produtos à oportunidade, especificando quantidade e tempo de locação. |
| RF-03 | Motor de Cálculo de Valor | Must | O sistema deve ignorar o valor enviado pelo frontend e recalcular o total da oportunidade baseado nos itens no banco. |
| RF-04 | Movimentação de Funil (Kanban) | Must | O sistema deve permitir alterar a etapa (`etapa_id`) da oportunidade. |
| RF-05 | Validação de Etapas (Gatekeeper) | Must | O sistema deve bloquear o avanço no funil se as regras de proposta não forem atendidas. |
| RF-06 | Histórico de Movimentação | Should | Cada mudança de etapa deve gerar um log na tabela `oportunidade_historico`. |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Segurança / Confiança | Cálculo server-side do valor da venda | O valor recebido no payload de create/update é ignorado/sobrescrito pela rotina de cálculo | 🟢 |
| Desempenho | O(N) no cálculo de totais | O recálculo faz loop pelos itens toda vez que algo muda | 🟢 |

## Critérios de Aceitação

```gherkin
Dado uma oportunidade com 2 itens (Item A: R$ 100 x 2 locações, Item B: R$ 50 x 1 locação)
Quando o recálculo for acionado
Então o valor total da oportunidade deve ser R$ 250,00 travado no banco

Dado uma Oportunidade recém-criada na etapa "Contato Inicial"
Quando o vendedor tentar arrastá-la para "Negociação"
E não houver nenhuma Proposta em PDF gerada para esta oportunidade
Então a API deve retornar Erro "Gere uma proposta antes de avançar para negociação"
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Motor de Cálculo | Must | Previne fraudes ou erros de client-side em que o vendedor lança um valor incorreto para bater meta. |
| Gatekeepers de Funil | Must | Garante o processo comercial (SOP) da empresa, forçando o uso do gerador de propostas. |
| Histórico | Should | Útil para métricas de tempo de ciclo (Cycle Time), mas a transição em si é a prioridade. |

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/opportunity_handler.php` | `handle_create...`, `handle_update...`, `handle_move...` | 🟢 |
