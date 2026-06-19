# User (Gestão de Usuários e Identidade), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=user&action=create` | JSON (`nome`, `email`, `senha`, `role`) | JSON `{"id": X}` | 201, 403, 409 |
| DELETE | `/?endpoint=user&action=delete` | JSON (`id`) | JSON `{"success": true}` | 200, 403, 404 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_user` | `($pdo, $data)` | `JSON` | Realiza hash da senha e valida permissão hardcoded na linha 8. |
| `handle_delete_user` | `($pdo, $data)` | `JSON` | Tenta deletar fisicamente. Em caso de restrição PDOException, aplica update de status. |

## Fluxo Principal (Exclusão Inteligente)
1. Controller roteia para `handle_delete_user`.
2. Verifica se a role logada é `Gestor` ou `Analista`. Se não for, aborta (403).
3. Inicia um bloco `try-catch (\PDOException $e)`.
4. Executa `DELETE FROM usuarios WHERE id = ?`.
5. Se a instrução executar sem erros, o registro é apagado (Hard Delete) e retorna sucesso.
6. Se o banco disparar exceção `23000` (Integrity Constraint Violation), o código entra no bloco `catch`.
7. Dentro do `catch`, o PHP executa `UPDATE usuarios SET status = 'inativo' WHERE id = ?`.
8. Retorna sucesso HTTP 200 como se a deleção tivesse funcionado perfeitamente.

## Dependências
- **Database (PDO)**: Tabela `usuarios`. FKs dependentes: `leads`, `oportunidades`, `vendas_fornecedores`.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Hashing via `password_hash()` | `api/handlers/user_handler.php` no CREATE/UPDATE | 🟢 |
| Hardcode de Papéis no topo do arquivo | `if(!in_array($_SESSION['role'], ['Gestor', 'Analista']))` na linha 8 | 🟢 |
| Soft Delete Acionado por Falha Mecânica | Captura de `PDOException` código `23000` (linha 98). | 🟢 |

## Estado Interno
Stateless transacional.

## Observabilidade
Baixa. A transição de um Hard Delete falho para um Soft Delete ocorre silenciosamente, sem logar no backend que isso aconteceu (oculta do administrador que houve um fallback).

## Riscos e Lacunas
- 🟢 Acoplamento de Segurança (Hardcode): A verificação de cargos fixos ("Gestor", "Analista") será removida e substituída pelo uso nativo da matriz RBAC (ex: checagem de `user:manage`).
- 🟡 Dependência de Comportamento do Banco: O Soft Delete só funciona se as chaves estrangeiras (`ON DELETE RESTRICT`) estiverem perfeitamente configuradas no MySQL. Se alguém rodar um `SET FOREIGN_KEY_CHECKS=0`, o Hard Delete vai passar e deixar dados órfãos.
