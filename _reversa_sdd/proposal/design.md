# Proposal (Propostas Comerciais e Sincronização), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=proposal&action=create` | JSON (`oportunidade_id`, `itens`, `valor_total`) | JSON `{"success": true, "id": X}` | 200, 400 |
| PUT | `/?endpoint=proposal&action=update_status` | JSON (`id`, `novo_status`) | JSON `{"success": true}` | 200, 403 (RBAC) |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_proposal` | `($pdo, $data)` | `JSON` | Cria a proposta herdando a Oportunidade. |
| `handle_update_proposal` | `($pdo, $data)` | `JSON` | Atualiza status e trigga os side-effects. |
| `sync_opportunity_stage` | `($pdo, $oportunidade_id, $status_proposta)` | `void` | Busca dinâmica de etapa via configuração de funil. |
| `create_vendas_fornecedores_from_proposal` | `($pdo, $proposta_id, $organizacao_id, $cliente_pf_id)` | `void` | Insere no financeiro. |

## Fluxo Principal (Atualização de Status)
1. Controller roteia para `handle_update_proposal`.
2. **Gatekeeper (RBAC)**: O sistema busca a proposta e compara o `usuario_id` com a sessão logada (se for Vendedor). Se diferente, aborta com 403.
3. Inicia transação de banco de dados (`$pdo->beginTransaction()`).
4. Executa `UPDATE propostas SET status = ? WHERE id = ?`.
5. Aciona o Service interno `sync_opportunity_stage`.
   - Consulta a tabela de configurações de funil para mapear o status para o `etapa_id` correspondente.
   - Faz `UPDATE oportunidades SET etapa_id = ? WHERE id = ?`.
6. Avalia a regra de Handoff: se `$novo_status == 'Aprovada'`.
   - Aciona o Service interno `create_vendas_fornecedores_from_proposal`.
   - Busca dados da proposta, oportunidade e cliente associado.
   - Monta payload e executa `INSERT INTO vendas_fornecedores (...)`.
7. Efetua o commit da transação.

## Dependências
- **Database (PDO)**: Tabelas `propostas`, `proposta_itens`, `oportunidades`, `vendas_fornecedores`.
- **Session**: Verifica cargo e id do usuário para RBAC.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Sincronização tratada como Side-Effect em código PHP transacionado, não como Trigger SQL | `api/handlers/proposal_handler.php:126` | 🟢 |
| Mapeamento rígido (hardcoded) de "Status de Proposta" para "ID de Etapa de Funil" | `sync_opportunity_stage` | 🟢 |
| RBAC aplicado no nível do handler em vez de Middleware/Route Guard global | `api/handlers/proposal_handler.php:195` | 🟢 |

## Estado Interno
As entidades envolvidas dependem fortemente do isolamento transacional para evitar que a proposta fique Aprovada mas a Oportunidade não mova.

## Observabilidade
Sem logs explícitos. Falhas no PDO lançam exceptions que quebram o fluxo e dão rollback (seguro, mas opaco).

## Riscos e Lacunas
- 🔴 Hardcode de Etapas do Funil: A função `sync_opportunity_stage` provavelmente tem os IDs das etapas (ex: `etapa_id = 4` para 'Ganho') engessados no código. Se o usuário administrador alterar as etapas do CRM via painel, a integração de aprovação de proposta vai quebrar silenciosamente ou apontar para a etapa errada.
- 🟡 Handoff Financeiro Primitivo: A inserção direta em `vendas_fornecedores` gera débito técnico se a estrutura da tabela de vendas mudar, pois cria alto acoplamento do módulo de CRM (Vendas) com o módulo de ERP (Fornecedores). Em uma refatoração, o ideal é emitir um Evento (ex: `ProposalApprovedEvent`) e deixar o módulo financeiro escutar e processar.
