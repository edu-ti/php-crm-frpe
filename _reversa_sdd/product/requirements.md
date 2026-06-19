# Product (Catálogo, Tabelas de Preço e Kits)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo responsável pelo gerenciamento de toda a base de ofertas da empresa. Ele não se limita ao cadastro de um "Produto" isolado, mas suporta a orquestração de "Tabelas de Preço" (para diferentes regiões ou negociações) e o agrupamento de itens em "Kits", facilitando vendas em combo.

## Responsabilidades
- Cadastro básico de Produtos (nome, descrição base).
- Gestão de Tabelas de Preço (Mestre-Detalhe), onde os produtos recebem o valor monetário.
- Cadastro e montagem de Kits (combos de produtos).
- Cálculo dinâmico e "snapshot" do valor total de um Kit no momento de sua criação ou edição.

## Regras de Negócio
- **Integridade de Preço**: O produto em si não possui preço. O preço existe na entidade associativa `tabela_preco_itens`. 🟢
- **Proteção Relacional**: Um item de tabela de preço não pode ser excluído do sistema se ele fizer parte da composição de algum Kit ativo. 🟢
- **Snapshot de Valor de Kit**: O valor total de um Kit é a soma dos valores de seus itens no momento de sua montagem. A aplicação faz um snapshot (cópia) desse valor para o registro pai (`kits.valor_total`). Isso blinda o Kit contra flutuações futuras na Tabela de Preço, a menos que o Kit seja explicitamente editado/recalculado. 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | CRUD de Produto Base | Must | O sistema deve permitir cadastrar as informações vitais de um produto, sem atrelar preço. |
| RF-02 | Gestão de Tabelas de Preço | Must | O sistema deve permitir criar "Tabelas" e adicionar itens com seus respectivos valores. |
| RF-03 | Montagem de Kits | Must | O usuário pode criar um Kit inserindo múltiplos "itens de tabela de preço" nele. |
| RF-04 | Cálculo do Valor do Kit | Must | Ao criar/editar um Kit, o backend deve somar o valor unitário dos itens e salvar no pai. |
| RF-05 | Bloqueio de Exclusão Segura | Must | O sistema deve impedir a deleção de um item de tabela de preço se `kit_itens` o referenciar. |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Desempenho | Snapshotting de cálculo | Ao invés de usar View do MySQL para calcular o preço do kit online (que é lento), a aplicação materializa o valor na tabela pai (`kits.valor_total`). | 🟢 |

## Critérios de Aceitação

```gherkin
Dado que o "Monitor" na "Tabela Padrão" custa R$ 500
Quando eu monto o "Kit Home Office" contendo 2x Monitores
Então o valor total gravado do Kit deve ser R$ 1.000

Dado o "Kit Home Office" já montado com o Monitor
E o gerente de produtos apaga o "Monitor" da "Tabela Padrão"
Então o sistema deve exibir erro: "Item não pode ser excluído pois pertence a um Kit."
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Tabela de Preços e Itens | Must | Necessário para alimentar o módulo de `opportunity` com valores válidos. |
| Proteção Relacional (Kits) | Must | Evita que o sistema de faturamento quebre ao tentar ler um Kit cujo item base "sumiu". |

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/product_handler.php` | `handle_create_product`, `handle_create_price_table`, `handle_create_kit` e validações nas linhas `273` e `330` | 🟢 |
