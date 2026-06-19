# Proposal (Propostas Comerciais e Sincronização), Contratos Externos

## `POST /?endpoint=proposal&action=create`

**Descrição**: Gera uma nova proposta vinculada a uma oportunidade.

### Request Body (JSON)
```json
{
  "oportunidade_id": 901,
  "valor_total": 12500.00,
  "itens": [
    {
      "produto_id": 55,
      "quantidade": 10,
      "valor_unitario": 1250.00
    }
  ]
}
```

### Response (200 OK)
```json
{
  "success": true,
  "id": 120,
  "message": "Proposta gerada com sucesso e vinculada à oportunidade 901."
}
```

---

## `PUT /?endpoint=proposal&action=update_status`

**Descrição**: Atualiza o status da proposta, disparando o avanço de funil na Oportunidade e o provisionamento de Vendas Fornecedores (caso aprovada).

### Request Body (JSON)
```json
{
  "id": 120,
  "novo_status": "Aprovada"
}
```

### Response (200 OK) - Sucesso com Handoff
```json
{
  "success": true,
  "message": "Status atualizado para Aprovada. Oportunidade movida para 'Ganho'. Provisionamento financeiro gerado com sucesso."
}
```

### Response (403 Forbidden) - Falha de RBAC
```json
{
  "success": false,
  "error": "Você não tem permissão para editar propostas geradas por outro usuário."
}
```
