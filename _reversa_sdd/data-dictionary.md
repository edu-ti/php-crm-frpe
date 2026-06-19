# Dicionário de Dados

## Tabela: `usuarios`
- **id**: int (PK)
- **nome**: string
- **email**: string (unique)
- **senha**: string (hash)
- **role**: string
- **status**: string (Ativo/Inativo)
- **deleted_at**: datetime

## Tabela: `agendamentos`
- **id**: int (PK)
- **titulo**: string
- **descricao**: text
- **data_inicio**: datetime
- **tipo**: string
- **criado_por_id**: int (FK -> usuarios)
- **oportunidade_id**: int (FK -> oportunidades)
- **data_entrega**: datetime

## Tabela: `agendamento_usuarios`
- **agendamento_id**: int (FK -> agendamentos)
- **usuario_id**: int (FK -> usuarios)

## Tabela: `clientes_pf`
- **id**: int (PK)
- **nome**: string
- **cpf**: string (11 chars, unique)
- **email**: string (unique)
- **telefone**: string
- **cep**, **logradouro**, **numero**, **complemento**, **bairro**, **cidade**, **estado**: string

## Tabela: `contatos`
- **id**: int (PK)
- **nome**: string
- **organizacao_id**: int (FK -> organizacoes)
- **cargo**: string
- **setor**: string
- **email**: string (unique)
- **telefone**: string

## Tabela: `vendas_fornecedores`
- **id**: int (PK)
- **fornecedor_id**: int (FK -> fornecedores)
- **organizacao_id**: int (FK -> organizacoes)
- **usuario_id**: int (FK -> usuarios)
- **titulo**: string
- **data_venda**: date
- **valor_total**: decimal

## Tabela: `empenhos`
- **id**: int (PK)
- **oportunidade_id**: int (FK -> oportunidades)
- **numero**: string
- **valor**: decimal
- **itens**: json

## Tabela: `notas_fiscais`
- **id**: int (PK)
- **empenho_id**: int (FK -> empenhos)
- **oportunidade_id**: int (FK -> oportunidades)
- **numero**: string
- **valor**: decimal
- **itens**: json

## Tabela: `leads`
- **id**: int (PK)
- **nome**: string
- **email**: string
- **telefone**: string
- **origem**: string
- **produto_interesse**: string
- **status**: string

## Tabela: `oportunidades`
- **id**: int (PK)
- **titulo**: string
- **valor**: decimal
- **etapa_id**: int (FK -> etapas_funil)
- **cliente_pf_id**: int (FK -> clientes_pf)
- **organizacao_id**: int (FK -> organizacoes)
- **contato_id**: int (FK -> contatos)

## Tabela: `oportunidade_itens`
- **id**: int (PK)
- **oportunidade_id**: int (FK -> oportunidades)
- **produto_id**: int (FK -> produtos)
- **parametros**: json
- **meses_locacao**: int

## Tabela: `oportunidade_historico`
- **id**: int (PK)
- **oportunidade_id**: int (FK -> oportunidades)
- **tipo**: string

## Tabela: `organizacoes`
- **id**: int (PK)
- **nome_fantasia**: string
- **cnpj**: string (unique)

## Tabela: `produtos`
- **id**: int (PK)
- **nome_produto**: string
- **valor_unitario**: decimal
- **imagem_url**: string

## Tabela: `tabela_preco`
- **id**: int (PK)
- **codigo**: string
- **nome_tabela**: string

## Tabela: `tabela_preco_itens`
- **id**: int (PK)
- **tabela_preco_id**: int (FK -> tabela_preco)
- **valor_unitario**: decimal

## Tabela: `kits`
- **id**: int (PK)
- **valor_total**: decimal

## Tabela: `kit_itens`
- **id**: int (PK)
- **kit_id**: int (FK -> kits)
- **tabela_preco_item_id**: int (FK -> tabela_preco_itens)
- **valor_unitario_snapshot**: decimal

## Tabela: `propostas`
- **id**: int (PK)
- **oportunidade_id**: int (FK -> oportunidades)
- **valor_total**: decimal
- **status**: string
- **data_criacao**: datetime
- **data_aprovacao**: datetime

## Tabela: `proposta_itens`
- **id**: int (PK)
- **proposta_id**: int (FK -> propostas)
- **produto_id**: int (FK -> produtos)
- **parametros**: json
- **quantidade**: int
- **valor_unitario**: decimal
- **meses_locacao**: int

## Tabela: `roles`
- **id**: int (PK)
- **name**: string

## Tabela: `permissions`
- **id**: int (PK)
- **resource**: string
- **action**: string
- **label**: string

## Tabela: `role_permissions`
- **role_id**: int (FK -> roles)
- **permission_id**: int (FK -> permissions)
- **allowed**: boolean
