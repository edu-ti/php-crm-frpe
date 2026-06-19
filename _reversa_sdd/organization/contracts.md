# Organization (Organizações/Empresas), Contratos Externos

## `POST /?endpoint=organization&action=create`

**Descrição**: Cria uma nova organização, validando a não-existência prévia do CNPJ informado.

### Request Body (JSON)
```json
{
  "nome_fantasia": "Tech Solutions LTDA",
  "cnpj": "12.345.678/0001-90",
  "razao_social": "Tecnologia e Solucoes EIRELI"
}
```
*Nota: Apenas `nome_fantasia` é estritamente obrigatório.*

### Response (200 OK)
```json
{
  "success": true,
  "id": 145,
  "message": "Organização cadastrada com sucesso."
}
```

### Response (400 Bad Request) - Falha de Validação
```json
{
  "success": false,
  "error": "Já existe uma organização cadastrada com este CNPJ."
}
```
