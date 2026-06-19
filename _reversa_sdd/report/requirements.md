# Report (Dashboards, KPIs e Relatórios Analíticos)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo puramente de leitura analítica (OLAP-like) operando sobre a base transacional (OLTP). Ele consolida e agrega dados de faturamento, vendas e eficiência de funil para alimentar dashboards gerenciais, gráficos e KPIs. Seu principal desafio é unir fontes divergentes (Vendas oriundas de Propostas de Oportunidades vs Vendas diretas).

## Responsabilidades
- Fornecer métricas de faturamento (Anual, Mensal) combinando fontes diversas.
- Calcular indicadores-chave como Ticket Médio.
- Gerar estatísticas de conversão de funil (Propostas Ganhas vs Perdidas).
- Classificar clientes/fornecedores baseando-se em curva ABC de faturamento.

## Regras de Negócio
- **Consolidação Híbrida de Faturamento**: O "Faturamento Total" da empresa não vem de uma única tabela. Ele é a união da tabela `propostas` (quando status = 'Aprovada') com a tabela `vendas_fornecedores` (vendas avulsas não originadas em oportunidades). O sistema deve somar ambas e garantir a desduplicação se houver sobreposição (dependendo de como o legado tratava o Handoff). 🟢
- **Cálculo de Ticket Médio**: Volume total transacionado dividido pelo número de "documentos de ganho" (Propostas Aprovadas + Vendas Diretas). 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | KPI de Faturamento Global | Must | A API deve retornar a soma financeira total, consolidando propostas fechadas e vendas avulsas do período solicitado. |
| RF-02 | Dashboard de Funil | Must | O sistema deve entregar a contagem e valor financeiro agregado por cada Etapa de Funil. |
| RF-03 | Curva ABC | Should | O sistema deve ranquear clientes/fornecedores e determinar a curva de impacto no faturamento. |
| RF-04 | Ticket Médio Mensal | Must | O sistema deve calcular o ticket médio fatiando os dados por mês do ano corrente. |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Performance | Carga de Banco | Agregações massivas (`UNION` e `SUM`) diretamente na base OLTP. Alta exigência de I/O em BD grande. | 🟡 |

## Critérios de Aceitação

```gherkin
Dado que existam R$ 10.000 em Propostas Aprovadas no mês
E existam R$ 5.000 em Vendas Fornecedores Avulsas no mesmo mês
Quando o gerente puxar o dashboard de "Faturamento do Mês"
Então o sistema deve exibir "Faturamento: R$ 15.000"

Dado que no mês houveram 3 vendas/propostas fechadas (Total de R$ 15.000)
Quando o sistema calcular o Ticket Médio
Então deve retornar "R$ 5.000"
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Consolidação de Receita | Must | O Dashboard é a "vitrine" do CRM para a diretoria. É a razão principal de se pagar por um CRM. |
| Separação Curva ABC | Could | Valioso analiticamente, mas não essencial para a operação diária na v1 de uma refatoração. |

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/report_handler.php` | `ReportHandler::handleRequest` | 🟢 |
