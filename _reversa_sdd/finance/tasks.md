# Finance (Empenhos e Notas Fiscais), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Conexão ao banco de dados e schema de `empenhos` e `notas_fiscais` com chaves estrangeiras ativas.
- [ ] Middleware ou utilitário global de sanitização monetária criado (para evitar código repetido).

## Tarefas

- [ ] T-01, Extrair a lógica de formatação de moeda para uma função reutilizável (Helper)
  - Origem no legado: `api/handlers/finance_handler.php:14` (`str_replace` triplo).
  - Critério de pronto: Função recebe string e devolve `float`/`decimal`.
  - Confiança: 🟢

- [ ] T-02, Criar endpoint de Empenho (`POST /?endpoint=finance&action=create_empenho`)
  - Origem no legado: `handle_create_empenho`
  - Critério de pronto: Endpoint aplica a sanitização de valor e insere o registro atrelado ao `oportunidade_id`. Retorna 200.
  - Confiança: 🟢

- [ ] T-03, Criar endpoint de Nota Fiscal (`POST /?endpoint=finance&action=create_nota_fiscal`)
  - Origem no legado: `handle_create_nota_fiscal`
  - Critério de pronto: Endpoint aplica a sanitização de valor e insere a NF. Aceita `empenho_id` opcionalmente.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Testar Helper Monetário injetando "R$ 1.000.500,99", "1000.50", e valores inválidos.
- [ ] TT-02, Testar gravação de Empenho sem `oportunidade_id` (esperar erro 400).
- [ ] TT-03, Testar gravação de Nota Fiscal com e sem `empenho_id` (ambos devem salvar se FK permitir NULL).

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, N/A (Se o schema se mantiver idêntico).

## Ordem Sugerida
1. T-01 (Helper é bloqueante para os endpoints).
2. T-02 e T-03 (Endpoints em paralelo).

## Lacunas Pendentes (🔴)
Nenhuma. O módulo é um CRUD simples com uma regra de parsing embutida.
