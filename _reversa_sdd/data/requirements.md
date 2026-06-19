# Data (Consultas Centralizadas e Dashboard)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo de leitura centralizado responsável por alimentar as interfaces de frontend (tabelas e dashboards) com dados cruzados e KPIs filtrados por permissão.

## Responsabilidades
- Fornecer coleções de dados mestres (oportunidades, usuários, propostas, fornecedores).
- Calcular estatísticas e KPIs para dashboards (ex: taxas de conversão, volume de vendas).
- Aplicar o "Scoped Data", garantindo que usuários vejam apenas os dados compatíveis com seu nível de acesso.

## Regras de Negócio
- A visibilidade de oportunidades e agendamentos deve ser filtrada conforme o cargo (`role`) do usuário autenticado (RBAC). 🟢
- Estatísticas de vendas só devem considerar propostas aprovadas ou oportunidades ganhas. 🟡

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Listagem de Dados Mestres | Must | O sistema deve expor endpoints agregados para o carregamento inicial da UI |
| RF-02 | Cálculo de KPIs | Must | O sistema deve calcular estatísticas agregadas (Somas e Contagens) de propostas/oportunidades |
| RF-03 | Filtragem Baseada em Permissão | Must | Vendedores veem apenas seus dados; Gerentes/Admins veem tudo |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Performance | Consultas complexas envolvendo múltiplas entidades | Dependência de `usuarios`, `oportunidades`, `propostas`, `fornecedores` | 🟡 |
| Segurança | Controle de Escopo (Data Scoping) | `api/handlers/data_handler.php:21` | 🟢 |

> Inferido a partir do código. Validar com equipe de operações.

## Critérios de Aceitação

```gherkin
Dado que o usuário logado tem o perfil "Vendedor"
Quando ele solicita a lista de oportunidades (get_data)
Então o sistema deve retornar apenas as oportunidades atribuídas a ele

Dado um dashboard carregando os KPIs do mês
Quando o endpoint de estatísticas (get_stats) é acionado
Então ele deve calcular a taxa de conversão baseada apenas nas oportunidades "Ganha"
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Filtragem RBAC nos Dados | Must | Crítico para segurança corporativa (Data Isolation) |
| Listagens Múltiplas Agregadas | Should | Reduz roundtrips do frontend (carrega tudo num endpoint), mas poderia ser dividido |
| KPIs em Tempo Real | Should | Fundamental para gestão comercial |

> Prioridade inferida por frequência de chamada e posição na cadeia de dependências.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/data_handler.php` | `handle_get_data`, `handle_get_stats` | 🟢 |
