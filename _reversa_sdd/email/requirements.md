# Email (Comunicações e Templates)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo responsável por gerenciar e disparar comunicações via e-mail (envios individuais ou em massa para leads) e suportar os assets estáticos gerados por editores WYSIWYG (TinyMCE).

## Responsabilidades
- Enviar e-mails em massa para base de leads do CRM.
- Fornecer endpoint para upload e armazenamento de imagens inseridas através do editor TinyMCE.
- Processar o corpo do e-mail HTML antes do envio para garantir a compatibilidade.

## Regras de Negócio
- As URLs das imagens (src) inseridas no editor visual (TinyMCE) devem ser convertidas de caminhos relativos para URLs absolutas antes do disparo do e-mail, senão quebram nos clientes de e-mail. 🟢
- O envio em massa deve utilizar provedor configurado (ex: SendGrid/PHPMailer) em vez do `mail()` puro do PHP para evitar cair em SPAM. 🟡

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Envio em Massa para Leads | Must | O sistema deve disparar o mesmo template HTML para uma lista de e-mails selecionados |
| RF-02 | Upload de Imagens WYSIWYG | Should | O sistema deve receber upload de imagem do editor, salvar no disco/S3 e retornar a URL |
| RF-03 | Conversão de URLs Relativas | Must | O HTML deve ser modificado (regex/parser) para inserir o domínio raiz nas tags `<img>` |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Performance | Envio assíncrono ou chunked | `handle_send_bulk_email_leads` (inferido risco de timeout) | 🔴 |
| Integração | SMTP Externo | Dependência de `SendGrid` ou `PHPMailer` no legado | 🟢 |

> Inferido a partir do código. Validar com equipe de operações.

## Critérios de Aceitação

```gherkin
Dado um texto HTML contendo `<img src="/uploads/imagem.jpg">`
Quando a rotina de envio em massa é acionada
Então a tag deve ser transformada para `<img src="https://crm.dominio.com/uploads/imagem.jpg">` antes de enviar ao SMTP

Dado um lote de 500 leads selecionados
Quando o usuário solicita o disparo da campanha
Então os 500 e-mails devem ser enfileirados/enviados sem derrubar o servidor por timeout
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Envio de E-mails | Must | Comunicação nativa do funil de vendas e campanhas de marketing |
| Conversão de Links (TinyMCE) | Must | Sem isso, os leads recebem e-mails quebrados visualmente |
| Upload de Imagem Nativo | Should | Melhora a UX do vendedor ao criar o template |

> Prioridade inferida por frequência de chamada e posição na cadeia de dependências.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/email_handler.php` | `handle_send_bulk_email_leads` | 🟢 |
