# Organization (Organizações/Empresas)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo base do CRM para gestão de entidades B2B. Uma "Organização" representa uma empresa cliente, fornecedora ou parceira que possui CNPJ e pode estar atrelada a múltiplos contatos físicos (Pessoas Físicas) ou oportunidades de negócio.

## Responsabilidades
- Cadastrar e manter os dados vitais de uma empresa (Razão Social, Nome Fantasia, CNPJ).
- Garantir a unicidade das empresas através do CNPJ.
- Fornecer os dados da entidade para preenchimento de Propostas, Contratos e Notas Fiscais.

## Regras de Negócio
- **Unicidade de CNPJ**: O sistema não deve permitir o cadastro de duas organizações com o mesmo CNPJ ativo. Se o usuário tentar inserir, a API deve bloquear. 🟢
- **Obrigatoriedade Base**: O nome da empresa (Nome Fantasia) é estritamente obrigatório para sua criação, enquanto o CNPJ pode ser preenchido a posteriori (embora recomendado). 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Cadastro de Organização | Must | A API deve permitir criar uma empresa com no mínimo um "Nome Fantasia". |
| RF-02 | Validação de CNPJ | Must | A API deve bloquear a inserção ou edição se o CNPJ fornecido já pertencer a outra empresa. |
| RF-03 | Atualização de Dados | Should | O usuário pode complementar dados como Inscrição Estadual, Endereço e CNPJ após a criação. |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Simplicidade | CRUD Direto | O módulo é categorizado com baixa complexidade e sem integrações pesadas | 🟢 |

## Critérios de Aceitação

```gherkin
Dado que o CNPJ "12.345.678/0001-90" pertence à empresa "Acme Corp"
Quando um vendedor tenta cadastrar "Acme Filial" com o CNPJ "12.345.678/0001-90"
Então o sistema deve retornar um erro avisando que a empresa já existe

Dado que o vendedor não tem o CNPJ no momento da prospecção
Quando ele cadastra apenas o Nome Fantasia "Tech Solutions"
Então a Organização deve ser criada com sucesso
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Validação de CNPJ | Must | Previne a fragmentação da base de dados e duplicidade de crédito/faturamento no ERP futuro. |
| Permissão de CNPJ nulo | Must | Não travar o fluxo do vendedor no topo de funil quando ele tem apenas o nome da empresa. |

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/organization_handler.php` | `handle_create_organization` | 🟢 |
