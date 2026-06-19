# Product (Catálogo, Tabelas de Preço e Kits), Contratos Externos

## `POST /?endpoint=product&action=create`

**Descrição**: Cadastra um novo produto base no catálogo.

### Request Body (JSON)
```json
{
  "nome_produto": "Licença Software CRM",
  "descricao_padrao": "Licença anual por usuário"
}
```

### Response (200 OK)
```json
{
  "success": true,
  "id": 105,
  "message": "Produto cadastrado com sucesso."
}
```

---

## `POST /?endpoint=product&action=create_price_table`

**Descrição**: Cria uma tabela de preços e insere valores para os produtos nela.

### Request Body (JSON)
```json
{
  "codigo": "TAB-NORDESTE-2026",
  "itens": [
    {
      "produto_id": 105,
      "valor_unitario": 120.50
    },
    {
      "produto_id": 106,
      "valor_unitario": 80.00
    }
  ]
}
```

### Response (200 OK)
```json
{
  "success": true,
  "id": 12,
  "message": "Tabela de preços cadastrada."
}
```

---

## `POST /?endpoint=product&action=create_kit`

**Descrição**: Monta um Kit (combo) associando itens de tabelas de preço e calculando o Snapshot do valor total.

### Request Body (JSON)
```json
{
  "nome_kit": "Combo Implantação VIP",
  "descricao": "Licença + Treinamento",
  "itens": [
    { "tabela_preco_id": 15 },
    { "tabela_preco_id": 18 }
  ]
}
```

### Response (200 OK)
```json
{
  "success": true,
  "id": 3,
  "valor_total": 4500.00,
  "message": "Kit criado com sucesso."
}
```

---

## `DELETE /?endpoint=product&action=delete_price_item`

**Descrição**: Exclui um item de uma tabela de preço (desde que não pertença a um Kit).

### Request Body (JSON)
```json
{
  "tabela_preco_id": 15
}
```

### Response (200 OK)
```json
{
  "success": true,
  "message": "Preço excluído."
}
```

### Response (403 Forbidden)
```json
{
  "success": false,
  "error": "Não é possível excluir este preço pois ele pertence a um ou mais Kits."
}
```
