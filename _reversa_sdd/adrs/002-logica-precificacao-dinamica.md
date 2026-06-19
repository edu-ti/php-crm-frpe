# ADR 002: Lógica de Precificação Dinâmica e Multiplicadores de Locação

## Status
Aceito (Retroativo)

## Contexto
O CRM precisa precificar não apenas a venda pontual de produtos físicos ou licenças isoladas, mas contratos de locação recorrentes com diferentes durações.

## Decisão
Implementar um cálculo de valor total nas propostas/oportunidades que inclua dinamicamente a variável `meses_locacao` multiplicando o valor unitário e a quantidade dos itens. Os metadados de configuração e personalização do item são armazenados em JSON no campo `parametros`.

## Consequências
- **Positivas:** Permite extrema flexibilidade comercial sem criar tabelas adicionais para modalidades de locação ou criar SKUs duplicados para cada faixa de locação.
- **Negativas:** O backend fica responsável por refazer o cálculo recursivamente sempre que um item for alterado, correndo o risco de dessincronização se alguém atualizar o item diretamente no banco.

## Alternativas Consideradas
- **Modelar locações em tabelas separadas (`contratos_locacao`):** Descartado pela complexidade na interface única; o usuário queria gerenciar vendas e locações na mesma Proposta/Oportunidade.
- **Adicionar campos fixos na tabela de produtos:** Descartado porque a quantidade de meses é uma variável da proposta, não do catálogo.
