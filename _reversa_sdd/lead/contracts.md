# Lead (Prospecção e Qualificação), Contratos Externos

## `POST /?endpoint=lead&action=import_leads`

**Descrição**: Realiza a importação em lote de uma planilha CSV de Leads.

### Request Body (Multipart Form-Data)
- `file`: Arquivo CSV contendo colunas como "Nome", "Email" e "Telefone".

### Response (200 OK)
```json
{
  "success": true,
  "stats": {
    "total_processado": 150,
    "novos_inseridos": 140,
    "duplicados_ignorados": 10
  },
  "message": "Importação concluída com sucesso."
}
```

### Response (400 Bad Request)
```json
{
  "success": false,
  "error": "Arquivo CSV inválido ou não enviado."
}
```

---

## `POST /?endpoint=lead&action=convert_lead_to_pre_proposal`

**Descrição**: Converte um lead existente em uma nova oportunidade de negócio (Pré-proposta), auto-criando o cliente se necessário.

### Request Body (JSON)
```json
{
  "lead_id": 859,
  "usuario_id": 12
}
```

### Response (200 OK)
```json
{
  "success": true,
  "cliente_id": 412,
  "oportunidade_id": 901,
  "numero_proposta": "2026/015",
  "message": "Lead convertido com sucesso em Pré-proposta."
}
```

### Response (404 Not Found)
```json
{
  "success": false,
  "error": "Lead não encontrado ou já convertido."
}
```

### Response (500 Internal Server Error) - Falha na Transação
```json
{
  "success": false,
  "error": "Erro ao tentar vincular o cliente à oportunidade. Conversão abortada."
}
```
