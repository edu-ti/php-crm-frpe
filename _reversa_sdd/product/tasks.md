# Product (Catálogo, Tabelas de Preço e Kits), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema de `produtos`, `tabela_preco`, `tabela_preco_itens`, `kits` e `kit_itens` criados e relacionados.

## Tarefas

- [ ] T-01, Criar endpoint CRUD Básico de Produto (`POST /?endpoint=product&action=create`)
  - Origem no legado: `handle_create_product`
  - Critério de pronto: Endpoint que recebe nome e insere na tabela `produtos`. Retorna 200.
  - Confiança: 🟢

- [ ] T-02, Criar endpoint de Tabela de Preços (`POST /?endpoint=product&action=create_price_table`)
  - Origem no legado: `handle_create_price_table`
  - Critério de pronto: Insere o header da tabela e em seguida insere os itens (relacionando `produto_id` e seu `valor_unitario`).
  - Confiança: 🟢

- [ ] T-03, Criar Validador de Exclusão de Tabela de Preço
  - Origem no legado: Validação soft na linha 273.
  - Critério de pronto: Repository Method `isItemInAnyKit($tabelaPrecoItemId)` que faz o COUNT na tabela `kit_itens` e retorna booleano.
  - Confiança: 🟢

- [ ] T-04, Criar endpoint de Exclusão Segura de Preço (`DELETE /?endpoint=product&action=delete_price_item`)
  - Origem no legado: `handle_delete_price_item`
  - Critério de pronto: Tenta excluir um preço. Se a T-03 retornar true, bloqueia com HTTP 403. Caso contrário, deleta.
  - Confiança: 🟢

- [ ] T-05, Criar Motor de Cálculo e Snapshot de Kit
  - Origem no legado: Lógica iterativa da linha 330.
  - Critério de pronto: Função isolada que recebe um array de `tabela_preco_id`, soma os valores extraídos do banco de dados e retorna a somatória. Pode usar `SUM` do SQL para otimização em vez de loop do PHP.
  - Confiança: 🟡 (Oportunidade de refatorar de N+1 queries para 1 query com `WHERE IN`).

- [ ] T-06, Criar endpoint de Montagem de Kit (`POST /?endpoint=product&action=create_kit`)
  - Origem no legado: `handle_create_kit`
  - Critério de pronto: Cria o kit com os itens informados, roda a T-05 e salva o snapshot do valor total.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Teste E2E (T-04): Tentar excluir um item de preço que pertença a um Kit. Deve falhar com 403.
- [ ] TT-02, Teste E2E (T-05/T-06): Criar um Kit com 3 itens. Alterar o preço de um dos itens. O valor total do Kit no banco **não** deve mudar (garantia de snapshot).
- [ ] TT-03, Teste Unitário (T-05): Mockar o repositório de preços e testar a somatória.

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, N/A (Kits legados devem manter o valor congelado na coluna `valor_total` original).

## Ordem Sugerida
1. T-01 e T-02 (Produtos Base e Preços são os building blocks).
2. T-03 e T-04 (Proteção dos Preços).
3. T-05 e T-06 (Montagem e Valoração de Kits).

## Lacunas Pendentes (🔴)
Evolução sugerida: O model de Tabelas de Preço não lida com "Validade" (Data Inicial e Data Final), o que costuma ser um anti-pattern em ERPs. Sugere-se adicionar essas colunas se possível na reescrita.
