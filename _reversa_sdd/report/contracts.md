# Report (Dashboards, KPIs e Relatórios Analíticos), Contratos Externos

## `GET /?endpoint=report&action=dashboard_financeiro`

**Descrição**: Retorna os KPIs financeiros principais filtrados por mês e ano. Combina receitas de Oportunidades Ganhas e Vendas Diretas.

### Query Parameters
- `mes` (int): Mês (1-12). Padrão: mês atual.
- `ano` (int): Ano (ex: 2026). Padrão: ano atual.

### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "faturamento_total": 45500.00,
    "ticket_medio": 3500.00,
    "quantidade_negocios": 13,
    "crescimento_vs_mes_anterior": 12.5
  }
}
```

---

## `GET /?endpoint=report&action=funil`

**Descrição**: Retorna o agregado financeiro e volumétrico das Oportunidades divididas por etapa de funil, útil para renderizar gráficos de pipeline ou charts estilo "Funnel".

### Query Parameters
- `mes` (int): Mês (1-12). Opcional.
- `ano` (int): Ano (ex: 2026). Opcional.

### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "etapas": [
      {
        "id": 1,
        "nome": "Qualificação",
        "quantidade": 15,
        "valor_acumulado": 120000.00
      },
      {
        "id": 2,
        "nome": "Apresentação",
        "quantidade": 8,
        "valor_acumulado": 64000.00
      },
      {
        "id": 3,
        "nome": "Negociação",
        "quantidade": 5,
        "valor_acumulado": 45000.00
      },
      {
        "id": 4,
        "nome": "Ganho",
        "quantidade": 10,
        "valor_acumulado": 35500.00
      }
    ]
  }
}
```
