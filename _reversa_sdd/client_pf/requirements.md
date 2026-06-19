# Client PF (Pessoa Física)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Gerenciamento do ciclo de vida de Clientes Pessoa Física (B2C), suportando operações de CRUD individuais e importação em lote.

## Responsabilidades
- Criar, ler, atualizar e deletar registros de clientes pessoa física.
- Processar importação em lote de clientes.
- Garantir a integridade e unicidade de dados sensíveis (CPF e E-mail).

## Regras de Negócio
- CPFs devem ser únicos na base de dados. 🟢
- E-mails devem ser únicos na base de dados. 🟢
- Na importação em lote, duplicatas (por CPF ou E-mail) devem ser detectadas. 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | CRUD de Cliente PF | Must | O sistema deve permitir gerenciar clientes individualmente |
| RF-02 | Validação de Unicidade | Must | O sistema deve rejeitar a criação/atualização se CPF ou E-mail já existirem |
| RF-03 | Importação em Lote | Should | O sistema deve processar lotes de clientes, ignorando ou mesclando duplicatas |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Performance | Importação em lote deve ser otimizada | Algoritmo de "Importação em lote" referenciado | 🟡 |

> Inferido a partir do código. Validar com equipe de operações.

## Critérios de Aceitação

```gherkin
Dado um CPF já cadastrado no banco de dados
Quando o usuário tenta criar um novo cliente com este CPF
Então o sistema deve retornar um erro de validação e não salvar

Dado um lote válido de clientes sem duplicatas no banco
Quando a rotina de importação em lote é acionada
Então todos os clientes devem ser persistidos com sucesso
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| CRUD de Clientes PF | Must | Entidade central para operações B2C no CRM |
| Unicidade CPF/Email | Must | Regra de negócio explícita e crítica para integridade |
| Importação em Lote | Should | Agiliza o onboarding de novos clientes, mas não bloqueia a operação básica |

> Prioridade inferida por frequência de chamada e posição na cadeia de dependências.

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/client_pf_handler.php` | `handle_create_cliente_pf` e rotinas de importação | 🟢 |
