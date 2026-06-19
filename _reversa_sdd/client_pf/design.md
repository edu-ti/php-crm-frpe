# Client PF (Pessoa Física), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=client_pf&action=create` | JSON (`nome`, `cpf`, `email`, etc.) | JSON `{"success": true, "id": X}` | 200, 400 |
| POST | `/?endpoint=client_pf&action=import_batch` | Multipart Form / CSV | JSON com stats de importação | 200, 400 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_cliente_pf` | `($pdo, $data)` | `JSON` via `json_response` | Cria cliente único após validar CPF/Email. |

## Fluxo Principal (Criação de Cliente)
1. O Front Controller `api.php` roteia a requisição para `handle_create_cliente_pf`.
2. O payload é validado (presença e formato básico de CPF e E-mail).
3. São executadas consultas `SELECT` na tabela `clientes_pf` para verificar se o `cpf` ou o `email` já existem.
4. Se existirem, a execução é interrompida com retorno HTTP 400 (ou 409) informando a duplicidade.
5. Caso não existam, o registro é inserido via `INSERT INTO clientes_pf` e o ID gerado é retornado ao cliente.

## Fluxos Alternativos
- **Importação em Lote:**
  1. A API recebe um arquivo CSV contendo múltiplos clientes.
  2. Para cada linha, executa as validações de unicidade de CPF e Email.
  3. Linhas com duplicatas são logadas em uma variável de estado interna do loop e ignoradas na inserção.
  4. Linhas válidas são inseridas.
  5. Retorna o total de sucesso e a lista de falhas/duplicadas.

## Dependências
- **Database (PDO)**: Acesso direto à tabela `clientes_pf`.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Importação em lote trata duplicidade via `SELECT` para cada linha | `api/handlers/client_pf_handler.php` | 🟡 |
| Separação física entre Cliente PF e Contatos de Organizações B2B | Contexto de arquitetura (módulo `contact` separado de `client_pf`) | 🟢 |

## Estado Interno
Sem estado em memória contínuo. Operações são persistidas imediatamente em MySQL.

## Observabilidade
Sem logs estruturados. Retornos detalhados de erros nas rotinas de validação de duplicidade.

## Riscos e Lacunas
- 🟡 Performance da importação em lote: Fazer um `SELECT` para validar CPF/E-mail a cada iteração do CSV pode gerar N+1 no banco e travar o servidor para lotes grandes.
