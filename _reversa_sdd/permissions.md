# Matriz de Permissões (RBAC)

O sistema adota um modelo baseado em Papéis (`roles`) e Permissões (`permissions`) com recursos (`resource`) e ações (`action`).

## Papéis Principais
- **Gestor**: Acesso global. Pode criar/editar usuários, alterar configurações e ver relatórios globais.
- **Analista**: Similar ao gestor, atua no backoffice.
- **Vendedor / Comercial**: Acesso restrito. Só vê os próprios leads, oportunidades e propostas.
- **Marketing**: Acesso voltado para captação de leads.

## Regras de Acesso Implícitas
1. **Regra do 'move'**: A permissão de ação `move` (geralmente em Kanban) exige, no nível do backend, que o usuário também tenha a permissão `edit` para o mesmo recurso.
2. **Fallback**: Se o banco de dados `role_permissions` estiver vazio, o sistema faz fallback para um catálogo hardcoded no backend (`rbac_handler.php`).
3. **Restrição Crítica**: Somente `Gestor` ou `Analista` podem alterar a tabela de `usuarios` e invocar endpoints administrativos de deleção.
