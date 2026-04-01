<?php
// api/update_roles_setup_v2.php
// Reconfiguração Estrutural do RBAC - Solicitada em 12/02/2025
// Objetivo: Definir permissões granulares no banco para eliminar travas manuais no código.

header('Content-Type: text/plain'); // Facilita visualização no browser

require_once __DIR__ . '/core/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}

echo "Iniciando reconfiguração do RBAC...\n\n";

// 1. Definição das Permissões (Catálogo)
// Garante que todas essas permissões existam na tabela 'permissions'
$catalog = [
    // Dashboard
    ['resource' => 'dashboard', 'action' => 'view', 'label' => 'Ver Dashboard'],

    // Leads / Funil (Vendas, Fornecedores, Licitações)
    ['resource' => 'leads', 'action' => 'view', 'label' => 'Ver Funil'],
    ['resource' => 'leads', 'action' => 'create', 'label' => 'Criar Oportunidade'],
    ['resource' => 'leads', 'action' => 'edit', 'label' => 'Editar Oportunidade'],
    ['resource' => 'leads', 'action' => 'move', 'label' => 'Mover Card (Kanban)'],
    ['resource' => 'leads', 'action' => 'delete', 'label' => 'Excluir Oportunidade'],

    // Leads Online
    ['resource' => 'leads_online', 'action' => 'view', 'label' => 'Ver Leads Online'],
    ['resource' => 'leads_online', 'action' => 'manage', 'label' => 'Gerenciar Leads Online'], // Create/Edit is manage here

    // Agenda
    ['resource' => 'agenda', 'action' => 'view', 'label' => 'Ver Agenda'],
    ['resource' => 'agenda', 'action' => 'create', 'label' => 'Criar Agendamento'],
    ['resource' => 'agenda', 'action' => 'edit', 'label' => 'Editar Agendamento'],
    ['resource' => 'agenda', 'action' => 'delete', 'label' => 'Excluir Agendamento'],

    // Clientes
    ['resource' => 'clients', 'action' => 'view', 'label' => 'Ver Clientes'],
    ['resource' => 'clients', 'action' => 'create', 'label' => 'Criar Cliente'],
    ['resource' => 'clients', 'action' => 'edit', 'label' => 'Editar Cliente'],
    ['resource' => 'clients', 'action' => 'delete', 'label' => 'Excluir Cliente'],

    // Propostas
    ['resource' => 'proposals', 'action' => 'view', 'label' => 'Ver Propostas'],
    ['resource' => 'proposals', 'action' => 'create', 'label' => 'Criar Proposta'],
    ['resource' => 'proposals', 'action' => 'edit', 'label' => 'Editar Proposta'],
    ['resource' => 'proposals', 'action' => 'delete', 'label' => 'Excluir Proposta'],
    ['resource' => 'proposals', 'action' => 'print', 'label' => 'Imprimir Proposta'],

    // Catálogo / Produtos
    ['resource' => 'products', 'action' => 'view', 'label' => 'Ver Catálogo'],
    ['resource' => 'products', 'action' => 'create', 'label' => 'Criar Produto'],
    ['resource' => 'products', 'action' => 'edit', 'label' => 'Editar Produto'],
    ['resource' => 'products', 'action' => 'delete', 'label' => 'Excluir Produto'],

    // Marketing (Módulo)
    ['resource' => 'marketing_module', 'action' => 'view', 'label' => 'Ver Marketing'],
    ['resource' => 'marketing_module', 'action' => 'manage', 'label' => 'Gerenciar Marketing'],

    // Relatórios
    ['resource' => 'reports', 'action' => 'view', 'label' => 'Ver Relatórios'],
    ['resource' => 'reports', 'action' => 'create', 'label' => 'Criar Relatório'], // Se houver
    ['resource' => 'reports', 'action' => 'export', 'label' => 'Exportar Relatório'],
    ['resource' => 'reports', 'action' => 'print', 'label' => 'Imprimir Relatório'],

    // Configurações (Geralmente restrito)
    ['resource' => 'settings', 'action' => 'view', 'label' => 'Ver Configurações'],
    ['resource' => 'settings', 'action' => 'edit', 'label' => 'Editar Configurações'],
];

// Inserir Permissões no Banco (Seed)
foreach ($catalog as $p) {
    try {
        $stmt = $pdo->prepare("INSERT INTO permissions (resource, action, label) VALUES (?, ?, ?)");
        $stmt->execute([$p['resource'], $p['action'], $p['label']]);
    } catch (PDOException $e) {
        // Ignora duplicidade
    }
}

echo "Permissões básicas garantidas na tabela 'permissions'.\n";


// 2. Definição de Perfis e Regras
// Estrutura: 'Role' => [ 'resource' => 'access_level' ]
// access_level: 'full', 'view_only', 'none', custom array

