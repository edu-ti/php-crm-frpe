# Email (Comunicações e Templates), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=email&action=send_bulk` | JSON (`assunto`, `html`, `leads_ids` array) | JSON `{"success": true}` | 200, 400, 500 |
| POST | `/?endpoint=email&action=upload_image` | Multipart form-data (`file`) | JSON `{"location": "url_absoluta"}` | 200, 400 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_send_bulk_email_leads` | `($pdo, $data)` | `JSON` via `json_response` | Varre leads e envia e-mail via provedor. |

## Fluxo Principal (Envio em Massa)
1. O Front Controller roteia para `handle_send_bulk_email_leads`.
2. O payload é recebido com a lista de IDs de leads selecionados no grid do frontend, o assunto da campanha e o HTML (já parseado).
3. **Parse de Imagens:** Uma expressão regular/parser analisa o corpo do HTML. Atributos `src` relativos (ex: `src="uploads/xyz.jpg"`) são reescritos anexando a constante `APP_URL` ou protocolo/domínio raiz da aplicação, tornando-os absolutos.
4. Consulta ao banco: O sistema roda um `SELECT email FROM leads WHERE id IN (...)` (supondo o formato).
5. O sistema instancia a biblioteca de e-mail (SendGrid, PHPMailer ou mailer configurado).
6. Um laço (`foreach`) itera sobre a lista e enfileira/envia a mensagem (com BCC para evitar exposição ou envios individuais).
7. Retorna HTTP 200.

## Fluxos Alternativos
- **Nenhum Lead selecionado:** O endpoint rejeita com 400.
- **Falha no SMTP:** Lança exceção ou registra erro, podendo interromper o lote.

## Dependências
- **Database (PDO)**: Tabelas de `leads` (e possivelmente `clientes_pf` dependendo do escopo das campanhas).
- **Provedor de E-mail**: SDK do SendGrid ou PHPMailer.
- **File System**: Pasta `uploads/` para armazenar imagens do TinyMCE.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Substituição de imagens em tempo de execução via Regex/String Replace antes do envio | `api/handlers/email_handler.php:29` | 🟢 |

## Estado Interno
Depende de File System para upload das imagens.
Ausência de Fila (Queue): Aparentemente, o envio é processado no próprio request HTTP, criando alto risco de bloqueio.

## Observabilidade
Falta histórico de campanhas. Não há inferência sobre tabela de "logs de disparo" ou acompanhamento de abertura (Tracking Pixel).

## Riscos e Lacunas
- 🔴 Timeout Request: Enviar mais de 100 e-mails de uma vez em um script síncrono PHP causa `Max Execution Time Exceeded`. Na reimplementação, é mandatório usar Filas (Queues) assíncronas (ex: Redis/RabbitMQ ou Cron job processando tabela de fila).
- 🟡 O editor TinyMCE espera o endpoint padrão `location`, caso contrário o upload da imagem falha visualmente.
