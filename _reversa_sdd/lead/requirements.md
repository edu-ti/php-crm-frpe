# Lead (Prospecção e Qualificação)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo central responsável pela entrada de contatos no topo do funil (Leads). Permite a importação em lote, atualização de status de prospecção e a conversão de um Lead qualificado em uma "Pré-proposta" (Oportunidade) atrelada a um Cliente real.

## Responsabilidades
- Importar Leads em lote via CSV, tratando duplicidades.
- Atualizar status/etapa do lead na fase de prospecção.
- Converter Lead em Oportunidade (Pré-proposta).
- Inserir Cliente (PF) automaticamente durante a conversão, caso ele ainda não exista na base.

## Regras de Negócio
- Durante a importação de leads, o sistema deve ignorar registros que já existam com o mesmo e-mail ou telefone. 🟢
- A conversão de Lead exige a transição formal para "Oportunidade" no funil de vendas. 🟢
- Ao converter, se o Lead não estiver associado a um `cliente_pf` existente (buscando por email/telefone), o sistema deve criá-lo automaticamente com os dados do Lead. 🟢
- A pré-proposta gerada deve receber uma numeração sequencial identificável, como `ANO/ID` (ex: 2026/001). 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Importação de Leads | Must | O sistema deve aceitar arquivos CSV e inserir leads em massa sem duplicar e-mails/telefones. |
| RF-02 | Conversão Lead para Oportunidade | Must | O sistema deve transformar os dados do Lead em uma nova Oportunidade vinculada ao funil. |
| RF-03 | Auto-Criação de Cliente | Must | Na conversão, criar um `cliente_pf` se não existir match de contato. |
| RF-04 | Geração de Número de Proposta | Must | A oportunidade gerada deve ter um campo `numero_proposta` autogerado (Ano/Sequencial). |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Desempenho | Upload assíncrono para grandes lotes | Algoritmo de "Importação em lote" referenciado | 🟡 |
| Integridade Transacional | Criação de Cliente + Oportunidade | A conversão toca em múltiplas tabelas simultaneamente | 🟢 |

> Inferido a partir do código. A conversão de lead toca em `clientes_pf`, `oportunidades` e deleta/inativa o `lead`. Transações (DB Transactions) são estritamente necessárias.

## Critérios de Aceitação

```gherkin
Dado um Lead qualificado chamado "João" com o email "joao@email.com"
Quando o vendedor clica em "Converter para Pré-proposta"
E não existe cliente cadastrado com "joao@email.com"
Então o sistema deve criar o cliente "João"
E criar uma Oportunidade "2026/045" vinculada a este novo cliente

Dado um CSV com 10 leads, onde 3 já estão no banco de dados (mesmo email)
Quando processar a importação
Então apenas 7 novos leads devem ser inseridos e o log deve apontar 3 ignorados
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Conversão e Auto-criação de Cliente | Must | É a ponte vital entre a Prospecção (Marketing) e Vendas (CRM) |
| Gerador de Número Sequencial | Must | Exigência contratual/operacional do modelo de negócio (pré-propostas oficiais) |
| Importação de Lote | Must | Alimenta o topo de funil a partir de planilhas de marketing |

> Prioridade inferida pela alta dependência de `oportunidades` e `clientes_pf`.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/lead_handler.php` | `handle_convert_lead_to_pre_proposal`, `handle_import_leads` | 🟢 |
