# ADR 004: Exportação de Relatórios com PhpSpreadsheet

## Status
Aceito (Retroativo)

## Contexto
O Dashboard apresenta dezenas de KPIs, tabelas de evolução e funis. Os analistas e gestores precisavam extrair essas informações para Excel a fim de realizar pivot tables externas ou enviar para auditoria.

## Decisão
A biblioteca `PhpSpreadsheet` foi escolhida e implementada (`report_handler.php` e módulos relacionados) para gerar planilhas nativas em formato .xlsx contendo múltiplas abas quando o usuário solicita o download do relatório.

## Consequências
- **Positivas:** Gera arquivos Excel nativos ricos (com formatação) suportando abas, em vez de CSVs textuais planos.
- **Negativas:** Alto consumo de memória no servidor para relatórios com milhares de linhas; requer a extensão `ext-zip` e `ext-gd` no PHP.

## Alternativas Consideradas
- **Exportação CSV simples:** Seria mais rápido e consumiria menos memória, mas perderia a habilidade de exportar relatórios complexos com abas separadas para (Propostas, Vendas Fornecedores, etc) em um único arquivo de saída.
