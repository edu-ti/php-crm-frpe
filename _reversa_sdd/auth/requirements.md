# Auth

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Gerenciamento de sessão de usuários no sistema CRM, provendo endpoints de login e logout para controle de acesso.

## Responsabilidades
- Autenticar credenciais de usuários (email e senha).
- Criar e manter a sessão do usuário autenticado no backend.
- Invalidar sessões (logout).

## Regras de Negócio
- Somente usuários com status 'Ativo' podem fazer login. 🟢
- Credenciais inválidas retornam 401 Unauthorized. 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Realizar Login | Must | O sistema deve validar as credenciais e retornar dados do usuário autenticado |
| RF-02 | Realizar Logout | Must | O sistema deve destruir a sessão atual |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Segurança | Controle de acesso via Session | `api/handlers/auth_handler.php` | 🟢 |
| Segurança | Verificação de hash de senhas | `api/handlers/auth_handler.php` | 🟢 |

> Inferido a partir do código. Validar com equipe de operações.

## Critérios de Aceitação

```gherkin
Dado um usuário ativo e credenciais válidas
Quando o endpoint de login é chamado
Então o sistema deve criar a sessão e retornar sucesso

Dado um usuário inativo ou com credenciais inválidas
Quando o endpoint de login é chamado
Então o sistema deve recusar o acesso e não criar sessão
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Login de Usuários | Must | Caminho crítico para uso do CRM |
| Logout de Usuários | Must | Requisito básico de segurança |

> Prioridade inferida por frequência de chamada e posição na cadeia de dependências.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/auth_handler.php` | `handle_login`, `handle_logout` | 🟢 |
