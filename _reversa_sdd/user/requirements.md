# User (Gestão de Usuários e Identidade)

> Template do arquivo `requirements.md`. Foca no QUE a unit faz, não no como.

## Visão Geral
Módulo responsável pela gestão do ciclo de vida das contas de acesso ao CRM (funcionários, gerentes). Ele não cuida do login em si (que fica no `auth`), mas sim do cadastro, alteração de dados, troca de senha e inativação de contas.

## Responsabilidades
- Criar novos usuários e definir seus papéis (Roles).
- Alterar dados de contato e credenciais.
- Excluir ou Inativar usuários que saíram da empresa.

## Regras de Negócio
- **Segurança de Acesso (RBAC)**: Apenas usuários com o papel 'Gestor' ou 'Analista' têm permissão de realizar operações de escrita no cadastro de usuários (Criar, Editar, Excluir). 🟢
- **Fallback de Exclusão (Soft Delete)**: Se um administrador tentar excluir (Hard Delete) um usuário que já interagiu com o sistema (ex: criou Leads ou Fechou Oportunidades), o banco de dados vai barrar a exclusão via chave estrangeira. Neste cenário exato, o sistema não deve exibir erro ao admin; ele deve capturar a recusa estrutural e transformar o comando silenciosamente em uma desativação (Soft Delete) atualizando `status = inativo`. 🟢

## Requisitos Funcionais

| ID | Requisito | Prioridade | Critério de Aceite |
|----|-----------|-----------|-------------------|
| RF-01 | Cadastro de Funcionário | Must | Permitir a criação de um usuário com Nome, Email, Senha, Papel e Status ativo. |
| RF-02 | Validação de Permissão | Must | Bloquear o acesso ao endpoint se o usuário logado for 'Vendedor'. |
| RF-03 | Exclusão Inteligente | Must | Tentar deletar; se falhar por FK, setar status para Inativo. |

## Requisitos Não Funcionais

| Tipo | Requisito inferido | Evidência no código | Confiança |
|------|--------------------|---------------------|-----------|
| Usabilidade | Feedback Transparente | Quando o fallback de Soft Delete ocorre, a mensagem de retorno para a interface não indica que foi um "delete falso", evitando confusão para o leigo. | 🟢 |

## Critérios de Aceitação

```gherkin
Dado que o "Vendedor João" tem uma oportunidade vinculada ao seu ID
E o Administrador clica em "Excluir Usuário João"
Quando a requisição for processada
Então o sistema deve tentar deletar e receber erro de ForeignKey
E o sistema deve, automaticamente, fazer um UPDATE "status = inativo"
E retornar sucesso ao administrador.
```

## Prioridade (MoSCoW)

| Requisito | MoSCoW | Justificativa |
|-----------|--------|---------------|
| Fallback para Soft Delete | Must | Garante que o sistema nunca quebre logs de auditoria e históricos de vendas de ex-funcionários, ao mesmo tempo que limpa a interface. |
| Restrição de Papel Hardcoded | Won't Have | No novo sistema, a restrição de 'Gestor'/'Analista' deve ser migrada para a Matriz Dinâmica do módulo `RBAC` em vez de hardcoded. |

## Rastreabilidade de Código

| Arquivo | Função / Classe | Cobertura |
|---------|-----------------|-----------|
| `api/handlers/user_handler.php` | `handle_create_user`, `handle_delete_user`, Tratamento PDO 23000 | 🟢 |
