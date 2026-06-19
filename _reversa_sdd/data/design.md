# Data (Consultas Centralizadas e Dashboard), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| GET | `/?endpoint=data&action=get_data` | Variáveis de Sessão | JSON com `oportunidades`, `agendamentos`, etc. | 200, 401 |
| GET | `/?endpoint=data&action=get_stats` | Variáveis de Sessão | JSON com `total_ganho`, `conversao` | 200, 401 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_get_data` | `($pdo)` | `JSON` via `json_response` | Compila múltiplas consultas numa única resposta. |
| `handle_get_stats` | `($pdo)` | `JSON` via `json_response` | Agrega e calcula KPIs (conversões, métricas). |

## Fluxo Principal (Get Data)
1. O Front Controller roteia a requisição para `handle_get_data`.
2. A sessão é checada (`$_SESSION['role']` e `$_SESSION['user_id']`).
3. O código constrói as queries SQL de busca.
4. **Data Scoping:** Se o `role` for "Vendedor", a cláusula `WHERE usuario_id = :id` é adicionada nas queries de oportunidades e agendamentos. Se "Admin", as tabelas são buscadas na íntegra.
5. As consultas são disparadas (PDO).
6. Os arrays associativos resultantes são empacotados em um grande array raiz (`$response['oportunidades']`, `$response['usuarios']`, etc).
7. Retorna HTTP 200 via `json_response`.

## Fluxos Alternativos
- **Sessão Inválida:** Retorna erro e força logout (dependendo da implementação geral do middleware/handler).
- **Sem Dados:** Se as queries não retornarem resultados, os arrays correspondentes vão vazios (`[]`), sem causar erro.

## Dependências
- **Database (PDO)**: Acesso de leitura a `oportunidades`, `agendamentos`, `usuarios`, `propostas`, `fornecedores`, `produtos`.
- **Sessão PHP**: Necessita da `$_SESSION` preenchida pelo módulo `auth` para aplicar o RBAC.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Data Scoping no nível do Handler, modificando SQL dinamicamente (sem RLS no banco) | `api/handlers/data_handler.php:21` | 🟢 |
| Over-fetching (Fetch Everything): Retornar todas as coleções na mesma requisição para facilitar o Frontend | `handle_get_data` | 🟢 |

## Estado Interno
Depende da superglobal `$_SESSION`. Não modifica estado.

## Observabilidade
Nenhuma inferida.

## Riscos e Lacunas
- 🔴 Carga excessiva: Ao retornar várias entidades inteiras sem paginação ("Fetch Everything"), a API se tornará um gargalo à medida que a base crescer.
- 🟡 "Vendas Fornecedores": A regra exata de como `fornecedores` e `produtos` se cruzam com `propostas` aprovadas para popular o dashboard de stats pode estar complexa ou espalhada (verificada também no `proposal_handler`).
