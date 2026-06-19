# Auth, Contratos Externos

## `POST /?endpoint=auth&action=login`

**Descrição**: Autentica o usuário e inicia a sessão no servidor.

### Request Body (JSON)
```json
{
  "email": "user@example.com",
  "senha": "password123"
}
```

### Response (200 OK)
```json
{
  "success": true,
  "user": {
    "id": 1,
    "nome": "João Silva",
    "email": "user@example.com",
    "role": "Vendedor",
    "status": "Ativo"
  }
}
```

### Response (401 Unauthorized)
```json
{
  "success": false,
  "error": "Credenciais inválidas."
}
```

---

## `GET /?endpoint=auth&action=logout`

**Descrição**: Destrói a sessão atual do usuário autenticado.

### Response (200 OK)
```json
{
  "success": true,
  "message": "Logout realizado com sucesso."
}
```
