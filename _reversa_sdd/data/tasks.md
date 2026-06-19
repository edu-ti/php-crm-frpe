# Data (Consultas Centralizadas e Dashboard), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Conexão ao banco de dados PDO.
- [ ] Modelos/Tabelas base (`oportunidades`, `agendamentos`, `usuarios`, `propostas`, `fornecedores`) existentes e populadas.
- [ ] Sessão preenchida pelo módulo `auth` simulada.

## Tarefas

- [ ] T-01, Criar endpoint base de `get_data` (`GET /?endpoint=data&action=get_data`)
  - Origem no legado: `api/handlers/data_handler.php`
  - Critério de pronto: Endpoint inicializado recebendo a requisição.
  - Confiança: 🟢

- [ ] T-02, Implementar mecanismo de Data Scoping baseado em Sessão (RBAC)
  - Origem no legado: `api/handlers/data_handler.php:21`
  - Critério de pronto: Identificar se o usuário logado é "Vendedor" ou "Admin". Montar a cláusula `WHERE usuario_id = :id` ou ignorar o filtro para superusuários.
  - Confiança: 🟢

- [ ] T-03, Executar as múltiplas queries e empacotar a resposta do `get_data`
  - Origem no legado: `api/handlers/data_handler.php`
  - Critério de pronto: Retornar um único JSON com arrays indexados para `oportunidades`, `usuarios`, etc.
  - Confiança: 🟢

- [ ] T-04, Criar endpoint de `get_stats` (`GET /?endpoint=data&action=get_stats`)
  - Origem no legado: `api/handlers/data_handler.php`
  - Critério de pronto: Executar agregações (Somas e Counts) na base, respeitando o Data Scoping de permissão.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Testar endpoint `get_data` com usuário "Admin" (espera ver dados de toda a base).
- [ ] TT-02, Testar endpoint `get_data` com usuário "Vendedor" (espera ver APENAS os dados atrelados ao seu próprio ID).
- [ ] TT-03, Testar `get_stats` garantindo que a taxa de conversão calcule corretamente com base em propostas/oportunidades "Ganhas".

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, N/A (Módulo apenas de leitura, não possui migração de estrutura própria).

## Ordem Sugerida
1. T-01 e T-02 (A lógica central de RBAC dita a arquitetura das queries).
2. T-03 (Escrever e otimizar os comandos SELECT).
3. T-04 (Replicar a lógica de RBAC para os cálculos de métricas).

## Lacunas Pendentes (🔴)
É viável manter o padrão de "Over-fetching" na migração? No legado, retorna tudo. Se a base for grande, a arquitetura moderna exigiria endpoints separados ou GraphQL. A reimplementação precisa de uma decisão se quebra em paginação ou não.