function get_permissions_for_role($role_type)
{
    $perms = [];

    // Default: Deny All

    if (in_array($role_type, ['ANALISTA', 'DIRETOR', 'GESTOR', 'SUPER_ADMIN'])) {
        // FULL ACCESS EVERYTHING
        return 'ALL';
    }

    // Configuração Específica por Perfil
    switch ($role_type) {
        case 'COMERCIAL':
            return [
                'dashboard' => ['view'],
                'leads' => ['view', 'create', 'edit', 'move', 'delete'], // Access Total
                'leads_online' => ['view', 'manage'], // Total
                'agenda' => ['view', 'create', 'edit', 'delete'], // Total
                'clients' => ['view', 'create', 'edit', 'delete'], // Total
                'proposals' => ['view', 'create', 'edit', 'delete', 'print'], // Total
                'products' => ['view', 'create', 'edit', 'delete'], // Total
                'marketing_module' => ['view', 'manage'], // Total
                'reports' => ['view', 'create', 'export', 'print'], // Total
                'settings' => [], // Não mencionado, assume false
            ];

        case 'FINANCEIRO':
            return [
                'dashboard' => ['view'],
                'leads' => ['view'], // Só Visualiza (Funil)
                'leads_online' => [], // Não tem acesso
                'agenda' => ['view', 'create', 'edit', 'delete'], // Total
                'clients' => ['view'], // Só Visualiza (Alterado)
                'proposals' => ['view'], // Só Visualiza
                'products' => ['view'], // Só Visualiza (Catálogo)
                'marketing_module' => [],
                'reports' => ['view', 'create', 'export', 'print'], // Total
                'settings' => [],
            ];

        case 'VENDEDOR':
        case 'TECNICO':
        case 'ESPECIALISTA':
            return [
                'dashboard' => ['view'],
                'leads' => ['view', 'create', 'edit', 'move', 'delete'], // Total (Delete incluído no Access Total do pedido, mas geralmente Vendedor não deleta. O pedido diz "Acesso Total". Vou manter Create/Edit/Move. Delete vou por true se "Acesso Total" for literal, mas no item Propostas ele especificou "Manter delete false". Para Leads ele disse "Acesso Total". Vou dar delete true se ele pediu Total, mas cuidado... vou pôr true para cumprir "Acesso Total" literal.)
                // Pedido VENDEDOR: *FUNIL DE VENDAS (Acesso Total). Então Delete = True.
                'leads_online' => [],
                'agenda' => ['view', 'create', 'edit', 'delete'], // Total
                'clients' => ['view', 'create', 'edit', 'delete'], // Total
                'proposals' => ['view', 'create', 'edit', 'print'], // Delete FALSE explícito
                'products' => ['view'], // Só visualiza
                'marketing_module' => [],
                'reports' => [],
                'settings' => [],
            ];

        case 'MARKETING':
            return [
                'dashboard' => ['view'],
                'leads' => ['view'], // Só Visualiza
                'leads_online' => ['view', 'manage'], // Acesso Total
                'agenda' => ['view', 'create', 'edit', 'delete'], // Total
                'clients' => ['view'], // Só Visualiza
                'proposals' => ['view'], // Só Visualiza
                'products' => ['view'], // Só Visualiza
                'marketing_module' => ['view', 'manage'], // Total
                'reports' => [], // Não mencionado
                'settings' => [],
            ];
    }
    return [];
}


// 3. Aplicação das Regras
$all_roles_to_update = [
    'ANALISTA',
    'DIRETOR',
    'GESTOR',
    'SUPER_ADMIN',
    'COMERCIAL',
    'FINANCEIRO',
    'VENDEDOR',
    'TECNICO',
    'MARKETING',
    'ESPECIALISTA'
];

foreach ($all_roles_to_update as $roleName) {
    echo "Processando role: $roleName... ";

    // 1. Get Role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$roleName]);
    $roleId = $stmt->fetchColumn();

    if (!$roleId) {
        // Cria se não existe
        $pdo->prepare("INSERT INTO roles (name) VALUES (?)")->execute([$roleName]);
        $roleId = $pdo->lastInsertId();
        echo "(Criada) ";
    }

    // 2. Limpar Permissões Antigas (Reset Granular)
    $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$roleId]);

    // 3. Definir Novas Permissões
    $rules = get_permissions_for_role($roleName);

    if ($rules === 'ALL') {
        // Dá permissão para TUDO que existe no catálogo
        foreach ($catalog as $p) {
            assign_permission($pdo, $roleId, $p['resource'], $p['action']);
        }
    } else {
        // Regras específicas
        foreach ($rules as $resource => $actions) {
            foreach ($actions as $action) {
                assign_permission($pdo, $roleId, $resource, $action);
            }
        }
    }
    echo "OK\n";
}

function assign_permission($pdo, $roleId, $resource, $action)
{
    // Busca ID da permissão
    $stmt = $pdo->prepare("SELECT id FROM permissions WHERE resource = ? AND action = ?");
    $stmt->execute([$resource, $action]);
    $permId = $stmt->fetchColumn();

    if ($permId) {
        $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id, allowed) VALUES (?, ?, 1)");
        $stmt->execute([$roleId, $permId]);
    }
}

echo "\nReconfiguração concluída com sucesso!";
