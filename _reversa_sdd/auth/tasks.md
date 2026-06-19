# Auth, Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema/migrations do banco da tabela `usuarios` criados.
- [ ] Conexão PDO/Banco de dados funcional.
- [ ] Configuração de sessão do PHP (ou equivalente) configurada.

## Tarefas

- [ ] T-01, Criar endpoint de login (`POST /?endpoint=auth&action=login`)
  - Origem no legado: `api/handlers/auth_handler.php:6` (`handle_login`)
  - Critério de pronto: Valida presença de `email` e `senha` no payload.
  - Confiança: 🟢

- [ ] T-02, Implementar verificação de credenciais no login
  - Origem no legado: `api/handlers/auth_handler.php:14`
  - Critério de pronto: Consulta usuário por email e status 'Ativo', valida hash com `password_verify`.
  - Confiança: 🟢

- [ ] T-03, Preencher dados da Sessão
  - Origem no legado: `api/handlers/auth_handler.php:26`
  - Critério de pronto: Seta `$_SESSION['user_id']`, `$_SESSION['role']` em caso de sucesso no login e retorna status 200.
  - Confiança: 🟢

- [ ] T-04, Criar endpoint de logout (`GET /?endpoint=auth&action=logout`)
  - Origem no legado: `api/handlers/auth_handler.php:36` (`handle_logout`)
  - Critério de pronto: Limpa a superglobal `$_SESSION` via `session_destroy()`.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Teste do happy path de login (credenciais corretas, usuário ativo)
- [ ] TT-02, Teste do caso de erro de login (credenciais inválidas ou usuário inativo)
- [ ] TT-03, Teste de destruição da sessão ao fazer logout

## Ordem Sugerida
1. T-01 (Setup do endpoint e validação de payload base)
2. T-02 (Integração com banco de dados)
3. T-03 (Finalização do fluxo de sucesso com gerenciamento de sessão)
4. T-04 (Logout, que depende da sessão já estar sendo criada)

## Lacunas Pendentes (🔴)
Nenhuma lacuna técnica encontrada para esta unit básica.
