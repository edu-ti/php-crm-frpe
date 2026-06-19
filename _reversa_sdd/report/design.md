# Report (Dashboards, KPIs e Relatórios Analíticos), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| GET | `/?endpoint=report&action=dashboard_financeiro` | `?mes=X&ano=Y` | JSON `{faturamento_total: X, ticket_medio: Y}` | 200 |
| GET | `/?endpoint=report&action=funil` | `?mes=X&ano=Y` | JSON `{etapas: [{nome, valor_acumulado, quantidade}]}` | 200 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `ReportHandler::handleRequest` | `($method, $action)` | `JSON` | Ponto de entrada unificado para roteamento interno de relatórios. |

## Fluxo Principal (Consolidação Híbrida - Faturamento)
1. Controller roteia GET para `ReportHandler` com action `dashboard_financeiro`.
2. O Handler recupera os filtros de data (`$mes`, `$ano`) da Query String.
3. Monta uma instrução SQL usando `UNION ALL` para unificar duas fontes:
   - Fonte A: `SELECT valor_total FROM propostas WHERE status = 'Aprovada' AND MONTH(data_criacao) = ?`
   - Fonte B: `SELECT valor_total FROM vendas_fornecedores WHERE origin_type = 'avulsa' AND MONTH(data_venda) = ?` (Condicionante para evitar duplicidade de Handoff).
4. O PHP ou o próprio SQL (via subquery de SUM) soma as linhas resultantes.
5. Em paralelo, faz um `COUNT(*)` sobre a mesma subquery combinada para obter a quantidade de negócios fechados.
6. Calcula no PHP: `$ticketMedio = $faturamentoTotal / $quantidadeNegocios`.
7. Retorna o JSON serializado com as métricas formatadas.

## Dependências
- **Database (PDO)**: Consultas pesadas envolvendo as tabelas `propostas`, `vendas_fornecedores`, `oportunidades`, `etapas_funil`.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Queries complexas montadas dinamicamente na camada Handler em vez de Views no banco de dados. | `api/handlers/report_handler.php:146` | 🟢 |
| Cálculo de ticket médio e divisão de arrays (Curva ABC) efetuados na memória do PHP (`api/handlers/report_handler.php:445`) para poupar processamento do SGBD. | `api/handlers/report_handler.php:445` | 🟢 |
| Centralização de todos os relatórios em um único arquivo (Handler class-based), diferente dos demais CRUDs processuais do legado. | Uso da classe `ReportHandler` | 🟢 |

## Estado Interno
Stateless puro. Read-only em relação ao banco de dados.

## Observabilidade
Nenhuma. Como as queries são pesadas, não há registro de "slow query log" instrumentado na aplicação.

## Riscos e Lacunas
- 🟢 Escalabilidade do Dashboard: O problema do UNION ALL pesado ao vivo foi resolvido na arquitetura alvo. Foi definida a adoção de um Cron Job Noturno (Worker) que consolidará o faturamento diário numa tabela dedicada, otimizando as leituras.
- 🟡 Ausência de Cache: Dashboards são frequentemente recarregados pelos gestores. A cada reload (F5), a mesma query pesada rola de novo. Falta uma camada de Cache (Redis ou Transient DB) com expiração em X minutos para desafogar o MySQL.
