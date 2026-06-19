# Organization (Organizações/Empresas), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=organization&action=create` | JSON (`nome_fantasia`, `cnpj`, etc) | JSON `{"success": true, "id": X}` | 200, 400 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_organization` | `($pdo, $data)` | `JSON` via `json_response` | Valida unicidade e insere no BD. |

## Fluxo Principal
1. O roteador (`api.php`) encaminha a requisição POST para `handle_create_organization`.
2. O payload JSON (`$data`) é processado.
3. **Validação de Unicidade**: Se o campo `cnpj` foi enviado e não for vazio, o sistema executa um `SELECT COUNT(*) FROM organizacoes WHERE cnpj = ?`.
   - Se o contador for > 0, aborta retornando HTTP 400 com erro.
4. Caso passe, a instrução `INSERT INTO organizacoes` é montada via PDO com bind de parâmetros.
5. O registro é salvo e o ID gerado (`$pdo->lastInsertId()`) é retornado no JSON.

## Fluxos Alternativos
- **Sem CNPJ**: Se a payload vier apenas com o Nome Fantasia, a etapa de validação de CNPJ é pulada (o WHERE do Select não é disparado), inserindo a empresa como "prospect".

## Dependências
- **Database (PDO)**: Leitura e escrita na tabela `organizacoes`. Nenhuma chave estrangeira acionada nesta unit.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Bloqueio de duplicidade validado na camada de aplicação (PHP `SELECT COUNT`) antes do INSERT | `api/handlers/organization_handler.php:12` | 🟢 |
| Inexistência de validação algorítmica de CNPJ (Módulo 11) no backend | Legado não aplica fórmula de validação real | 🟡 |

## Estado Interno
Módulo estritamente Stateless (CRUD Simples).

## Observabilidade
Nenhum log especial identificado além do retorno de erro padrão do PHP.

## Riscos e Lacunas
- 🟡 Concorrência de Inserção (Race Condition): Como o bloqueio é feito via `SELECT` seguido de `INSERT` fora de uma transação ou lock, dois requests simultâneos em milissegundos com o mesmo CNPJ podem burlar o PHP e quebrar a constraint de banco (se houver UNIQUE KEY) causando um Fatal Error PDO não tratado.
