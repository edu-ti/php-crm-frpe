# Máquinas de Estado

## Propostas (status)

```mermaid
stateDiagram-v2
    [*] --> Rascunho
    Rascunho --> Enviada
    Enviada --> Aprovada
    Enviada --> Recusada
    Aprovada --> [*]
    Recusada --> [*]
```

*Gatilhos observados:*
- Ao atingir `Aprovada`, gera venda em `vendas_fornecedores`.
- Sincroniza retroativamente a Oportunidade correspondente.

## Oportunidades (funil)

```mermaid
stateDiagram-v2
    [*] --> ContatoInicial
    ContatoInicial --> Qualificação
    Qualificação --> Proposta
    Proposta --> Negociação
    Negociação --> FechadoGanho
    Negociação --> FechadoPerdido
    FechadoGanho --> [*]
    FechadoPerdido --> [*]
```
*(Nota: As etapas exatas podem ser customizadas no banco via `etapas_funil`, mas seguem essa progressão lógica)*
