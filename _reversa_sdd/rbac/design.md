# RBAC (Role-Based Access Control), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=rbac&action=save_permissions` | JSON (`role_id`, `permissions[{permission_id, allowed}]`) | JSON `{"success": true}` | 200, 403 |
| GET | `/?endpoint=rbac&action=get_matrix` | (Nenhum) | JSON `{"roles": [...], "permissions": [...]}` | 200 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_save_role_permissions` | `($pdo, $data)` | `JSON` | Recebe a matriz do frontend e salva. Applica regra de hierarquia "move" -> "edit". |

## Fluxo Principal (Salvar Matriz)
1. Controller roteia para `handle_save_role_permissions`.
2. Recebe a role (`$role_id`) e a lista de checkboxes do frontend (`$permissions`).
3. Inicia transação PDO (`$pdo->beginTransaction()`).
4. Para cada permissão no payload:
   - Verifica se a ação associada a esta `$permission_id` é `move`.
   - Se a ação for `move` e a flag `$allowed` for `true`, o código PHP busca o ID da permissão `edit` para o mesmo recurso.
   - Força a gravação de `edit = true` na matriz para esta role.
5. Executa `INSERT INTO role_permissions (...) ON DUPLICATE KEY UPDATE allowed = ?` (Upsert).
6. Efetua o commit.

## Dependências
- **Database (PDO)**: Tabelas `roles`, `permissions`, `role_permissions`.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Tratamento Upsert (`ON DUPLICATE KEY UPDATE`) em vez de Delete-all/Insert-all | Estrutura relacional comum para permissionamento para não quebrar chaves de auditoria. | 🟢 |
| Regra de hierarquia hardcoded na camada PHP | `api/handlers/rbac_handler.php:188` | 🟢 |
| Fallback em memória (Hardcode) para roles "Admin" e "User" se banco estiver vazio | Linhas finais de setup inicial no handler. | 🟢 |

## Estado Interno
Stateless transacional. A dependência mais crítica é garantir que a leitura subsequente (feita pelo `auth` no login) veja a matriz atualizada imediatamente.

## Observabilidade
Nenhuma logada explicitamente. Alterações em matriz de segurança deveriam gerar logs de auditoria (Security Audit Trail).

## Riscos e Lacunas
- 🟢 Invalidação de Cache/Sessão: Foi definido o uso de Cache Distribuído (Redis) para a arquitetura alvo. Isso elimina o gargalo de consultar o banco em cada requisição e permite revogação instantânea de acessos.
- 🟡 Ausência de Logs de Auditoria: Mudanças em permissões de usuários não deixam rastros na arquitetura atual (falta tabela `audit_log`).
- 🟡 Performance do Upsert: O laço de repetição fazendo UPSERT um a um dentro da transação pode ser lento dependendo da quantidade de checkboxes na interface.
