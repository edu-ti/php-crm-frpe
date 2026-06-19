# Organization (Organizações/Empresas), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Tabela `organizacoes` criada no banco com coluna `cnpj` preferencialmente configurada como `UNIQUE` (se o SGDB permitir nulls múltiplos em campos únicos, dependendo da engine).

## Tarefas

- [ ] T-01, Criar Validador de Unicidade de CNPJ
  - Origem no legado: `api/handlers/organization_handler.php:12`
  - Critério de pronto: Função ou Repository Method genérico `existsByCnpj($cnpj)` que retorna booleano.
  - Confiança: 🟢

- [ ] T-02, Criar Endpoint de Criação (`POST /?endpoint=organization&action=create`)
  - Origem no legado: `handle_create_organization`
  - Critério de pronto: O endpoint deve invocar a T-01 se o CNPJ vier preenchido. Se não existir, faz a inserção e retorna o ID da nova empresa.
  - Confiança: 🟢

- [ ] T-03, (Recomendação) Criar Validador Lógico de CNPJ
  - Origem no legado: Inexistente (Lacuna).
  - Critério de pronto: Função pura que aplique a fórmula do Módulo 11 para impedir a gravação de lixo na base de dados, como "11.111.111/1111-11".
  - Confiança: 🟡 (Depende de aprovação do PO, pois muda comportamento do legado).

## Tarefas de Teste

- [ ] TT-01, Teste Unitário (T-03): Testar validador de CNPJ com 5 números válidos e 5 inválidos.
- [ ] TT-02, Teste de Integração (T-02): Tentar criar empresa sem CNPJ (deve passar).
- [ ] TT-03, Teste de Integração (T-02): Criar empresa com CNPJ X. Em seguida, tentar criar outra empresa com CNPJ X (deve retornar HTTP 400).

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, Limpar a base legada de organizações com CNPJs duplicados (se existirem por bypasses passados) antes de aplicar a nova Unique Constraint no schema moderno.

## Ordem Sugerida
1. T-03 (Se aprovado) e T-01.
2. T-02.

## Lacunas Pendentes (🔴)
Nenhuma além da falta de validação algorítmica do CNPJ.
