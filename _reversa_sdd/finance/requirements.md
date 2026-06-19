# Finance (Empenhos e Notas Fiscais)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo financeiro do CRM responsável por registrar a formalização de faturamento do setor público e privado: o recebimento de Empenhos (promessa de pagamento) e a posterior emissão/registro de Notas Fiscais atreladas a uma oportunidade ganha.

## Responsabilidades
- Criar e gerenciar "Empenhos" associados a uma "Oportunidade".
- Criar e gerenciar "Notas Fiscais", opcionalmente atreladas a um Empenho.
- Tratar e padronizar valores monetários inseridos pelo usuário antes da persistência.

## Regras de Negócio
- Valores monetários (R$) informados no frontend com máscara devem ser limpos e convertidos para o formato numérico padrão (decimal) antes de salvar no banco. 🟢
- Todo empenho deve estar obrigatoriamente ligado a uma `oportunidade` existente. 🟢
- Uma Nota Fiscal pode (ou não) estar vinculada a um `empenho` prévio. 🟡

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Cadastro de Empenho | Must | O sistema deve permitir o registro de um empenho referenciando uma Oportunidade |
| RF-02 | Cadastro de Nota Fiscal | Must | O sistema deve permitir o registro de NFs |
| RF-03 | Sanitização Monetária | Must | O sistema deve converter strings como "R$ 1.500,00" para `1500.00` |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Integridade | Relacionamento forte com `oportunidades` | `dependencies: ["oportunidades"]` | 🟢 |

> Inferido a partir do código. Validar com equipe de operações.

## Critérios de Aceitação

```gherkin
Dado que o usuário insere o valor do empenho como "R$ 2.345,67"
Quando o registro é salvo na base
Então o banco de dados deve armazenar o valor decimal `2345.67`

Dado um ID de oportunidade válido
Quando um novo empenho é criado para essa oportunidade
Então ele aparece no histórico financeiro do negócio
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Registro de Empenhos | Must | Vital para faturamento no setor público (B2G) |
| Registro de Notas Fiscais | Must | Comprovante de faturamento |
| Sanitização de Moeda no Backend | Must | Previne erro de `Type Mismatch` no banco de dados e cálculos errados |

> Prioridade inferida por frequência de chamada e posição na cadeia de dependências.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/finance_handler.php` | `handle_create_empenho`, `handle_create_nota_fiscal` | 🟢 |
