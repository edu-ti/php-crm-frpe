# Spec Impact Matrix

Esta matriz cruza os principais componentes para identificar quais áreas precisam ser testadas/revisadas quando um componente específico é modificado.

| Componente Modificado | Componentes Impactados (Efeito Cascata) | Motivo / Acoplamento |
|-----------------------|-----------------------------------------|----------------------|
| **`opportunity`** | `proposal`, `report`, `historico_oportunidades` | Regras de cálculo de valor mudam o resultado no dashboard; mudanças no funil engatilham inserts de histórico. |
| **`proposal`** | `opportunity`, `vendas_fornecedores`, `report` | Propostas aprovadas atualizam a etapa da oportunidade e forçam a injeção em `vendas_fornecedores`. |
| **`product`** | `proposal_itens`, `kits` | Mudar tabelas de preço de produtos afeta como propostas futuras farão parsing do valor, embora os kits guardem "snapshots". |
| **`rbac`** | Todos os módulos via `data_handler` | Qualquer mudança na matriz de roles/permissions altera a visibilidade nos endpoints de lista (`get_*`). |
| **`user`** | `auth` | Soft-delete do usuário impacta validade da sessão. |
