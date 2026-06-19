# Finance (Empenhos e Notas Fiscais), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=finance&action=create_empenho` | JSON (`oportunidade_id`, `numero_empenho`, `valor`) | JSON `{"success": true}` | 200, 400 |
| POST | `/?endpoint=finance&action=create_nota_fiscal` | JSON (`empenho_id` opcional, `numero_nf`, `valor`, `data_emissao`) | JSON `{"success": true}` | 200, 400 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_create_empenho` | `($pdo, $data)` | `JSON` via `json_response` | Higieniza o valor e persiste o empenho. |
| `handle_create_nota_fiscal`| `($pdo, $data)` | `JSON` via `json_response` | Higieniza o valor e persiste a NF. |

## Fluxo Principal (Criar Empenho)
1. O Front Controller roteia a requisição para `handle_create_empenho`.
2. O payload (`$data`) é recebido.
3. **Sanitização Monetária**: A string de `valor` (ex: `"R$ 1.250,50"`) é limpa:
   - O prefixo `"R$ "` é removido.
   - O ponto separador de milhar (`.`) é removido.
   - A vírgula separadora de decimal (`,`) é substituída por ponto (`.`), resultando em `"1250.50"`.
4. Uma query de `INSERT` é montada, vinculando `oportunidade_id`.
5. A instrução é executada no banco via PDO.
6. Retorna HTTP 200.

## Fluxos Alternativos
- **Payload Inválido**: Falta de `oportunidade_id` em um empenho resulta em HTTP 400 (se validado) ou erro de banco.

## Dependências
- **Database (PDO)**: Tabelas `empenhos` e `notas_fiscais`. Chave estrangeira para `oportunidades`.

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Formatação monetária (Moeda BRL para Decimal ANSI) tratada no backend em vez de no frontend | `api/handlers/finance_handler.php:14` | 🟢 |
| Modelagem de NFs independente, permitindo criar NF sem empenho prévio | `notas_fiscais` (campo `empenho_id` não obrigatório) | 🟡 |

## Estado Interno
Sem estado em memória. Gravação direta no MySQL.

## Observabilidade
Nenhuma log explícita de faturamento identificada.

## Riscos e Lacunas
- 🟡 Ao inserir o valor da NF ou Empenho, não há evidência forte se ele muda o "status" da oportunidade (ex: de "Ganhou" para "Faturado"). Isso pode causar dessincronia no funil caso o status seja manual.
- 🔴 A regra de sanitização de moeda `str_replace` pode quebrar se o frontend enviar acidentalmente números já em formato decimal (ex: de um `<input type="number">`).
