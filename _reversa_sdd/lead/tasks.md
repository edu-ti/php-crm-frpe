# Lead (Prospecção e Qualificação), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema de `clientes_pf`, `oportunidades` e `leads` (com seus respectivos Repositories/Models criados).
- [ ] Conhecimento da lógica transacional (ex: `$pdo->beginTransaction()`).

## Tarefas

- [ ] T-01, Criar endpoint de Importação em Lote (`POST /?endpoint=lead&action=import_leads`)
  - Origem no legado: `handle_import_leads`
  - Critério de pronto: O endpoint deve ler um CSV, iterar linha a linha, validar se email/telefone já existe no banco e inserir apenas os novos. Retornar contagem de sucesso/ignorados.
  - Confiança: 🟢

- [ ] T-02, Criar utilitário Gerador de Número de Proposta
  - Origem no legado: Lógica de contagem em `handle_convert_lead_to_pre_proposal`
  - Critério de pronto: Função independente que bloqueie a tabela (ou use transação/serializable) para gerar o próximo número "ANO/SEQ" garantindo unicidade.
  - Confiança: 🟡 (Necessita de refatoração para evitar Race Condition).

- [ ] T-03, Criar endpoint de Conversão de Lead (`POST /?endpoint=lead&action=convert_lead_to_pre_proposal`)
  - Origem no legado: `handle_convert_lead_to_pre_proposal`
  - Critério de pronto: Endpoint deve buscar o Lead. Se não houver Cliente com mesmo email, cria o Cliente. Gera número da proposta, cria Oportunidade vinculada ao Cliente, e marca o Lead original como inativo. Tudo encapsulado em Transação.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Teste unitário do Gerador de Número de Proposta (simulando acessos concorrentes se possível).
- [ ] TT-02, Teste de Integração (E2E) da Importação: Injetar CSV com 5 linhas, sendo 2 já existentes. O resultado no DB deve ser de apenas 3 novos leads.
- [ ] TT-03, Teste de Integração (E2E) da Conversão: Converter um lead, forçar falha no `INSERT` da Oportunidade e checar se o Cliente foi criado (não deve ser, o Rollback deve atuar).

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, Garantir que os Leads legados possuam uma coluna indicando se já foram convertidos (para histórico).

## Ordem Sugerida
1. T-02 (Gerador de número é requisito para a conversão).
2. T-03 (Conversão de Lead - core business).
3. T-01 (Importação - entrada de dados).

## Lacunas Pendentes (🔴)
- Estratégia de Lock para o `numero_proposta`. No legado é provável que cause colisão em alto uso. O novo código precisa decidir se usa Lock Otimista, Pessimista ou Sequence do Banco de Dados.
