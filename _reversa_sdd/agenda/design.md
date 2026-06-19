# Agenda, Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=agenda&action=create` | JSON com dados do agendamento (`titulo`, `data`, `tipo`, `oportunidade_id`, etc.) | JSON `{"success": true}` | 200, 400 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_agendamento` | `($pdo, $data)` | `JSON` via `json_response` | Cria agendamento, envia email, muda etapa da oportunidade. |

## Fluxo Principal
1. O Front Controller `api.php` roteia para `handle_create_agendamento`.
2. Validação do payload (`data`).
3. Inserção do registro na tabela `agendamentos`.
4. Chamada de função do módulo `email` para disparar notificação aos convidados.
5. Verificação da regra de negócio: Se o `tipo` do agendamento for "Controle de Entrega" e possuir `oportunidade_id`.
6. Se sim, execução de um `UPDATE` na tabela `oportunidades` para alterar a etapa atual.
7. Retorno HTTP 200 via `json_response`.

## Fluxos Alternativos
- **Falha de Validação:** Se dados essenciais faltarem, retorna HTTP 400.
- **Falha no Envio de Email:** A implementação atual pode não tratar erros de email de forma resiliente, permitindo a criação do agendamento mesmo se o SMTP falhar (comportamento típico legado, inferido).

## Dependências
- **Database (PDO)**: Tabelas `agendamentos` e `oportunidades`.
- **Módulo Email**: Utilizado para notificação.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Lógica de negócio do funil acoplada ao agendamento | `api/handlers/agenda_handler.php:96` | 🟢 |

## Estado Interno
Sem estado em memória. Estado é mantido no MySQL (tabelas `agendamentos` e `oportunidades`).

## Observabilidade
Sem emissão explícita de logs estruturados reportada.

## Riscos e Lacunas
- 🔴 O que acontece se o SMTP de e-mail estiver offline durante o agendamento? Ele reverte a criação no banco ou apenas ignora silenciosamente?
- 🟡 A mudança de etapa na oportunidade é hardcoded, dificultando customizações futuras do pipeline.
