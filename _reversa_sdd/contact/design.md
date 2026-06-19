# Contact (Contatos B2B), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=contact&action=create` | JSON (`nome`, `email`, `organizacao_id`, etc.) | JSON `{"success": true, "id": X}` | 200, 400 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_contact` | `($pdo, $data)` | `JSON` via `json_response` | Cria contato associado a uma organização. |

## Fluxo Principal
1. O Front Controller `api.php` roteia para `handle_create_contact`.
2. Validação do payload (`data`), garantindo presença de nome e, opcionalmente, email e `organizacao_id`.
3. Verificação de Regra de Negócio: Se o e-mail for fornecido, executa um `SELECT` na tabela `contatos` para garantir unicidade do e-mail.
4. Se o e-mail já existir, retorna HTTP 400 com erro.
5. Inserção do registro na tabela `contatos` via `INSERT INTO`, vinculando à tabela de organizações via Foreign Key (caso `organizacao_id` exista).
6. Retorno HTTP 200 via `json_response` com o ID inserido.

## Fluxos Alternativos
- **E-mail Duplicado:** O processamento é interrompido antes do `INSERT` e a API devolve um erro formatado para o front-end.

## Dependências
- **Database (PDO)**: Tabelas `contatos`.
- **Módulo `organizacao`**: O contato depende da existência de um ID de organização válido, que é normalmente garantido por Constraints do MySQL.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| E-mails de contato devem ser únicos globalmente na tabela de contatos | `api/handlers/contact_handler.php:12` | 🟢 |

## Estado Interno
Sem estado em memória. Registros persistidos no MySQL.

## Observabilidade
Sem emissão explícita de logs estruturados reportada.

## Riscos e Lacunas
- 🟡 E se um mesmo contato pertencer a duas organizações diferentes? A unicidade global de e-mail impede que ele seja cadastrado duas vezes, forçando a arquitetura a ter uma tabela de relacionamento N:N (se desejado no futuro) ou manter 1:N restrito.
