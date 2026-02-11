ALTER TABLE usuarios MODIFY COLUMN role ENUM(
    'Gestor',
    'Comercial',
    'Vendedor',
    'Especialista',
    'Analista',
    'Representante',
    'Marketing',
    'CEO',
    'Executivo de Vendas',
    'Gestor Comercial',
    'Comercial/Vendas'
) NOT NULL;
