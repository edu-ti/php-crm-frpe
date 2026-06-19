# Invoice Parser (Leitor de Notas Fiscais), Tarefas de Implementação

> Template do arquivo `tasks.md`. Foca em uma sequência de tarefas executáveis para reimplementar a unit a partir do legado, com rastreabilidade ao código original.

## Pré-requisitos
- [ ] Instalar e configurar o pacote PDF Parser (ex: `composer require smalot/pdfparser` ou equivalente no novo paradigma).
- [ ] Configuração do ambiente para aceitar uploads e lidar com multipart.

## Tarefas

- [ ] T-01, Criar endpoint que receba o upload do arquivo PDF
  - Origem no legado: `api/handlers/invoice_parser_handler.php:1`
  - Critério de pronto: Endpoint `POST /?endpoint=invoice_parser&action=parse` configurado, recebendo o form-data com o arquivo.
  - Confiança: 🟢

- [ ] T-02, Implementar a extração textual do PDF usando a biblioteca
  - Origem no legado: Chamada a `Smalot\PdfParser\Parser`
  - Critério de pronto: Salvar arquivo temporariamente, passar no Parser e gerar uma grande string de texto limpo.
  - Confiança: 🟢

- [ ] T-03, Codificar o motor de Expressões Regulares (RegEx) para as NFs
  - Origem no legado: `api/handlers/invoice_parser_handler.php:30`
  - Critério de pronto: Escrever as funções que isolam CNPJ, Valor Total e Itens a partir do texto extraído.
  - Confiança: 🟡 (Ajuste fino será necessário dependendo de quantos layouts são esperados).

- [ ] T-04, Retornar a estrutura parseada no JSON
  - Origem no legado: Retorno do Handler
  - Critério de pronto: Retornar o HTTP 200 com os dados limpos ou mensagem de erro se a Regex falhar.
  - Confiança: 🟢

- [ ] T-05, (Opcional) Integrar criação automática de Nota Fiscal via Finance
  - Origem no legado: Caso o `oportunidade_id` seja enviado no form.
  - Critério de pronto: Chamar internamente o serviço/camada de negócio de `finance` para gravar os dados automaticamente e poupar um request a mais do front.
  - Confiança: 🟡

## Tarefas de Teste

- [ ] TT-01, Unit Test das expressões regulares alimentando textos simulados de notas válidas e corrompidas.
- [ ] TT-02, E2E Test realizando o upload de um PDF nativo e verificando o JSON retornado.
- [ ] TT-03, E2E Test com um arquivo de imagem disfarçado (.jpg) para garantir o graceful fail.

## Tarefas de Migração de Dados (se aplicável)

- [ ] TM-01, N/A (Módulo estritamente utilitário/stateless).

## Ordem Sugerida
1. T-03 (Criar os Unit Tests de RegEx primeiro - TDD fortemente recomendado para parsing).
2. T-02 (Integração com biblioteca de PDF).
3. T-01 e T-04 (Endpoint e Output).
4. T-05 (Automação extra).

## Lacunas Pendentes (🔴)
Falta um repositório de PDFs de teste (anônimos) para validar as regex atuais e garantir regressão zero.
