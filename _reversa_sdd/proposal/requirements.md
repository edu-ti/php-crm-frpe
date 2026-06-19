# Proposal (Propostas Comerciais e Sincronização)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo central para fechamento de negócios. Ele herda os dados da Oportunidade, mas representa o documento formal enviado ao cliente. Este módulo possui a maior densidade de regras de negócio de integração, atuando como gatilho (trigger) para avançar a Oportunidade no funil e provisionar a área de "Vendas Fornecedores" (Financeiro/Operacional) quando a proposta é ganha.

## Responsabilidades
- Criar e editar Propostas associadas a Oportunidades.
- Sincronizar (Reverse-Sync) automaticamente a Oportunidade mãe caso o status da Proposta mude (ex: Proposta Enviada -> Oportunidade em Negociação).
- Provisionar os registros operacionais para faturamento (`vendas_fornecedores`) quando o cliente aceita a proposta.
- Garantir isolamento de dados (Vendedores só editam suas próprias propostas).

## Regras de Negócio
- **Reverse-Sync de Funil**: Mudar o status de uma Proposta (ex: "Aprovada") força a Oportunidade mãe a avançar para a etapa equivalente (ex: "Ganho"). 🟢
- **Provisionamento Automático (Handoff)**: Quando uma proposta passa para o status "Aprovada", o sistema deve injetar os dados automaticamente na tabela `vendas_fornecedores`, preparando o terreno para o módulo financeiro. 🟢
- **Isolamento de Tenant (RBAC Nível Linha)**: Um usuário com perfil de "Vendedor" só pode editar ou excluir propostas cujo `usuario_id` seja o dele próprio. Perfis superiores podem editar todas. 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Geração de Proposta | Must | O sistema deve permitir criar uma Proposta espelhando ou ajustando os itens da Oportunidade. |
| RF-02 | Atualização de Status | Must | O usuário pode marcar a proposta como "Enviada", "Aprovada" ou "Recusada". |
| RF-03 | Sincronização Bidirecional | Must | A atualização de status (RF-02) deve refletir na etapa do funil da Oportunidade relacionada. |
| RF-04 | Handoff Operacional | Must | Se RF-02 = "Aprovada", o sistema cria a venda no módulo de fornecedores. |
| RF-05 | Bloqueio de Edição (Vendedores) | Must | O sistema bloqueia a edição/exclusão se o ID do vendedor não bater com o criador. |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Acoplamento | Alta Dependência Transacional | O aceite da proposta mexe em Proposta, Oportunidade e Vendas. Requer Atomicidade. | 🟢 |

## Critérios de Aceitação

```gherkin
Dado que a Oportunidade #100 está na etapa "Qualificação"
E o vendedor emite a Proposta #50 e altera o status para "Enviada"
Quando o sistema processar a requisição
Então a Proposta deve ficar "Enviada"
E a Oportunidade #100 deve ser movida automaticamente para a etapa "Negociação"

Dado que a Proposta #50 muda para "Aprovada"
Quando o sistema processar a requisição
Então a Oportunidade #100 deve ir para "Ganho"
E um registro deve nascer na tabela `vendas_fornecedores` contendo os dados do cliente e valor.
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Sincronização de Funil | Must | O CRM fica dessincronizado se o vendedor aprovar a proposta e a oportunidade continuar em "Qualificação". |
| Handoff Operacional | Must | É o motor financeiro da empresa. Sem isso, o backoffice não fatura. |

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/proposal_handler.php` | `handle_create_proposal`, `handle_update_proposal`, `sync_opportunity_stage`, `create_vendas_fornecedores_from_proposal` | 🟢 |
