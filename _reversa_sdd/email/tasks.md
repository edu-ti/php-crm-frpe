# Email (Comunicações e Templates), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Configuração do provedor de e-mail (SMTP / SendGrid / Amazon SES) injetada nas variáveis de ambiente.
- [ ] Sistema de Filas (Jobs/Queues) preparado (ex: Redis + Supervisor), dado que o envio síncrono é um risco.

## Tarefas

- [ ] T-01, Criar endpoint base para upload de imagens WYSIWYG (`POST /?endpoint=email&action=upload_image`)
  - Origem no legado: Inferido do handler / TinyMCE nativo.
  - Critério de pronto: Receber o arquivo, salvar com nome único em storage público e retornar JSON `{ "location": "URL" }`.
  - Confiança: 🟢

- [ ] T-02, Criar endpoint de enfileiramento de e-mails em massa (`POST /?endpoint=email&action=send_bulk`)
  - Origem no legado: `api/handlers/email_handler.php:1`
  - Critério de pronto: Endpoint recebe payload (leads_ids, subject, html).
  - Confiança: 🟢

- [ ] T-03, Implementar parser do HTML para conversão de URLs (TinyMCE)
  - Origem no legado: `api/handlers/email_handler.php:29`
  - Critério de pronto: Processar o campo `html`, transformando `src="/uploads/..."` em `src="https://dominio.com/uploads/..."`.
  - Confiança: 🟢

- [ ] T-04, Consultar lista de Leads e despachar Job assíncrono (Refatoração do Legado)
  - Origem no legado: `handle_send_bulk_email_leads` (Era síncrono, agora deve ser assíncrono).
  - Critério de pronto: Executar o `SELECT` e disparar N jobs na fila para envio real em background. Retornar 200 pro front-end na hora.
  - Confiança: 🟡

## Tarefas de Teste

- [ ] TT-01, Testar upload de imagem simulando requisição multipart e garantindo o JSON de resposta exato.
- [ ] TT-02, Injetar HTML com tags `<img>` relativas e absolutas e testar se a conversão ocorre apenas nas relativas.
- [ ] TT-03, Testar o disparo enviando lista de 5 IDs fictícios e checar se 5 jobs caíram na fila de envio.

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, N/A (Módulo transitório, não gerencia dados persistentes além dos uploads que já devem ser copiados).

## Ordem Sugerida
1. T-01 (Isolado e simples, pré-requisito pro frontend).
2. T-03 (Lógica de regex/DOM parser, ideal para começar e testar unitariamente).
3. T-02 e T-04 (Integração do endpoint com o sistema de mensageria).

## Lacunas Pendentes (🔴)
Falta confirmação de qual provedor SMTP/API será usado na reimplementação, para dimensionar a SDK (SendGrid API vs SMTP Genérico via PHPMailer/SymfonMailer).
