# RBAC (Role-Based Access Control)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo base de segurança do sistema. Responsável por gerenciar os perfis de acesso (Roles) e a matriz de permissões, determinando quem pode ler, criar, editar ou excluir recursos dentro do CRM. Ele não autentica o usuário (isso é responsabilidade do `auth`), mas autoriza ações após o login.

## Responsabilidades
- Manter o catálogo de Cargos/Perfis (`roles` como Admin, Vendedor, Gerente).
- Manter o catálogo de Ações por Recurso (`permissions` como `opportunity:create`, `lead:delete`).
- Gerenciar a matriz de cruzamento (`role_permissions`), definindo se um cargo tem acesso a uma ação.
- Fornecer defaults de segurança caso o banco esteja vazio.

## Regras de Negócio
- **Dependência Hierárquica de Ações**: Conceder a permissão de movimentar um registro (ex: `move`) exige estruturalmente que a role também possua a permissão de edição base (`edit`) para aquele recurso. O sistema deve barrar ou auto-conceder o `edit` se o `move` for habilitado. 🟢
- **Semente de Sobrevivência (Fallback)**: Caso as tabelas de permissões sejam acidentalmente truncadas (limpas), o sistema deve possuir um catálogo em memória injetado para não causar lockout total do Administrador. 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Gestão de Matriz | Must | Um administrador pode ligar/desligar permissões específicas (checkboxes) para um Cargo. |
| RF-02 | Validação de Dependência | Must | Se o Admin ligar o "Move" de uma oportunidade, o sistema deve garantir que o "Edit" também esteja ligado. |
| RF-03 | Catálogo Fixo Injetável | Should | Em caso de base zerada, a API fornece as roles "Admin" e "User" via memory fallback para o frontend renderizar a matriz. |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Performance | Caching de Permissões | Como o RBAC é consultado a cada request HTTP, ele costuma ser guardado no Payload do JWT na unit `auth` para evitar queries repetidas na tabela `role_permissions`. | 🟢 |

## Critérios de Aceitação

```gherkin
Dado que estou na tela de Permissões editando a Role "Vendedor"
Quando eu marco a opção "Pode Mover Oportunidade" e clico em Salvar
Então o sistema deve processar a matriz
E deve salvar no banco "move = true" e "edit = true" (implicitamente)

Dado que o banco de dados sofreu um truncate nas tabelas de permissões
Quando a API for inicializada e consultada por permissões
Então ela deve retornar o catálogo fallback com os defaults estruturais lidos em memória.
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Matriz de Permissões | Must | Pilar de segurança de um sistema multi-inquilino/B2B com separação de poderes. |
| Dependência de Edit | Should | Melhora a consistência lógica do sistema e evita bugs onde a UI permite arrastar um card mas a API barra o `UPDATE`. |

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/rbac_handler.php` | `handle_save_role_permissions`, Regra linha `188`, Memory Fallback | 🟢 |
