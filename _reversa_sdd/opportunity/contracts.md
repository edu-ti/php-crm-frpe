# Opportunity (Gestão de Oportunidades e Funil), Contratos Externos

## `POST /?endpoint=opportunity&action=create`

**Descrição**: Cria uma nova oportunidade (Pré-proposta) atrelada a um cliente e calcula seu valor com base nos itens fornecidos.

### Request Body (JSON)
```json
{
  "cliente_id": 412,
  "titulo": "Locação de Computadores 2026",
  "etapa_id": 1,
  "itens": [
    {
      "produto_id": 55,
      "quantidade": 10,
      "meses_locacao": 12,
      "parametros_json": "{\"custo_frete\": 50, \"seguro\": 10}"
    }
  ]
}
```

### Response (200 OK)
```json
{
  "success": true,
  "id": 901,
  "message": "Oportunidade criada com sucesso.",
  "valor_calculado": 12500.00
}
```

---

## `PUT /?endpoint=opportunity&action=update`

**Descrição**: Atualiza os dados base e recria os itens vinculados a uma oportunidade, recalculando o total.

### Request Body (JSON)
```json
{
  "id": 901,
  "titulo": "Locação de Computadores 2026 (Atualizado)",
  "itens": [
    {
      "produto_id": 55,
      "quantidade": 15,
      "meses_locacao": 12,
      "parametros_json": "{}"
    }
  ]
}
```

### Response (200 OK)
```json
{
  "success": true,
  "message": "Oportunidade atualizada.",
  "novo_valor": 18000.00
}
```

---

## `PATCH /?endpoint=opportunity&action=move`

**Descrição**: Tenta mover a oportunidade para uma nova etapa do funil (com validação de Gatekeepers).

### Request Body (JSON)
```json
{
  "id": 901,
  "nova_etapa_id": 4
}
```
*(Considerando etapa_id = 4 como Negociação)*

### Response (200 OK) - Sucesso
```json
{
  "success": true,
  "message": "Oportunidade movida para a nova etapa."
}
```

### Response (403 Forbidden) - Bloqueio de Gatekeeper
```json
{
  "success": false,
  "error": "Não é possível mover para 'Negociação' sem possuir ao menos uma Proposta gerada."
}
```
