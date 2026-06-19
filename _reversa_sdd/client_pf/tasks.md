# Client PF (Pessoa Física), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema/migrations da tabela `clientes_pf` criados (com chaves únicas em `cpf` e `email`).
- [ ] Conexão PDO funcional.

## Tarefas

- [ ] T-01, Criar endpoint base de criação de cliente (`POST /?endpoint=client_pf&action=create`)
  - Origem no legado: `api/handlers/client_pf_handler.php:1`
  - Critério de pronto: Endpoint recebe JSON com dados do cliente e executa validação básica de payload.
  - Confiança: 🟢

- [ ] T-02, Implementar validação de unicidade (CPF e Email)
  - Origem no legado: `api/handlers/client_pf_handler.php:18`
  - Critério de pronto: Consultar o banco para garantir que CPF e E-mail não existam. Retornar 400 se já existirem.
  - Confiança: 🟢

- [ ] T-03, Implementar inserção no banco de dados para criação unitária
  - Origem no legado: `api/handlers/client_pf_handler.php`
  - Critério de pronto: Inserir dados na tabela `clientes_pf` e retornar o ID gerado com status 200.
  - Confiança: 🟢

- [ ] T-04, Criar endpoint de importação em lote (`POST /?endpoint=client_pf&action=import_batch`)
  - Origem no legado: Algoritmo referenciado no `client_pf_handler.php`
  - Critério de pronto: O endpoint deve aceitar upload de CSV e processar as linhas.
  - Confiança: 🟢

- [ ] T-05, Implementar lógica de processamento em lote com detecção de duplicidade
  - Origem no legado: Algoritmo referenciado no `client_pf_handler.php`
  - Critério de pronto: Validar unicidade linha a linha, pular registros já existentes, inserir novos e retornar estatísticas de sucesso/falha.
  - Confiança: 🟡

## Tarefas de Teste

- [ ] TT-01, Teste do happy path de criação de cliente.
- [ ] TT-02, Teste de violação de unicidade (tentar criar cliente com CPF ou E-mail já existente).
- [ ] TT-03, Teste de importação de CSV com linhas mistas (novos clientes e clientes já existentes).

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, N/A para esta fase (a menos que a estrutura da tabela legado `clientes_pf` seja modificada).

## Ordem Sugerida
1. T-01, T-02, T-03 (Implementação do fluxo síncrono e unitário, estabelecendo as regras de negócio de unicidade).
2. TT-01, TT-02 (Garantir que as regras estão firmes).
3. T-04, T-05 (Reutilizar a lógica de validação para a importação em lote).

## Lacunas Pendentes (🔴)
Necessidade de investigar se a importação em lote suporta atualização (upsert) se o CPF for o mesmo, ou se apenas ignora (comportamento atual inferido é ignorar).
