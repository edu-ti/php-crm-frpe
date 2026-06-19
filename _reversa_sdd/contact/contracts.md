# Contact (Contatos B2B), Contratos Externos

## `POST /?endpoint=contact&action=create`

**Descrição**: Cria um novo contato associado a uma organização.

### Request Body (JSON)
```json
{
  "nome": "Maria Silva",
  "email": "maria.silva@empresa.com",
  "telefone": "11988887777",
  "cargo": "Diretora de TI",
  "organizacao_id": 10
}
```

### Response (200 OK)
```json
{
  "success": true,
  "id": 45,
  "message": "Contato cadastrado com sucesso."
}
```

### Response (400 Bad Request) - Duplicidade de E-mail
```json
{
  "success": false,
  "error": "Este e-mail já está sendo utilizado por outro contato."
}
```
