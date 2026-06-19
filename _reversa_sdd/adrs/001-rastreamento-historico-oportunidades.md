# ADR 001: Rastreamento de Histórico de Oportunidades

## Status
Aceito (Retroativo)

## Contexto
O CRM lida com a progressão de Oportunidades por um funil de vendas. É vital para auditoria e métricas (ex: tempo em cada etapa) saber exatamente quando e por quem uma oportunidade mudou de estágio. Inicialmente, poderia-se apenas atualizar o campo `etapa_id` na Oportunidade.

## Decisão
Implementar uma tabela dedicada `historico_oportunidades` e registrar inserções nela a cada alteração de estágio ou evento significativo no ciclo de vida da oportunidade.

## Consequências
- **Positivas:** Permite construir métricas de funil, calcular tempo de permanência e auditar ações de vendedores.
- **Negativas:** Requer lógica extra nos handlers (`opportunity_handler`) para garantir que o insert no histórico acompanhe o update na entidade principal, idealmente via transação (embora a implementação atual faça de forma sequencial).

## Alternativas Consideradas
- **Triggers no Banco de Dados:** Criar uma trigger `AFTER UPDATE` na tabela `oportunidades`. Descartada porque não é possível registrar facilmente o usuário autenticado que fez a mudança sem passar variáveis de sessão para o MySQL.
- **Tabela de Logs Genérica:** Descartada por falta de indexação específica para relatórios de BI de vendas.
