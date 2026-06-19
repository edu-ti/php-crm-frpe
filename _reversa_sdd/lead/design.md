# Lead (Prospecção e Qualificação), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=lead&action=import_leads` | Multipart CSV (`file`) | JSON `{"success": true, "stats": {...}}` | 200, 400 |
| POST | `/?endpoint=lead&action=convert_lead_to_pre_proposal` | JSON (`lead_id`, e possivelmente `usuario_id`) | JSON `{"success": true, "oportunidade_id": X, "numero_proposta": Y}` | 200, 400, 404 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_import_leads` | `($pdo, $data)` | `JSON` via `json_response` | Lê CSV linha a linha, valida unicidade e insere leads. |
| `handle_convert_lead_to_pre_proposal` | `($pdo, $data)` | `JSON` via `json_response` | Transaciona o fechamento do lead e geração do cliente/oportunidade. |

## Fluxo Principal (Conversão de Lead)
1. Roteamento para `handle_convert_lead_to_pre_proposal` no Front Controller.
2. Inicia uma Transação PDO (`$pdo->beginTransaction()`).
3. Faz um `SELECT` no Lead pelo ID informado. Se não achar, aborta.
4. **Auto-criação de Cliente**: Faz um `SELECT` em `clientes_pf` usando o e-mail ou telefone do lead.
   - Se existir, guarda o `cliente_id` existente.
   - Se NÃO existir, faz um `INSERT` em `clientes_pf` clonando nome, email e telefone, e recupera o novo ID (`lastInsertId`).
5. **Geração de Número Sequencial**: Calcula o ano atual. Conta quantas oportunidades do tipo "pré-proposta" existem naquele ano para gerar a string `ANO/XXX` (ex: 2026/012).
6. Faz um `INSERT` na tabela `oportunidades` vinculando o `cliente_id`, o `numero_proposta` autogerado, e definindo a etapa de funil inicial correspondente.
7. Opcional/Inferido: Marca o lead original como "convertido" ou deleta da base de leads frios.
8. Commita a transação (`$pdo->commit()`) e retorna HTTP 200.

## Fluxos Alternativos
- **Falha de Integridade:** Se qualquer `INSERT` falhar, o PDO lança exceção e é feito um `rollBack()`, abortando o processo para não deixar cliente órfão de oportunidade ou vice-versa.
- **Importação CSV:** Ao iterar o arquivo, o sistema verifica a unicidade na própria linha. Se existir, pula; se não, insere.

## Dependências
- **Database (PDO)**: Acesso denso de escrita em `clientes_pf`, `oportunidades`, `leads` e leitura em `etapas_funil`.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Geração de número "humano" de proposta (ANO/SEQ) via count de banco (sujeito a concorrência se não houver lock) | Arquitetura de `handle_convert_lead...` | 🟡 |
| Transação encapsulando Criação de Cliente + Oportunidade + Status do Lead | `api/handlers/lead_handler.php:27` | 🟢 |

## Estado Interno
Sem estado próprio além dos ponteiros de leitura de arquivo na importação de CSV.

## Observabilidade
Não evidenciada, mas transações geralmente requerem bom log de Exception no `catch`.

## Riscos e Lacunas
- 🔴 Race Condition (Condição de Corrida) no número da proposta: Fazer `COUNT(*)` e somar +1 para gerar a string "2026/015" em um request web pode resultar em duas propostas com o mesmo número se dois usuários converterem leads simultaneamente. (Requer `SELECT FOR UPDATE` ou sequences/triggers reais).
