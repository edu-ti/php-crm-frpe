# Invoice Parser (Leitor de Notas Fiscais), Design Técnico

> Template do arquivo `design.md`. Foca no COMO a unit é construída, com base no código legado lido.

## Interface

Para endpoints HTTP:

| Método | Caminho | Entrada | Saída | Status codes |
|--------|---------|---------|-------|--------------|
| POST | `/?endpoint=invoice_parser&action=parse` | Multipart form-data (`pdf_file`, `oportunidade_id` opcional) | JSON com os dados extraídos (`valor_total`, `cnpj`, `itens`) | 200, 400, 500 |

Para funções PHP:

| Símbolo | Assinatura | Retorno | Observação |
|---------|-----------|---------|------------|
| `handle_parse_invoice` | `($pdo, $data)` | `JSON` via `json_response` | Extrai texto de um PDF usando Smalot e aplica expressões regulares. |

## Fluxo Principal
1. O Front Controller roteia para `handle_parse_invoice`.
2. O arquivo PDF é recebido via upload (`$_FILES`).
3. Instancia-se o parser da biblioteca `Smalot\PdfParser\Parser`.
4. O método `parseFile` é chamado no arquivo temporário, e a camada de texto bruta é extraída.
5. **Aplicação de RegEx**: O sistema roda expressões regulares predefinidas sobre a string massiva para buscar padrões como:
   - Valor Total: `/(?:VALOR TOTAL DA NOTA|Total NFe|Vlr Total)\s*[:R$]*\s*([\d\.,]+)/i`
   - CNPJ do Emissor/Destinatário: `/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/`
6. Opcionalmente, se houver lógica de vínculo e `oportunidade_id` for fornecido, a API pode salvar diretamente a NF na tabela `notas_fiscais`.
7. O resultado em JSON com os valores parseados é retornado com HTTP 200.

## Fluxos Alternativos
- **PDF Scanneado/Imagem**: O `Smalot` não acha texto e retorna string vazia. As Regex falham e o sistema devolve `{"success": false, "error": "Nenhum texto encontrado no PDF."}` (comportamento inferido).
- **Layout Desconhecido**: O PDF tem texto, mas a SEFAZ alterou o layout. A Regex falha, retornando `null` nos campos e demandando ajuste humano do vendedor no frontend.

## Dependências
- **Smalot/PdfParser**: Biblioteca de terceiros via Composer.
- **Database (PDO)**: Tabelas `notas_fiscais` e `oportunidades` (se fizer gravação automática).

## Decisões de Design Identificadas

| Decisão | Evidência no código | Confiança |
|---------|---------------------|-----------|
| Extração restrita a PDF Nativo (Text Layer) sem OCR pesado para evitar overhead de infraestrutura (Tesseract/AWS Textract não usados) | `Smalot\PdfParser\Parser` | 🟢 |
| Regex como mecanismo de matching ao invés de machine learning ou LLMs (no legado) | `api/handlers/invoice_parser_handler.php:30` | 🟢 |

## Estado Interno
Sem estado próprio além dos arquivos temporários de upload.

## Observabilidade
Nenhuma log explícita de "NF não reconhecida" para retroalimentar as expressões regulares.

## Riscos e Lacunas
- 🔴 Fragilidade das Expressões Regulares: Se a prefeitura ou estado muda uma palavra na DANFE (ex: de "VALOR TOTAL" para "VLR TOTAL NFE"), o parser quebra silenciosamente (retornando nulo). Faltam testes unitários automatizados para dezenas de layouts de NFe.
- 🟡 Segurança de Upload: Garantir que o `parseFile` seja imune a arquivos `.pdf` maliciosos.
