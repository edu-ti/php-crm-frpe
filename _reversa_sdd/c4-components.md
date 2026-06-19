# C4 Model - Nível 3: Componentes (API Backend)

```mermaid
graph TD
    classDef component fill:#85bbf0,stroke:#5d82a8,color:#000;
    classDef container fill:#438dd5,stroke:#2e6295,color:#fff;

    SPA[Frontend SPA]:::container
    
    subgraph Backend_API [Backend API (PHP)]
        API[api.php\n(Front Controller)]:::component
        Auth[auth_handler.php\n(Sessão e JWT)]:::component
        RBAC[rbac_handler.php\n(Matriz Permissões)]:::component
        Data[data_handler.php\n(Scoped Data)]:::component
        Opp[opportunity_handler.php\n(Funil e Valor)]:::component
        Prop[proposal_handler.php\n(Precificação e Vendas)]:::component
        Rep[report_handler.php\n(Agregação e BI)]:::component
        Outros[Outros Handlers\n(leads, products...)]:::component
    end
    
    DB[Database MySQL]:::container

    SPA -->|POST / GET| API
    API -->|Roteamento| Auth
    API -->|Roteamento| RBAC
    API -->|Roteamento| Data
    API -->|Roteamento| Opp
    API -->|Roteamento| Prop
    API -->|Roteamento| Rep
    API -->|Roteamento| Outros
    
    Auth --> DB
    RBAC --> DB
    Data --> DB
    Opp --> DB
    Prop --> DB
    Rep --> DB
    Outros --> DB
```
