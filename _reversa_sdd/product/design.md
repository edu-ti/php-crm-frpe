# Product (Catálogo, Tabelas de Preço e Kits), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=product&action=create` | JSON (`nome_produto`, `descricao`) | JSON `{"success": true, "id": X}` | 200, 400 |
| POST | `/?endpoint=product&action=create_price_table` | JSON (`codigo`, `itens[{produto_id, valor_unitario}]`) | JSON `{"success": true, "id": X}` | 200, 400 |
| POST | `/?endpoint=product&action=create_kit` | JSON (`nome_kit`, `itens[{tabela_preco_id}]`) | JSON `{"success": true, "id": X, "valor_total": Y}` | 200, 400 |
| DELETE | `/?endpoint=product&action=delete_price_item` | JSON (`tabela_preco_id`) | JSON `{"success": true}` | 200, 403, 404 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_product` | `($pdo, $data)` | `JSON` | Insere produto mestre. |
| `handle_create_price_table`| `($pdo, $data)` | `JSON` | Cria tabela e insere itens (tabela_preco_itens). |
| `handle_create_kit` | `($pdo, $data)` | `JSON` | Agrupa itens, soma valores e salva o kit. |

## Fluxo Principal (Criação de Kit e Snapshot)
1. O Front Controller roteia para `handle_create_kit`.
2. A aplicação recebe o array de IDs da tabela de preço que compõem o kit (`itens[]`).
3. Inicia transação PDO.
4. Insere o registro mestre na tabela `kits` (com `valor_total` provisoriamente em 0).
5. O sistema faz um loop no array de itens fornecido.
6. Para cada `tabela_preco_id`, o PHP executa um `SELECT valor_unitario FROM tabela_preco_itens WHERE id = ?`.
7. O PHP acumula o valor retornado em uma variável `$valorTotal`.
8. O sistema vincula o item ao kit (`INSERT INTO kit_itens (kit_id, tabela_preco_id)`).
9. Após o loop, o PHP faz um `UPDATE kits SET valor_total = ? WHERE id = ?` com a soma calculada.
10. A transação é comitada.

## Fluxo Alternativo (Deleção de Item de Preço)
1. Rota para `handle_delete_price_item`.
2. Faz validação cruzada (Gatekeeper Relacional): `SELECT COUNT(*) FROM kit_itens WHERE tabela_preco_id = ?`.
3. Se o COUNT > 0, lança exceção ou erro HTTP 403.
4. Se o COUNT = 0, faz o `DELETE FROM tabela_preco_itens`.

## Dependências
- **Database (PDO)**: Tabelas `produtos`, `tabela_preco`, `tabela_preco_itens`, `kits`, `kit_itens`.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Valores monetários ficam na tabela associativa (`tabela_preco_itens`) e não no produto, permitindo N preços para 1 produto. | `handle_create_price_table` | 🟢 |
| Cálculo de custo do kit feito de forma iterativa (N+1 queries) no PHP, em vez de `INSERT INTO ... SELECT SUM()` puramente no SQL. | `api/handlers/product_handler.php:330` | 🟢 |
| Validação manual de Foreign Key antes do Delete (soft validation) em vez de deixar o banco explodir a constraint FK. | `api/handlers/product_handler.php:273` | 🟢 |

## Estado Interno
Stateless. A integridade depende exclusivamente da transação de banco.

## Observabilidade
Nenhuma evidente. Recomenda-se logar alterações de preços por auditoria (não existente no legado).

## Riscos e Lacunas
- 🟡 Performance no Cadastro de Kits: O padrão N+1 queries no loop para buscar o valor unitário de cada item (passo 6) pode ser ineficiente para kits com centenas de itens (raro, mas possível).
- 🔴 Inexistência de versionamento em Tabelas de Preço: Se o usuário editar a Tabela de Preços ao invés de criar uma nova com "Vigência 2027", as oportunidades abertas baseadas na tabela antiga sofrerão mutação indesejada se não forem "Kits" (já que o Kit tem snapshot, mas a Oportunidade não garante snapshot do Unitário se recalculada).
