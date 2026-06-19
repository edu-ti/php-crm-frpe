# Data (Consultas Centralizadas e Dashboard), Contratos Externos

## `GET /?endpoint=data&action=get_data`

**Descrição**: Retorna os dados mestres do CRM, com filtragem aplicada dependendo do RBAC do usuário.

### Request Body
N/A (Sessão é utilizada via Cookie).

### Response (200 OK)
```json
{
  "success": true,
  "oportunidades": [
    { "id": 1, "titulo": "Negócio XYZ", "valor": 10000.00 }
  ],
  "agendamentos": [
    { "id": 5, "data": "2026-06-20T10:00:00Z" }
  ],
  "usuarios": [
    { "id": 2, "nome": "Carlos" }
  ],
  "fornecedores": [],
  "produtos": [],
  "vendas_fornecedores": []
}
```

---

## `GET /?endpoint=data&action=get_stats`

**Descrição**: Retorna estatísticas agregadas e KPIs do pipeline de vendas.

### Request Body
N/A.

### Response (200 OK)
```json
{
  "success": true,
  "stats": {
    "total_oportunidades": 120,
    "total_ganho": 550000.00,
    "taxa_conversao": 35.5,
    "propostas_aprovadas": 42
  }
}
```
