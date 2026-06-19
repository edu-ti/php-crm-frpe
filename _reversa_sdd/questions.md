# Perguntas para Validação (Lacunas Encontradas)

> Lacunas arquiteturais e de regras de negócio identificadas durante a revisão cruzada das especificações. Responda para refinar o SDD.

## Q1 - Proposal (Hardcode de Funil)
**Contexto**: O avanço de funil por aprovação de proposta na unit `proposal` está acoplado com IDs fixos no código (`sync_opportunity_stage`).
**Impacto**: 🔴 Dificulta a criação de múltiplos funis no futuro.
**Pergunta**: No novo sistema, você deseja manter esse vínculo atrelado a um ID fixo ou prefere uma configuração dinâmica no banco para definir qual etapa significa "Ganho"?
**Resposta**: Configuração dinâmica no banco de dados (flag `is_won_stage` nas Etapas de Funil).

## Q2 - RBAC (Performance e Invalidação)
**Contexto**: O módulo `rbac` não possui mecanismo de revogação forçada ou cache, e a validação on-the-fly pode ser lenta se não usar Redis ou Payload JWT.
**Impacto**: 🔴 Performance reduzida ou delay na aplicação de bloqueios.
**Pergunta**: Recomendamos injetar a matriz de permissões no JWT do usuário logado (no módulo Auth) ou usar Cache Distribuído (Redis). Qual a abordagem preferida para a refatoração?
**Resposta**: Cache Distribuído (Redis) para garantir revogação em tempo real e alta performance.

## Q3 - User (Forte Acoplamento de Segurança)
**Contexto**: O controle de quem pode criar/excluir usuários no módulo `user` está fixo como "Gestor" ou "Analista" direto no código PHP (hardcoded).
**Impacto**: 🔴 Quebra o isolamento de responsabilidades do RBAC.
**Pergunta**: A nova versão deve mover isso formalmente para a matriz do RBAC (ex: permissão `user:manage`) e remover o hardcode, correto?
**Resposta**: Sim, remover a verificação hardcoded e usar permissão dinâmica `user:manage` atrelada à matriz RBAC.

## Q4 - Report (Escalabilidade de Agregações)
**Contexto**: O cálculo do faturamento global faz `UNION ALL` de transações ao vivo (`propostas` e `vendas_fornecedores`) usando funções de data que quebram índices.
**Impacto**: 🔴 Risco severo de timeout e table lock conforme a base crescer.
**Pergunta**: Para o novo sistema, você aprova a criação de Views Materializadas ou o uso de cron jobs de consolidação financeira noturna para alimentar os dashboards?
**Resposta**: Uso de Cron Job Noturno (Worker) para consolidar os dados numa tabela de faturamento diário.
