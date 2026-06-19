# Auth, Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=auth&action=login` | `email`, `senha` | `JSON` com user details | 200, 401 |
| GET | `/?endpoint=auth&action=logout` | None | `JSON` | 200 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_login` | `($pdo, $data)` | `JSON` via `json_response` | Inicializa sessão PHP. |
| `handle_logout` | `()` | `JSON` via `json_response` | Destrói a sessão PHP. |

## Fluxo Principal
1. O Front Controller `api.php` recebe a requisição para `endpoint=auth` e chama as funções apropriadas em `api/handlers/auth_handler.php`.
2. Em `handle_login`, os dados recebidos via requisição POST (JSON) são validados para presença de email e senha.
3. É feita uma consulta SQL na tabela `usuarios` com o `email` fornecido e cujo `status` seja 'Ativo'.
4. A senha fornecida é verificada contra o hash no banco de dados usando `password_verify` nativo do PHP.
5. Em caso de sucesso, `$_SESSION['user_id']`, `$_SESSION['role']` e outras variáveis são preenchidas. O servidor retorna HTTP 200 com os dados básicos do usuário.

## Fluxos Alternativos
- **Senha Inválida ou Usuário Inativo:** A consulta ao banco não retorna resultados compatíveis ou `password_verify` falha. A API retorna HTTP 401 `{"error": "Credenciais inválidas"}` e interrompe a execução.
- **Logout:** A função `session_destroy()` é chamada, invalidando o cookie atual do backend.

## Dependências
- **Database (PDO)**: Usado para buscar o usuário por email.
- **`config.php`**: Inicializa a sessão (`session_start()`) e as funções de utilidade (como `json_response`).

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Autenticação baseada em sessão (Session Cookie) e não em JWT puro | `api/handlers/auth_handler.php` | 🟢 |
| Senhas hasheadas via `password_hash`/`password_verify` | `api/handlers/auth_handler.php` | 🟢 |

## Estado Interno
O módulo manipula a superglobal `$_SESSION`, salvando o estado autenticado do usuário no servidor para uso pelos outros handlers (como o RBAC).

## Observabilidade
Sem emissão explícita de logs estruturados além de retornos de API para o frontend.

## Riscos e Lacunas
- 🟡 Ausência de bloqueio de tentativas de força bruta (rate limiting no login).
