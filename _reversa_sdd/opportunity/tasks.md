# Opportunity (Gestão de Oportunidades e Funil), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema do Banco: `oportunidades`, `oportunidade_itens`, `etapas_funil`, `oportunidade_historico` criados no banco de dados.

## Tarefas

- [ ] T-01, Criar Serviço de Cálculo de Valor de Item
  - Origem no legado: Iteração em `handle_create_opportunity` e `handle_update` (linhas 24-30).
  - Critério de pronto: Função pura/isolada que recebe os dados do produto (valor base), o JSON de parâmetros customizados, a quantidade e meses, retornando o valor final do item.
  - Confiança: 🟢

- [ ] T-02, Criar endpoint de Criação de Oportunidade (`POST /?endpoint=opportunity&action=create`)
  - Origem no legado: `handle_create_opportunity`
  - Critério de pronto: Insere a Oportunidade, mapeia o array recebido inserindo em `oportunidade_itens`, roda a T-01 para cada item, soma e atualiza a Oportunidade mãe com o Valor Total calculado. Transacionado.
  - Confiança: 🟢

- [ ] T-03, Criar endpoint de Edição de Oportunidade (`PUT /?endpoint=opportunity&action=update`)
  - Origem no legado: `handle_update_opportunity`
  - Critério de pronto: Deleta itens antigos e insere novos (se mantiver a estratégia destrutiva), executa T-01 e recalcula o total da oportunidade.
  - Confiança: 🟢

- [ ] T-04, Implementar Gatekeepers (Serviço de Validação de Funil)
  - Origem no legado: IFs em `handle_move_opportunity` (linhas 485 e 498).
  - Critério de pronto: Função que receba `oportunidade_id` e a nova `etapa_id`, fazendo `COUNT` na tabela de propostas para autorizar ou não a transição para Negociação/Ganho.
  - Confiança: 🟢

- [ ] T-05, Criar endpoint de Movimentação no Funil (`PATCH /?endpoint=opportunity&action=move`)
  - Origem no legado: `handle_move_opportunity`
  - Critério de pronto: Executa a T-04. Se validado, altera a etapa da oportunidade e faz INSERT em `oportunidade_historico`.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Teste Unitário (T-01): Passar diferentes matrizes de parâmetros JSON para ver se o cálculo bate perfeitamente (lidando com nulls).
- [ ] TT-02, Teste E2E (T-02/T-03): Criar oportunidade e editar. Garantir via asserção de banco que o valor salvo é o calculado pelo servidor, e não o enviado no mock do client.
- [ ] TT-03, Teste E2E (T-04/T-05): Tentar mover oportunidade sem proposta para Negociação (deve dar HTTP 403 ou 400).

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, Re-calcular e auditar os valores totais legados vs a nova rotina, caso existam divergências de arredondamento causadas pela versão antiga do PHP.

## Ordem Sugerida
1. T-01 (Cálculo puro).
2. T-02 e T-03 (Endpoints de Gestão).
3. T-04 e T-05 (Endpoints de Fluxo e Validação).

## Lacunas Pendentes (🔴)
Nenhuma técnica impeditiva.
