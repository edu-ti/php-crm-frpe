# Agenda, Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Dependências da unit listadas em `design.md` estão disponíveis (Serviço/módulo de Email funcional).
- [ ] Schema/migrations do banco da tabela `agendamentos` e `oportunidades` compatíveis.
- [ ] Configuração de credenciais SMTP definidas no ambiente.

## Tarefas

- [ ] T-01, Criar endpoint base de agendamento (`POST /?endpoint=agenda&action=create`)
  - Origem no legado: `api/handlers/agenda_handler.php:1`
  - Critério de pronto: O endpoint deve receber JSON, validar campos obrigatórios (título, data, oportunidade_id) e salvar no banco de dados.
  - Confiança: 🟢

- [ ] T-02, Implementar notificação de criação via E-mail
  - Origem no legado: `api/handlers/agenda_handler.php` (inferido pelo contexto `dependencies: ["email"]`)
  - Critério de pronto: Após salvar o agendamento no banco, deve invocar a rotina de envio de e-mail passando os dados relevantes.
  - Confiança: 🟡

- [ ] T-03, Implementar automação de funil para "Controle de Entrega"
  - Origem no legado: `api/handlers/agenda_handler.php:96`
  - Critério de pronto: Se o tipo for "Controle de Entrega" e a Oportunidade for fornecida, deve executar um `UPDATE` no status/etapa da `oportunidades`.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Teste do happy path de criação de agendamento simples sem automação.
- [ ] TT-02, Teste do happy path de "Controle de Entrega" validando que a oportunidade muda de etapa.
- [ ] TT-03, Teste mockando o serviço de e-mail para garantir que ele é chamado com os dados corretos.

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, Migração da tabela `agendamentos` legada, adequando as colunas caso haja refatoração (especialmente vínculos de `oportunidade_id`).

## Ordem Sugerida
1. T-01 (Setup do endpoint e banco de dados, fluxo base)
2. T-03 (Adição da regra de negócio de automação do funil de vendas, que só afeta o banco)
3. T-02 (Integração assíncrona/síncrona de E-mail, que adiciona complexidade de rede/SMTP)

## Lacunas Pendentes (🔴)
Necessidade de verificar se as notificações de e-mail possuem template HTML específico ou texto simples configurado via banco de dados.
