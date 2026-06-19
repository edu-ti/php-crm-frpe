# User (Gestão de Usuários e Identidade), Contratos Externos

## `POST /?endpoint=user&action=create`

**Descrição**: Cria um novo usuário de acesso ao CRM. Restrito a Gestores/Analistas.

### Request Body (JSON)
```json
{
  "nome": "João Silva",
  "email": "joao@empresa.com.br",
  "senha": "SenhaForte123!",
  "role": "Vendedor",
  "status": "ativo"
}
```

### Response (201 Created)
```json
{
  "success": true,
  "id": 45,
  "message": "Usuário criado com sucesso."
}
```

### Response (403 Forbidden)
```json
{
  "success": false,
  "error": "Permissão negada. Apenas Gestores ou Analistas podem criar usuários."
}
```

---

## `DELETE /?endpoint=user&action=delete`

**Descrição**: Exclui um usuário do sistema. Faz fallback para inativação (Soft Delete) caso o banco acuse chave estrangeira pendente (ex: usuário já efetuou vendas).

### Request Body (JSON)
```json
{
  "id": 45
}
```

### Response (200 OK) - Exclusão Física (Hard Delete)
```json
{
  "success": true,
  "message": "Usuário excluído permanentemente."
}
```

### Response (200 OK) - Fallback Oculto (Soft Delete)
```json
{
  "success": true,
  "message": "Usuário inativado (possui histórico no sistema)."
}
```
*Nota: A interface administrativa receberá sucesso em ambos os casos, simplificando a UX, embora o estado interno do banco seja diferente.*
