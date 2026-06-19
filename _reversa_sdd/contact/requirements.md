# Contact (Contatos B2B)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Gerenciamento de contatos (Pessoas Físicas) atrelados a Organizações (B2B), mantendo o relacionamento entre a empresa cliente e seus pontos de contato.

## Responsabilidades
- Criar, ler, atualizar e deletar registros de contatos corporativos.
- Manter o vínculo obrigatório (ou opcional, dependendo do design) com uma Organização.
- Garantir que não existam contatos com e-mails duplicados na base.

## Regras de Negócio
- E-mails de contatos não podem ser duplicados na base. 🟢
- O contato deve estar associado a uma organização válida (relacionamento B2B). 🟡

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | CRUD de Contato B2B | Must | O sistema deve permitir gerenciar contatos atrelados a organizações |
| RF-02 | Validação de Unicidade | Must | O sistema deve rejeitar a criação se o e-mail já existir |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Integridade | Relacionamento forte com tabela `organizacoes` | `dependencies: ["organizacoes"]` | 🟢 |

> Inferido a partir do código. Validar com equipe de operações.

## Critérios de Aceitação

```gherkin
Dado um E-mail já utilizado por outro contato
Quando o usuário tenta criar um novo contato com este E-mail
Então o sistema deve retornar um erro de validação

Dado os dados válidos de um contato com ID de Organização
Quando o contato é salvo
Então ele é associado à organização correspondente
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| CRUD Básico | Must | Essencial para gerenciar os pontos focais nas contas B2B |
| Unicidade de Email | Must | Previne inconsistências na base |

> Prioridade inferida por frequência de chamada e posição na cadeia de dependências.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/contact_handler.php` | `handle_create_contact` | 🟢 |
