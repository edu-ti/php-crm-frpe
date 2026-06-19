# User (Gestão de Usuários e Identidade), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema `usuarios` criado no banco com os campos `nome`, `email`, `senha` (hash), `role`, e `status` (default: 'ativo').
- [ ] Serviço de Hashing disponível na arquitetura.

## Tarefas

- [ ] T-01, Criar Endpoint de Criação de Usuário (`POST /?endpoint=user&action=create`)
  - Origem no legado: `handle_create_user`.
  - Critério de pronto: Endpoint que valida a existência do email. Aplica hash seguro na senha. Salva na base.
  - Confiança: 🟢

- [ ] T-02, Refatorar Validação de Acesso (Remover Hardcode)
  - Origem no legado: Linha 8 (`if(!in_array($_SESSION['role'], ['Gestor', 'Analista']))`).
  - Critério de pronto: Alterar a validação para usar o módulo `RBAC` em vez de um `if` manual. A rota deve ser protegida por middleware que exija a permissão `user:create` / `user:delete`.
  - Confiança: 🟢

- [ ] T-03, Implementar Exclusão Inteligente (`DELETE /?endpoint=user&action=delete`)
  - Origem no legado: `handle_delete_user` e `PDOException` catch.
  - Critério de pronto: Endpoint de exclusão. Deve tentar invocar `UserRepository->delete($id)`. Caso o ORM ou DBDriver lance exceção de Foreign Key (`23000`), capturar silenciosamente e invocar `UserRepository->updateStatus($id, 'inativo')`.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Teste E2E (T-03 - Exclusão Limpa): Inserir um usuário sem dependências. Chamar o DELETE. Verificar se o registro sumiu fisicamente do banco (AssertNull).
- [ ] TT-02, Teste E2E (T-03 - Fallback Soft Delete): Inserir um usuário. Inserir uma Oportunidade vinculada a este usuário (cria FK). Chamar o DELETE. Verificar se o registro ainda existe, mas com `status = 'inativo'`.

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, Exportar tabela `usuarios`.
- [ ] TM-02, (Opcional) Limpar usuários que já estão `status = 'inativo'` há mais de 5 anos se a política de retenção da empresa permitir.

## Ordem Sugerida
1. T-02 (Ajustar a base de segurança via RBAC).
2. T-01 (Criação de usuários).
3. T-03 (Deleção com tratamento de exceção).

## Lacunas Pendentes (🔴)
Nenhuma lacuna grave no escopo atual. A implementação recomendada com a refatoração do T-02 resolve a maior dívida técnica (hardcode de roles).
