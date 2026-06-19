# Agenda, Contratos Externos

## `POST /?endpoint=agenda&action=create`

**Descrição**: Cria um novo agendamento, envia notificações por email e, dependendo do tipo, avança a etapa da oportunidade no funil.

### Request Body (JSON)
```json
{
  "titulo": "Reunião de Alinhamento",
  "data": "2026-06-20T14:00:00Z",
  "tipo": "Controle de Entrega",
  "oportunidade_id": 42,
  "participantes": ["cliente@example.com", "vendedor@crm.com.br"]
}
```

### Response (200 OK)
```json
{
  "success": true,
  "message": "Agendamento criado com sucesso."
}
```

### Response (400 Bad Request)
```json
{
  "success": false,
  "error": "Campos obrigatórios ausentes."
}
```
