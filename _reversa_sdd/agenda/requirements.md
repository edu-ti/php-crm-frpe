# Agenda

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Gerenciamento de agendamentos (eventos/reuniões/entregas) com integração nativa para envio de notificações por e-mail e automação de funil.

## Responsabilidades
- Criar, listar, atualizar e remover agendamentos.
- Disparar notificações por e-mail para os envolvidos quando um agendamento é criado.
- Automatizar o avanço do funil de vendas quando o agendamento for de tipos específicos.

## Regras de Negócio
- Se o tipo do agendamento for 'Controle de Entrega', a oportunidade associada deve mudar de etapa automaticamente no funil. 🟢
- Notificações de email são enviadas na criação do agendamento. 🟡

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Criar Agendamento | Must | O sistema deve persistir os dados e acionar a fila/envio de emails |
| RF-02 | Automação de Funil | Must | Se o tipo for Controle de Entrega, a Oportunidade associada atualiza o status |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Integração | Integração com provedor de email (SMTP) | `api/handlers/agenda_handler.php` e dependência de email | 🟢 |

> Inferido a partir do código. Validar com equipe de operações.

## Critérios de Aceitação

```gherkin
Dado uma oportunidade existente
Quando um agendamento do tipo "Controle de Entrega" é criado
Então o agendamento é salvo e a oportunidade muda de etapa automaticamente

Dado os dados de um novo agendamento com convidados
Quando o agendamento é salvo
Então um e-mail de notificação deve ser enviado aos participantes
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| CRUD de Agendamentos | Must | Funcionalidade básica do módulo |
| Disparo de E-mails | Should | É esperado, mas falha de e-mail não deve reverter a criação do evento |
| Mudança de Etapa da Oportunidade | Must | Regra de negócio explícita do funil |

> Prioridade inferida por frequência de chamada e posição na cadeia de dependências.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/agenda_handler.php` | `handle_create_agendamento` | 🟢 |
