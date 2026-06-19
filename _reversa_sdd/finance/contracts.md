# Finance (Empenhos e Notas Fiscais), Contratos Externos

## `POST /?endpoint=finance&action=create_empenho`

**Descrição**: Registra um novo empenho atrelado a uma oportunidade de negócio.

### Request Body (JSON)
```json
{
  "oportunidade_id": 105,
  "numero_empenho": "2026NE00123",
  "data_emissao": "2026-06-15",
  "valor": "R$ 150.000,00"
}
```

### Response (200 OK)
```json
{
  "success": true,
  "id": 10,
  "message": "Empenho registrado com sucesso."
}
```

### Response (400 Bad Request)
```json
{
  "success": false,
  "error": "Oportunidade não informada ou valor inválido."
}
```

---

## `POST /?endpoint=finance&action=create_nota_fiscal`

**Descrição**: Registra uma Nota Fiscal, podendo ser avulsa ou atrelada a um empenho prévio.

### Request Body (JSON)
```json
{
  "empenho_id": 10,
  "numero_nf": "000.001.555",
  "data_emissao": "2026-06-20",
  "valor": "R$ 150.000,00",
  "observacoes": "Referente ao faturamento da primeira parcela"
}
```
*Nota: `empenho_id` é opcional.*

### Response (200 OK)
```json
{
  "success": true,
  "id": 45,
  "message": "Nota Fiscal registrada com sucesso."
}
```
