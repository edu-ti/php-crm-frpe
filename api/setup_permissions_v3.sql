-- api/setup_permissions_v3.sql
-- Reconfiguração Estrutural do RBAC - Gerado em 13/02/2025
-- Importe este arquivo no phpMyAdmin para atualizar as permissões.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. Garantir que todas as PERMISSÕES existam no catálogo
INSERT INTO `permissions` (`resource`, `action`, `label`) VALUES
('dashboard', 'view', 'Ver Dashboard'),
('leads', 'view', 'Ver Funil'),
('leads', 'create', 'Criar Oportunidade'),
('leads', 'edit', 'Editar Oportunidade'),
('leads', 'move', 'Mover Card (Kanban)'),
('leads', 'delete', 'Excluir Oportunidade'),
('leads_online', 'view', 'Ver Leads Online'),
('leads_online', 'manage', 'Gerenciar Leads Online'),
('agenda', 'view', 'Ver Agenda'),
('agenda', 'create', 'Criar Agendamento'),
('agenda', 'edit', 'Editar Agendamento'),
('agenda', 'delete', 'Excluir Agendamento'),
('clients', 'view', 'Ver Clientes'),
('clients', 'create', 'Criar Cliente'),
('clients', 'edit', 'Editar Cliente'),
('clients', 'delete', 'Excluir Cliente'),
('proposals', 'view', 'Ver Propostas'),
('proposals', 'create', 'Criar Proposta'),
('proposals', 'edit', 'Editar Proposta'),
('proposals', 'delete', 'Excluir Proposta'),
('proposals', 'print', 'Imprimir Proposta'),
('products', 'view', 'Ver Catálogo'),
('products', 'create', 'Criar Produto'),
('products', 'edit', 'Editar Produto'),
('products', 'delete', 'Excluir Produto'),
('marketing_module', 'view', 'Ver Marketing'),
('marketing_module', 'manage', 'Gerenciar Marketing'),
('reports', 'view', 'Ver Relatórios'),
('reports', 'export', 'Exportar Relatório'),
('reports', 'print', 'Imprimir Relatório'),
('settings', 'view', 'Ver Configurações'),
('settings', 'edit', 'Editar Configurações')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

-- 2. Garantir que as ROLES existam
INSERT INTO `roles` (`name`) VALUES 
('ANALISTA'), ('DIRETOR'), ('GESTOR'), ('SUPER_ADMIN'),
('COMERCIAL'), ('FINANCEIRO'),
('VENDEDOR'), ('TECNICO'), ('ESPECIALISTA'),
('MARKETING')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 3. Limpar permissões antigas para reconfiguração limpa
DELETE FROM `role_permissions` WHERE `role_id` IN (SELECT `id` FROM `roles` WHERE `name` IN (
    'ANALISTA', 'DIRETOR', 'GESTOR', 'SUPER_ADMIN',
    'COMERCIAL', 'FINANCEIRO', 'VENDEDOR', 'TECNICO', 'ESPECIALISTA', 'MARKETING'
));

-- 4. Atribuir Permissões

-- ========================================================
-- GRUPO: ACESSO TOTAL (ANALISTA, DIRETOR, GESTOR, SUPER_ADMIN)
-- ========================================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `allowed`)
SELECT r.id, p.id, 1
FROM `roles` r, `permissions` p
WHERE r.name IN ('ANALISTA', 'DIRETOR', 'GESTOR', 'SUPER_ADMIN');

-- ========================================================
-- PERFIL: COMERCIAL
-- Acesso Total: Dashboard, Funil Vendas, Leads Online, Agenda, Clientes, Propostas, Catalogo, Marketing, Relatorios
-- ========================================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `allowed`)
SELECT r.id, p.id, 1
FROM `roles` r, `permissions` p
WHERE r.name = 'COMERCIAL' AND (
    (p.resource = 'dashboard' AND p.action = 'view') OR
    (p.resource = 'leads') OR -- Tudo de leads
    (p.resource = 'leads_online') OR -- Tudo de leads_online
    (p.resource = 'agenda') OR -- Tudo de agenda
    (p.resource = 'clients') OR -- Tudo de clientes
    (p.resource = 'proposals') OR -- Tudo de propostas
    (p.resource = 'products') OR -- Tudo de produtos/catalogo
    (p.resource = 'marketing_module') OR -- Tudo de marketing
    (p.resource = 'reports') -- Tudo de relatorios
);

-- ========================================================
-- PERFIL: FINANCEIRO
-- Acesso Total: Dashboard, Agenda, Clientes, Relatorios
-- Só Visualiza: Funil Vendas, Propostas, Catalogo
-- Sem Acesso: Leads Online, Marketing
-- ========================================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `allowed`)
SELECT r.id, p.id, 1
FROM `roles` r, `permissions` p
WHERE r.name = 'FINANCEIRO' AND (
    (p.resource = 'dashboard' AND p.action = 'view') OR
    (p.resource = 'leads' AND p.action = 'view') OR
    (p.resource = 'agenda') OR -- Tudo de agenda
    (p.resource = 'clients') OR -- Tudo de clientes
    (p.resource = 'proposals' AND p.action = 'view') OR
    (p.resource = 'products' AND p.action = 'view') OR
    (p.resource = 'reports') -- Tudo de relatorios
);

-- ========================================================
-- PERFIL: VENDEDOR, TECNICO, ESPECIALISTA
-- Acesso Total: Dashboard, Funil Vendas, Agenda, Clientes
-- Propostas: Criar, Editar, Visualizar, Imprimir (SEM EXCLUIR)
-- Só Visualiza: Catalogo
-- Sem Acesso: Leads Online, Marketing, Relatorios
-- ========================================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `allowed`)
SELECT r.id, p.id, 1
FROM `roles` r, `permissions` p
WHERE r.name IN ('VENDEDOR', 'TECNICO', 'ESPECIALISTA') AND (
    (p.resource = 'dashboard' AND p.action = 'view') OR
    (p.resource = 'leads') OR -- Tudo de leads (Funil)
    (p.resource = 'agenda') OR -- Tudo de agenda
    (p.resource = 'clients') OR -- Tudo de clientes
    (p.resource = 'proposals' AND p.action IN ('view', 'create', 'edit', 'print')) OR
    (p.resource = 'products' AND p.action = 'view')
);

-- ========================================================
-- PERFIL: MARKETING
-- Acesso Total: Dashboard, Leads Online, Agenda, Marketing
-- Só Visualiza: Funil Vendas, Clientes, Propostas, Catalogo
-- Sem Acesso: Relatorios
-- ========================================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `allowed`)
SELECT r.id, p.id, 1
FROM `roles` r, `permissions` p
WHERE r.name = 'MARKETING' AND (
    (p.resource = 'dashboard' AND p.action = 'view') OR
    (p.resource = 'leads' AND p.action = 'view') OR
    (p.resource = 'leads_online') OR -- Tudo de leads_online
    (p.resource = 'agenda') OR -- Tudo de agenda
    (p.resource = 'clients' AND p.action = 'view') OR
    (p.resource = 'proposals' AND p.action = 'view') OR
    (p.resource = 'products' AND p.action = 'view') OR
    (p.resource = 'marketing_module') -- Tudo de marketing
);

COMMIT;
