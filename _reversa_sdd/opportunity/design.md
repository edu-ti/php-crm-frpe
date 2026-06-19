# Opportunity (Gestão de Oportunidades e Funil), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=opportunity&action=create` | JSON (`cliente_id`, `etapa_id`, `itens[]`, etc) | JSON `{"success": true, "id": X}` | 200, 400 |
| PUT | `/?endpoint=opportunity&action=update` | JSON (`id`, `itens[]`, etc) | JSON `{"success": true}` | 200, 400 |
| PATCH | `/?endpoint=opportunity&action=move` | JSON (`id`, `nova_etapa_id`) | JSON `{"success": true}` | 200, 400, 403 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_opportunity` | `($pdo, $data)` | `JSON` | Cria registro base, insere itens e calcula total. |
| `handle_update_opportunity` | `($pdo, $data)` | `JSON` | Deleta itens antigos, insere novos e recalcula total. |
| `handle_move_opportunity` | `($pdo, $data)` | `JSON` | Aplica os Gatekeepers e altera `etapa_id`. |

## Fluxo Principal (Recálculo de Valor)
Sempre que uma Oportunidade é criada ou os itens são atualizados (via `handle_create_opportunity` ou `handle_update_opportunity`):
1. O backend recebe o array de `itens` (produtos) do frontend.
2. Inicia transação PDO.
3. Se for Update, todos os registros em `oportunidade_itens` vinculados ao ID da Oportunidade são deletados (`DELETE FROM oportunidade_itens WHERE oportunidade_id = ?`).
4. Para cada item do array, o sistema extrai: `produto_id`, `quantidade`, `meses_locacao` e converte o campo `parametros_json` em array para achar os custos extras.
5. O cálculo individual é feito no backend: `(valor_base_produto + parametros) * quantidade * meses_locacao`.
6. O item é inserido no banco (`INSERT INTO oportunidade_itens`).
7. O total acumulado da oportunidade é atualizado no registro pai (`UPDATE oportunidades SET valor = ? WHERE id = ?`).
8. A transação é comitada.

## Fluxo de Transição (Movimentação no Funil)
Em `handle_move_opportunity`:
1. Recebe a requisição com o `id` da oportunidade e o `novo_etapa_id`.
2. Verifica em `etapas_funil` qual é a categoria dessa nova etapa (ex: se é tipo "Negociação" ou "Ganho").
3. **Gatekeeper (Negociação)**: Se a etapa alvo for "Negociação", faz um `SELECT COUNT(*) FROM propostas WHERE oportunidade_id = ?`. Se for 0, lança erro.
4. **Gatekeeper (Ganho)**: Se a etapa alvo for "Ganho", faz `SELECT COUNT(*) FROM propostas WHERE oportunidade_id = ? AND status = 'Aprovada'`. Se for 0, lança erro.
5. Se passar, atualiza o `etapa_id` da oportunidade.
6. Grava log em `oportunidade_historico`.

## Dependências
- **Database (PDO)**: Tabelas `oportunidades`, `oportunidade_itens`, `oportunidade_historico`, `etapas_funil`, `propostas`.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Update destrutivo de Itens (Delete All -> Insert New) ao invés de diff (upsert) para facilitar o recálculo total. | `handle_update_opportunity` | 🟢 |
| Hardcode ou dependência forte de "Nomes/Tipos de Etapa" para aplicar os gatekeepers. | `handle_move_opportunity` | 🟡 |

## Estado Interno
Sem estado em memória (Stateless API), regras baseadas integralmente em consultas ao BD.

## Observabilidade
Gravação no BD de um histórico formal em `oportunidade_historico` com `data_mudanca`, `etapa_anterior` e `etapa_nova`.

## Riscos e Lacunas
- 🟡 Performance no Update: O padrão Delete/Insert para atualizar os itens é fácil de implementar, mas zera IDs das PKs dos itens constantemente. Se houver dependências atreladas ao ID de um `oportunidade_item` específico, elas quebrarão.
- 🔴 Consistência do Catálogo: Se o preço de um produto mudar no catálogo mestre, o recálculo de uma oportunidade antiga que for apenas atualizada assumirá o preço novo, o que pode alterar o valor de uma proposta já enviada.
