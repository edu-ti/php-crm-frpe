# RBAC (Role-Based Access Control), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema `roles`, `permissions` e a tabela pivô `role_permissions` criados no banco. Recomenda-se chave composta Unique `(role_id, permission_id)`.

## Tarefas

- [ ] T-01, Criar Seeder de Catálogo Fixo (Memory Fallback)
  - Origem no legado: Linhas finais de inicialização no `rbac_handler`.
  - Critério de pronto: Script ou Migration/Seeder rodando na inicialização que garanta a existência das Roles básicas (ex: Admin, Vendedor) e da lista master de Permissions, caso o banco esteja vazio.
  - Confiança: 🟢

- [ ] T-02, Criar Endpoint de Leitura da Matriz (`GET /?endpoint=rbac&action=get_matrix`)
  - Origem no legado: Consulta SQL de interface de permissões.
  - Critério de pronto: Endpoint que retorna JSON com os perfis e as permissões já setadas (true/false) para a UI desenhar os checkboxes.
  - Confiança: 🟢

- [ ] T-03, Implementar Serviço de Hierarquia de Permissões
  - Origem no legado: Validação `if ($action == 'move')` (linha 188).
  - Critério de pronto: Função isolada `applyPermissionHierarchy(array $permissions): array` que varre a lista recebida do frontend e, se achar um "move" = true, força a injeção do respectivo "edit" = true na mesma lista antes de salvar.
  - Confiança: 🟢

- [ ] T-04, Criar Endpoint de Salvamento da Matriz (`POST /?endpoint=rbac&action=save_permissions`)
  - Origem no legado: `handle_save_role_permissions`.
  - Critério de pronto: Endpoint transacionado. Recebe o JSON. Roda a T-03. Executa iteração fazendo UPSERT em `role_permissions` ou aplica padrão Delete-all/Insert-all seguro.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Teste Unitário (T-03): Passar um array com `['resource' => 'opportunity', 'action' => 'move', 'allowed' => true]` e verificar se a função injeta automaticamente a ação `edit` como `true`.
- [ ] TT-02, Teste E2E (T-04): Chamar o endpoint com 10 permissões. Verificar no banco de dados se as 10 linhas em `role_permissions` foram atualizadas corretamente sem duplicar.

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, Exportar a tabela `role_permissions` do MySQL legado para carregar as customizações que o cliente já fez para os perfis atuais.

## Ordem Sugerida
1. T-01 (Seeder).
2. T-02 (Leitura).
3. T-03 (Regras de negócio isoladas).
4. T-04 (Gravação com UPSERT).

## Lacunas Pendentes (🔴)
Falta um mecanismo de cache na leitura para evitar overload do banco a cada verificação de permissionamento pelo middleware de rotas.
