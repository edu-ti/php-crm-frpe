# Matriz de Rastreabilidade (Code-Spec Matrix)

> Mapeamento entre os arquivos fonte do legado e as especificações geradas.

| Arquivo do legado | Unit correspondente | Cobertura |
|-------------------|---------------------|-----------|
| `api/handlers/auth_handler.php` | `auth/` | 🟢 |
| `api/handlers/agenda_handler.php` | `agenda/` | 🟢 |
| `api/handlers/client_pf_handler.php` | `client_pf/` | 🟢 |
| `api/handlers/contact_handler.php` | `contact/` | 🟢 |
| `api/handlers/data_handler.php` | `data/` | 🟢 |
| `api/handlers/email_handler.php` | `email/` | 🟢 |
| `api/handlers/finance_handler.php` | `finance/` | 🟢 |
| `api/handlers/invoice_parser_handler.php` | `invoice_parser/` | 🟢 |
| `api/handlers/lead_handler.php` | `lead/` | 🟢 |
| `api/handlers/opportunity_handler.php` | `opportunity/` | 🟢 |
| `api/handlers/organization_handler.php` | `organization/` | 🟢 |
| `api/handlers/product_handler.php` | `product/` | 🟢 |
| `api/handlers/proposal_handler.php` | `proposal/` | 🟢 |
| `api/handlers/rbac_handler.php` | `rbac/` | 🟢 |
| `api/handlers/report_handler.php` | `report/` | 🟢 |
| `api/handlers/user_handler.php` | `user/` | 🟢 |
| `api/config.php` | `n/a` | 🔴 |
| `api/router.php` | `n/a` | 🔴 |
| `api/utils.php` | `n/a` | 🔴 |

## Arquivos sem cobertura explícita (`n/a`)
- `api/config.php`, `api/router.php`, `api/utils.php` e arquivos auxiliares do core não foram transformados em unidades de negócio, pois tratam-se de infraestrutura da framework (bootstrap, injeção de dependências e helpers). No novo paradigma, espera-se que a infraestrutura moderna do framework alvo (ex: NestJS, Laravel) substitua essas responsabilidades.
