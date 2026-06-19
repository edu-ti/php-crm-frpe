# Arquitetura do Sistema: crm-frpe

## Visão Geral
O sistema "crm-frpe" é uma aplicação monolítica construída em PHP para o backend (API e serviços) e HTML/JS baunilha para o frontend, interligados via chamadas AJAX. O armazenamento de dados é feito em MySQL via PDO.

## Decisões Arquiteturais Principais
- **Backend Centralizado**: Toda requisição passa por `api.php`, que atua como um Front Controller roteando para handlers específicos em `api/handlers/`.
- **RBAC Integrado**: O `data_handler.php` em conjunto com a tabela de permissões faz o controle de acesso e "Scoped Data" (isolamento por usuário) em nível de query.
- **Relatórios**: Fortemente baseados no servidor com PhpSpreadsheet para exportação em Excel nativo e `UNION ALL` diretos no banco para dashboards.

## Dívidas Técnicas Identificadas
1. **Ausência de ORM Moderno**: As queries SQL cruas tornam refatorações e manutenções mais lentas.
2. **Falta de Testes Automatizados**: A base de código não possui cobertura de testes unitários ou e2e.
3. **Acoplamento na Geração de Vendas**: O módulo de Propostas está rigidamente acoplado ao de Fornecedores devido à geração automática (Hardcoded SQL Transaction).
4. **Dependências Frontend**: Há muitas bibliotecas locais (`/assets/vendor` e `/lib`) que dificultam atualizações.
