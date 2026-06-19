# Email (Comunicações e Templates), Contratos Externos

## `POST /?endpoint=email&action=send_bulk`

**Descrição**: Agenda o disparo em massa de um e-mail para múltiplos leads.

### Request Body (JSON)
```json
{
  "assunto": "Promoção Exclusiva!",
  "html": "<h1>Olá!</h1><p>Confira nossa oferta em <img src='/uploads/promo.jpg'></p>",
  "leads_ids": [10, 15, 23, 44]
}
```

### Response (200 OK)
```json
{
  "success": true,
  "message": "E-mails enfileirados para envio.",
  "queued": 4
}
```

### Response (400 Bad Request)
```json
{
  "success": false,
  "error": "Nenhum lead selecionado."
}
```

---

## `POST /?endpoint=email&action=upload_image`

**Descrição**: Recebe upload de imagem do editor TinyMCE e retorna a URL pública.

### Request Body (Multipart Form-Data)
- `file`: Arquivo de imagem (JPEG/PNG).

### Response (200 OK)
```json
{
  "location": "https://crm.dominio.com/uploads/promo.jpg"
}
```

### Response (400 Bad Request)
```json
{
  "error": "Formato de arquivo não suportado."
}
```
