# Invoice Parser (Leitor de Notas Fiscais), Contratos Externos

## `POST /?endpoint=invoice_parser&action=parse`

**Descrição**: Recebe um arquivo PDF de NFe, extrai o texto e aplica expressões regulares para estruturar os dados.

### Request Body (Multipart Form-Data)
- `pdf_file`: Arquivo `.pdf` da nota fiscal.
- `oportunidade_id` (opcional): ID da oportunidade para vínculo automático.

### Response (200 OK)
```json
{
  "success": true,
  "data": {
    "valor_total": "12500.50",
    "cnpj_destinatario": "12.345.678/0001-90",
    "itens": [
      {
        "descricao": "Serviços de Consultoria TI",
        "valor": "12500.50"
      }
    ]
  },
  "message": "Nota fiscal processada com sucesso."
}
```

### Response (400 Bad Request) - Falha no Parser
```json
{
  "success": false,
  "error": "Não foi possível extrair texto do PDF ou o layout é desconhecido."
}
```
