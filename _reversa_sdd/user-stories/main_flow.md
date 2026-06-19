# Fluxo Principal: Do Lead ao Faturamento

> User story agregada mostrando a jornada de um dado dentro do CRM, extraída por engenharia reversa.

## Atores
- Vendedor
- Gerente (Dashboard)

## Narrativa

**Épico: Venda B2B Completa**

Como **Vendedor**, eu quero registrar um Lead frio, qualificá-lo até virar Oportunidade, emitir uma Proposta e, ao fechá-la, ver o comissionamento e o faturamento serem registrados automaticamente para a empresa, para que eu não precise digitar os mesmos dados em vários sistemas.

**Cenários (BDD)**

**Cenário 1: Entrada do Lead**
* Dado que estou logado no CRM como Vendedor
* Quando eu acesso o módulo de Leads e clico em "Novo"
* E preencho os dados do cliente e salvo
* Então o sistema deve criar o Lead com o status "Novo".

**Cenário 2: Conversão em Oportunidade**
* Dado que liguei para o Lead e ele demonstrou interesse
* Quando eu altero o status do Lead para "Qualificado"
* Então o sistema deve criar automaticamente um registro em `oportunidades` (Handoff)
* E vincular esta oportunidade ao meu usuário e ao cliente recém-criado.

**Cenário 3: Envio de Proposta Comercial**
* Dado que a oportunidade avançou no funil
* Quando eu clico em "Gerar Proposta" informando o valor de R$ 15.000,00
* Então o sistema cria o registro em `propostas` com o status "Em Análise".

**Cenário 4: Fechamento (Ganho)**
* Dado que o cliente aceitou a proposta
* Quando eu atualizo o status da Proposta para "Aprovada"
* Então o sistema dispara os side-effects:
  - Altera a Oportunidade para "Ganho".
  - Cria um registro de faturamento/comissão no módulo `financeiro` / `vendas_fornecedores`.

**Cenário 5: Leitura de Resultados**
* Dado que sou um Gerente
* Quando acesso o `report` de Dashboard Financeiro
* Então o sistema me apresenta a somatória de faturamento onde a venda recém-fechada pelo Vendedor já se encontra contabilizada e impacta o Ticket Médio geral.
