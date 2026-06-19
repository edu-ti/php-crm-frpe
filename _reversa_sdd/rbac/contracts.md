# RBAC (Role-Based Access Control), Contratos Externos

## `GET /?endpoint=rbac&action=get_matrix`

**Descrição**: Retorna todas as roles e todas as permissões cadastradas no sistema, úteis para montar a interface de checkboxes.

### Response (200 OK)
```json
{
  "roles": [
    { "id": 1, "name": "Admin" },
    { "id": 2, "name": "Vendedor" }
  ],
  "permissions": [
    {
      "id": 1,
      "resource": "opportunity",
      "action": "view"
    },
    {
      "id": 2,
      "resource": "opportunity",
      "action": "edit"
    },
    {
      "id": 3,
      "resource": "opportunity",
      "action": "move"
    }
  ]
}
```

---

## `POST /?endpoint=rbac&action=save_permissions`

**Descrição**: Recebe um array com o estado modificado dos checkboxes da interface para uma Role específica.

### Request Body (JSON)
```json
{
  "role_id": 2,
  "permissions": [
    {
      "permission_id": 1,
      "allowed": true
    },
    {
      "permission_id": 3,
      "allowed": true
    }
  ]
}
```
*Nota: Embora o front envie `permission_id: 3 (move)` como true e oculte o `edit` do payload, a regra de negócio do backend forçará a ativação do `edit`.*

### Response (200 OK)
```json
{
  "success": true,
  "message": "Permissões salvas com sucesso."
}
```

### Response (403 Forbidden)
```json
{
  "success": false,
  "error": "Você não tem privilégios de Administrador para alterar a matriz."
}
```
