# ADR 005: Controle de Acesso Baseado em Papéis (RBAC) com Scoped Data

## Status
Aceito (Retroativo)

## Contexto
O CRM possui múltiplos vendedores concorrentes, e eles não devem ver os clientes ou propostas uns dos outros, enquanto gerentes precisam de visão global.

## Decisão
Implementar um Data Handler (`data_handler.php`) associado à Matriz de Permissões (`rbac_handler.php`) que intercepta o `$_SESSION['role']`. Se for "Gestor" ou "Analista", a query de listagem não possui filtro de dono; se for "Vendedor", anexa implicitamente `WHERE usuario_id = :session_id`. Adicionalmente, a regra de negócio exige que a permissão `move` conceda implicitamente a permissão `edit`.

## Consequências
- **Positivas:** Segurança nativa em todas as chamadas de API, garantindo isolamento multitenant entre os vendedores.
- **Negativas:** Dificulta o compartilhamento de uma mesma oportunidade entre dois vendedores que operam em co-autoria (funcionalidade ausente, necessitando que o Gestor reassente a oportunidade).

## Alternativas Consideradas
- **Teams / Equipes:** Criar o conceito de hierarquia de times (onde um coordenador vê as propostas de seu time). Descartado por complexidade.
- **Autorização a Nível de Frontend (ocultar botões):** Descartado por ser inseguro (qualquer um poderia disparar requisições para a API). O bloqueio precisou ser nativo no SQL do backend.
