# Invoice Parser (Leitor de Notas Fiscais)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo utilitário avançado que recebe o upload de arquivos PDF de Notas Fiscais (NFe), extrai o texto contido e identifica dados críticos da venda automaticamente, vinculando o documento processado à oportunidade de negócio pertinente.

## Responsabilidades
- Receber uploads de arquivos `.pdf`.
- Extrair a camada de texto do documento PDF.
- Identificar por reconhecimento de padrões (Regex) dados da NFe, como: Valor Total, Destinatário e Itens/Produtos.
- Sugerir ou realizar o vínculo automático dos dados extraídos com a Oportunidade alvo.

## Regras de Negócio
- O parser deve lidar com a instabilidade de formatação das NFes emitidas (quebras de linha, espaçamentos variáveis). 🟡
- Os dados extraídos podem atualizar a oportunidade ou criar um registro de nota fiscal vinculado (`finance_handler`). 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Upload de Arquivo PDF | Must | O sistema deve expor um endpoint que aceita multipart form-data (application/pdf) |
| RF-02 | Extração de Texto (OCR/Parser) | Must | O sistema deve ler a camada textual do PDF (sem necessitar de Visão Computacional se for PDF nativo) |
| RF-03 | Matcher de Padrões (Regex) | Must | O sistema deve identificar "Valor Total da Nota", "CNPJ/Nome" e "Itens" |
| RF-04 | Vínculo com Oportunidade | Should | A API deve aceitar opcionalmente o ID da oportunidade e atrelar a NF extraída a ela |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Portabilidade | Biblioteca robusta de PDF | Dependência de `Smalot\PdfParser\Parser` no legado | 🟢 |
| Resiliência | Tratamento de exceções no parser | PDFs corrompidos ou em formato de imagem (scanneados) quebram o fluxo | 🟡 |

> Inferido a partir do código. Validar com equipe de operações a volumetria de PDFs não-nativos (scans/imagens) que podem exigir OCR real.

## Critérios de Aceitação

```gherkin
Dado um PDF de NFe válido exportado pelo portal da Receita
Quando o vendedor envia o arquivo para a API do parser
Então o sistema devolve um JSON com o "Valor Total: R$ 5.000,00", poupando digitação manual

Dado um arquivo de imagem disfarçado de PDF (.jpg salvo como .pdf)
Quando enviado para o parser de texto (Smalot)
Então o sistema deve retornar um erro amigável "Não foi possível extrair texto legível"
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Parse de PDF nativo | Must | Reduz o "data entry" manual do vendedor, principal atrito do CRM |
| RegEx customizados para SEFAZ | Must | O layout padrão brasileiro exige expressões complexas |
| OCR de Imagens | Won't Have | Não é suportado nativamente pelo Smalot. Muito complexo para fase 1 da migração |

> Prioridade inferida pela complexidade e dependências externas.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/invoice_parser_handler.php` | `handle_parse_invoice` | 🟢 |
