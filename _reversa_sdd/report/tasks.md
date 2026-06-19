# Report (Dashboards, KPIs e Relatórios Analíticos), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Módulos `proposal` (Propostas) e `vendas_fornecedores` operacionais com dados populados (Seeders/Fakers) para permitir testes reais de agregação.

## Tarefas

- [ ] T-01, Criar Query/Serviço Consolidador de Receita (Faturamento Global)
  - Origem no legado: Lógica da linha 146 usando `UNION`.
  - Critério de pronto: Repository Method que receba as flags de data (Mês/Ano) e execute um `SUM` seguro unindo as fontes de receita aprovada e avulsa. Retorna um float/decimal.
  - Confiança: 🟢

- [ ] T-02, Criar Serviço de Cálculo de Ticket Médio
  - Origem no legado: Linha 445 do `report_handler.php`.
  - Critério de pronto: Utiliza a T-01 para pegar o valor e executa uma query de `COUNT` na mesma base. Faz a divisão no Backend tratando divisão por zero.
  - Confiança: 🟢

- [ ] T-03, Criar Endpoint de Dashboard Financeiro (`GET /?endpoint=report&action=dashboard_financeiro`)
  - Origem no legado: Action de relatório.
  - Critério de pronto: Controlador/Endpoint que encapsule a chamada à T-01 e T-02, retornando um JSON limpo com a visão consolidada.
  - Confiança: 🟢

- [ ] T-04, Criar Serviço Analítico de Funil (Pipeline)
  - Origem no legado: Métodos de aglomeração de etapas do funil.
  - Critério de pronto: Query com `GROUP BY etapa_id` na tabela de `oportunidades`, trazendo contagem e soma financeira provável.
  - Confiança: 🟢

- [ ] T-05, (Opcional Arquitetural) Adicionar Camada de Cache
  - Origem no legado: N/A (Sugestão de melhoria).
  - Critério de pronto: Adicionar cache Redis ou similar de 5 minutos aos endpoints de Dashboard para mitigar gargalo de `UNION ALL` no banco de dados em produção.
  - Confiança: 🟡

## Tarefas de Teste

- [ ] TT-01, Teste E2E (T-01): Criar via seeder 1 Proposta Aprovada (R$ 10.000) e 1 Venda Avulsa (R$ 5.000). Chamar o endpoint T-03. O retorno `faturamento_total` deve ser rigorosamente `15000.00`.
- [ ] TT-02, Teste Unitário (T-02): Garantir que se não houver vendas no mês, o cálculo de Ticket Médio retorne `0` em vez de explodir com "Division by Zero Exception".

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, N/A (Relatórios são read-only e não possuem dados próprios, apenas refletem o estado das outras tabelas migradas).

## Ordem Sugerida
1. T-01 e T-02 (Coração analítico financeiro).
2. T-03 (Exposição HTTP do financeiro).
3. T-04 (Analítico de Funil).
4. T-05 (Otimização).

## Lacunas Pendentes (🔴)
As consultas de relatório no modelo antigo (`UNION` em transacional) são o maior ponto de falha para escalar. Considere usar Views Materializadas no banco de dados na reescrita se o volume for alto.
