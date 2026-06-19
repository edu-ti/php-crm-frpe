# Client PF, Contratos Externos

## `POST /?endpoint=client_pf&action=create`

**Descrição**: Cria um novo registro de cliente pessoa física.

### Request Body (JSON)
```json
{
  "nome": "João da Silva",
  "cpf": "12345678901",
  "email": "joao@example.com",
  "telefone": "11999999999"
}
```

### Response (200 OK)
```json
{
  "success": true,
  "id": 105,
  "message": "Cliente cadastrado com sucesso."
}
```

### Response (400 Bad Request) - Duplicidade
```json
{
  "success": false,
  "error": "CPF ou E-mail já cadastrado."
}
```

---

## `POST /?endpoint=client_pf&action=import_batch`

**Descrição**: Importa clientes em lote a partir de arquivo CSV.

### Request Body (Multipart Form-Data)
- `file`: Arquivo CSV.

### Response (200 OK)
```json
{
  "success": true,
  "message": "Importação concluída.",
  "stats": {
    "total_linhas": 100,
    "inseridos": 90,
    "duplicados_ignorados": 10
  }
}
```
