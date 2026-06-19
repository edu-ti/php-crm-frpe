# Proposal (Propostas Comerciais e Sincronização), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema `propostas` e `proposta_itens` criados e referenciando `oportunidades`.
- [ ] Módulo Financeiro (`vendas_fornecedores`) acessível ou com interface de Eventos disponível.

## Tarefas

- [ ] T-01, Criar endpoint de Geração de Proposta (`POST /?endpoint=proposal&action=create`)
  - Origem no legado: `handle_create_proposal`
  - Critério de pronto: Recebe `oportunidade_id`, copia/vincula os itens para a nova estrutura de proposta e inicializa com status "Rascunho".
  - Confiança: 🟢

- [ ] T-02, Implementar Gatekeeper de RBAC (Autorização)
  - Origem no legado: `handle_update_proposal` (linha 195).
  - Critério de pronto: Middleware ou Policy `canEditProposal($user, $proposal)` que bloqueia se o perfil for Vendedor E o `$proposal->usuario_id` for diferente do logado.
  - Confiança: 🟢

- [ ] T-03, Extrair Dicionário Dinâmico de Etapas
  - Origem no legado: Hardcode em `sync_opportunity_stage`.
  - Critério de pronto: Criar tabela de configuração de funil (`funil_config_sync`) ou variáveis de ambiente para que o De-Para de "Status Proposta" -> "ID Etapa" não fique chumbado no código.
  - Confiança: 🟡 (Melhoria arquitetural).

- [ ] T-04, Criar Serviço de Sincronização de Funil (Reverse-Sync)
  - Origem no legado: `sync_opportunity_stage` (linha 126).
  - Critério de pronto: Função `syncOpportunityStage($oportunidade_id, $novo_status_proposta)` que executa o UPDATE na Oportunidade usando o dicionário da T-03.
  - Confiança: 🟢

- [ ] T-05, Criar Serviço de Handoff Operacional
  - Origem no legado: `create_vendas_fornecedores_from_proposal` (linha 131).
  - Critério de pronto: Função que lê os dados do cliente e da proposta e faz o INSERT em `vendas_fornecedores`. *Nota: Se a nova arquitetura usar Event-Driven, essa tarefa se transforma em disparar o evento `ProposalApprovedEvent`*.
  - Confiança: 🟢

- [ ] T-06, Criar endpoint de Atualização de Status (`PUT /?endpoint=proposal&action=update_status`)
  - Origem no legado: `handle_update_proposal`
  - Critério de pronto: Endpoint transacionado. Executa a T-02. Salva o novo status. Executa a T-04. Se o status for "Aprovada", executa a T-05. Faz commit.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Teste Unitário (T-02): Mockar um Vendedor tentando editar proposta de outro Vendedor. Deve retornar false/exception.
- [ ] TT-02, Teste E2E (T-06 - Sincronização): Atualizar proposta para "Enviada". Verificar no banco se a Oportunidade associada mudou para a etapa mapeada de Negociação.
- [ ] TT-03, Teste E2E (T-06 - Handoff): Aprovar uma proposta. Verificar se um novo ID de `vendas_fornecedores` foi gerado no banco com o valor exato da proposta.

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, Auditar propostas legadas "Aprovadas" que não possuem registro equivalente em `vendas_fornecedores` devido a falhas passadas na transação (se existirem).

## Ordem Sugerida
1. T-02 e T-03 (Fundações de segurança e config).
2. T-04 e T-05 (Side-effects isolados e testáveis unitariamente).
3. T-01 e T-06 (Endpoints HTTP que orquestram os side-effects).

## Lacunas Pendentes (🔴)
Nenhuma impeditiva. A T-03 é altamente recomendada para evitar o "número mágico" de hardcodes de banco.
