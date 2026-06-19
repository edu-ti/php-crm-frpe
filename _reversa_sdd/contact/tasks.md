# Contact (Contatos B2B), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Schema/migrations da tabela `contatos` criados.
- [ ] Tabela de `organizacoes` deve existir (se houver Foreign Key ativada no banco).

## Tarefas

- [ ] T-01, Criar endpoint base para criação de contatos corporativos (`POST /?endpoint=contact&action=create`)
  - Origem no legado: `api/handlers/contact_handler.php:1`
  - Critério de pronto: Endpoint aceita payload JSON e faz a validação básica.
  - Confiança: 🟢

- [ ] T-02, Implementar validação de unicidade de E-mail
  - Origem no legado: `api/handlers/contact_handler.php:12`
  - Critério de pronto: Se o campo e-mail for enviado, realizar consulta prévia no banco para barrar e-mails duplicados.
  - Confiança: 🟢

- [ ] T-03, Implementar inserção e vínculo com Organização
  - Origem no legado: `api/handlers/contact_handler.php`
  - Critério de pronto: Gravar os dados na tabela `contatos`, persistindo o `organizacao_id` fornecido, e retornar sucesso com o novo ID.
  - Confiança: 🟢

## Tarefas de Teste

- [ ] TT-01, Testar happy path criando contato sem e-mail (caso permitido) ou com e-mail único.
- [ ] TT-02, Testar a criação de um contato usando um e-mail que já existe na base, esperando recusa (400 Bad Request).
- [ ] TT-03, Testar vínculo de organização (fornecer um ID inválido deve quebrar no banco se FK for estrita).

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, N/A (Migração de dados B2B deve respeitar a ordem: primeiro organizar a tabela `organizacoes`, e em seguida importar os `contatos` para manter a integridade referencial).

## Ordem Sugerida
1. T-01 (Setup do endpoint e entrada de dados)
2. T-02 (Validação de regra de negócio crítica, antes de salvar)
3. T-03 (Efetivação no banco)

## Lacunas Pendentes (🔴)
Confirmar se um Contato pode existir de forma órfã (sem `organizacao_id`), ou se o frontend sempre exige o vínculo. A depender disso, a validação no T-01 deve ser mais rígida.
