<?php
// api/core/auth.php

// Definição das roles como constantes para evitar erros de digitação
define('ROLE_SUPER_ADMIN', 'SUPER_ADMIN');
define('ROLE_DIRETOR', 'DIRETOR');
define('ROLE_GESTOR', 'GESTOR');
define('ROLE_COMERCIAL', 'COMERCIAL');
define('ROLE_VENDEDOR', 'VENDEDOR');
define('ROLE_MARKETING', 'MARKETING');
define('ROLE_ANALISTA', 'ANALISTA');
define('ROLE_FINANCEIRO', 'FINANCEIRO');
define('ROLE_TECNICO', 'TECNICO');
define('ROLE_ESPECIALISTA', 'Especialista');

// Mapa de Permissões por Role
// Estrutura: 'Role' => ['permissao' => bool]
// Se a permissão não estiver listada, assume-se false (exceto SUPER_ADMIN)
$roles_permissions = [
    ROLE_SUPER_ADMIN => [], // Bypass total implementado na função hasPermission

    ROLE_DIRETOR => [
        'canSeeLeads' => true,
        'canSeeSettings' => true, // Pode editar configurações
        'canSeeCatalog' => true,
        'canManageLeads' => true,
        'canCreate' => true,
        'canEdit' => true,
        'canDelete' => true,
        'canPrint' => true,
        'canCreateOpportunity' => true,
        'canCreateClient' => true,
        'canCreateProduct' => true,
        'canDeleteProduct' => true,
        'canEditSchedule' => true,
        'canSeeReports' => true,
        'canMoveLeads' => true,
        'canSeeClients' => true,
    ],

    ROLE_GESTOR => [
        'canSeeLeads' => true,
        'canSeeSettings' => true,
        'canSeeCatalog' => true,
        'canManageLeads' => true,
        'canCreate' => true,
        'canEdit' => true,
        'canDelete' => true,
        'canPrint' => true,
        'canCreateOpportunity' => true,
        'canCreateClient' => true,
        'canCreateProduct' => true,
        'canDeleteProduct' => true,
        'canEditSchedule' => true,
        'canSeeReports' => true,
        'canMoveLeads' => true,
        'canSeeClients' => true,
    ],

    ROLE_COMERCIAL => [
        'canSeeLeads' => true,
        'canSeeSettings' => false,
        'canSeeCatalog' => true,
        'canManageLeads' => true,
        'canCreate' => true,
        'canEdit' => true,
        'canDelete' => true, // Avaliar se comercial pode deletar
        'canPrint' => true,
        'canCreateOpportunity' => true,
        'canCreateClient' => true,
        'canCreateProduct' => true,
        'canDeleteProduct' => true,
        'canEditSchedule' => true,
        'canSeeReports' => true,
        'canMoveLeads' => true,
        'canSeeClients' => true,
    ],

    ROLE_VENDEDOR => [
        'canSeeLeads' => true,
        'canSeeSettings' => false,
        'canSeeCatalog' => true, // Vê catálogo mas limita criação/edição? (Ajustar conforme regra antiga)
        'canManageLeads' => true,
        'canCreate' => true,
        'canEdit' => true,
        'canDelete' => false, // Vendedor geralmente não deleta
        'canPrint' => true,
        'canCreateOpportunity' => true,
        'canCreateClient' => true,
        'canCreateProduct' => false, // Vendedor não cria produto (baseado no código anterior)
        'canDeleteProduct' => false,
        'canEditOwnedItems' => true, // Restrição de propriedade
        'canEditSchedule' => true,
        'canSeeReports' => false, // Vendedor vê relatórios? Código anterior não lista explicitamente na regra geral
        'canMoveLeads' => true,
        'canSeeClients' => true,
    ],

    // MARKETING
    // Regra: Pode: FUNIL_LEADS_ONLINE (VISUALIZAR/CRIAR/EDITAR/MOVER).
    // Pode: módulo/tela MARKETING (VISUALIZAR/CRIAR/EDITAR).
    // Não pode: CONFIGURAÇÕES, CLIENTES, CATALOGO.
    ROLE_MARKETING => [
        'canSeeLeads' => true,       // Funil Leads Online
        'canManageLeads' => true,    // Funil Leads Online (Criar/Editar)
        'canMoveLeads' => true,      // Funil Leads Online (Mover)
        'canSeeMarketing' => true,   // Novo módulo Marketing
        'canManageMarketing' => true,// Novo módulo Marketing

        'canSeeSettings' => false,
        'canSeeClients' => false,    // NÃO pode ver CLIENTES
        'canSeeCatalog' => false,    // NÃO pode ver CALATOGO/PRODUTOS
        'canSeeReports' => false,    // Apenas relatórios de marketing? (Por enquanto bloqueado geral pois não especificado)
        'canCreateProduct' => false,
        'canCreateClient' => false,
        'canDelete' => false,
    ],

    ROLE_ANALISTA => [
        'canSeeLeads' => true,
        'canSeeSettings' => true,
        'canSeeCatalog' => true,
        'canManageLeads' => true,
        'canCreate' => true,
        'canEdit' => true,
        'canDelete' => true,
        'canPrint' => true,
        'canCreateOpportunity' => true,
        'canCreateClient' => true,
        'canCreateProduct' => true,
        'canDeleteProduct' => true,
        'canEditSchedule' => true,
        'canSeeReports' => true,
        'canMoveLeads' => true,
        'canSeeClients' => true,
    ],

    // Outros roles podem ser definidos aqui (FINANCEIRO, TECNICO) com permissões padrão restritas
    ROLE_TECNICO => [
        'canSeeLeads' => false,
        'canSeeSettings' => false,
        'canSeeCatalog' => true,
        'canSeeReports' => false,
    ],

    ROLE_FINANCEIRO => [
        'canSeeLeads' => false,
        'canSeeSettings' => false,
        'canSeeCatalog' => true,
        'canSeeReports' => true, // Talvez relatórios financeiros?
    ]
];


/**
 * Verifica se um usuário tem permissão para realizar uma ação (MÉTODO NOVO - DB).
 * Consulta a tabela role_permissions.
 *
 * @param string $role
 * @param string $resource
 * @param string $action
 * @param PDO $pdo
 * @return bool
 */
function hasPermission2($role, $resource, $action, $pdo = null)
{
    // 1. Bypass SUPER_ADMIN
    if ($role === ROLE_SUPER_ADMIN) {
        return true;
    }

    // 2. Fallback para hardcoded se não tiver PDO (segurança)
    if (!$pdo) {
        return false;
    }

    try {
        // Cache simples em memória para evitar queries repetidas na mesma requisição
        static $cache_permissions = [];
        $cache_key = "{$role}|{$resource}|{$action}";

        if (isAuthenticatedCache($cache_permissions, $cache_key)) {
            return $cache_permissions[$cache_key];
        }

        // Busca ID da role
        // Otimização: Poderia buscar todas as permissões da role de uma vez e cachear tudo
        // Vamos fazer isso: Carregar TUDO da role na primeira chamada.
        if (!isset($cache_permissions["loaded_{$role}"])) {
            $stmt = $pdo->prepare("
                SELECT p.resource, p.action, rp.allowed
                FROM role_permissions rp
                JOIN permissions p ON rp.permission_id = p.id
                JOIN roles r ON rp.role_id = r.id
                WHERE r.name = ?
            ");
            $stmt->execute([$role]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $k = "{$role}|{$row['resource']}|{$row['action']}";
                $cache_permissions[$k] = (bool) $row['allowed'];
            }
            $cache_permissions["loaded_{$role}"] = true;
        }

        return $cache_permissions[$cache_key] ?? false;

    } catch (Exception $e) {
        error_log("Erro hasPermission2: " . $e->getMessage());
        return false;
    }
}

function isAuthenticatedCache($cache, $key)
{
    return isset($cache[$key]);
}

/**
 * Retorna todas as permissões calculadas para uma role, mapeando RBAC detalhado para flags legadas.
 *
 * @param string $role
 * @param PDO $pdo
 * @return array
 */
function get_user_permissions($role, $pdo = null)
{
    // Se não tiver PDO, tenta usar a lógica antiga (fallback)
    if (!$pdo) {
        global $roles_permissions; // Fallack para array hardcoded existente neste arquivo
        // ... repete lógica antiga ou retorna vazio ...
        // Para simplificar, assumimos que sempre passaremos PDO agora.
        // Se falhar, retorna array vazio (seguro).
        return [];
    }

    $finalPermissions = [];

    // Definição do Mapa: Legacy Flag => [Resource, Action]
    $map = [
        'canSeeLeads' => ['leads', 'view'],
        'canManageLeads' => ['leads', 'create'], // Simplificação: se cria, gerencia
        'canMoveLeads' => ['leads', 'move'],
        'canSeeSettings' => ['settings', 'view'],
        'canSeeCatalog' => ['products', 'view'],
        'canSeeClients' => ['clients', 'view'],

        // Reports
        'canSeeReports' => ['reports', 'view'],
        'canCreateReport' => ['reports', 'create'],
        'canEditReport' => ['reports', 'edit'],
        'canDeleteReport' => ['reports', 'delete'],
        'canImportReport' => ['reports', 'import'],
        'canExportReport' => ['reports', 'export'],
        'canPrintReport' => ['reports', 'print'],

        'canCreateOpportunity' => ['leads', 'create'], // leads.create map to old key too
        'canEditOpportunity' => ['leads', 'edit'],     // New key for granular edit
        'canDeleteOpportunity' => ['leads', 'delete'], // New key

        'canCreateClient' => ['clients', 'create'],
        'canEditClient' => ['clients', 'edit'],       // New
        'canDeleteClient' => ['clients', 'delete'],   // New

        'canCreateProduct' => ['products', 'create'],
        'canEditProduct' => ['products', 'edit'],     // New
        'canDeleteProduct' => ['products', 'delete'],

        'canSeeMarketing' => ['marketing_module', 'view'],
        'canManageMarketing' => ['marketing_module', 'manage'],
        'canViewLeadsOnline' => ['leads_online', 'view'],

        // Proposals (Granular)
        'canViewProposals' => ['proposals', 'view'],
        'canCreateProposal' => ['proposals', 'create'],
        'canEditProposal' => ['proposals', 'edit'],
        'canDeleteProposal' => ['proposals', 'delete'],
        'canPrintProposal' => ['proposals', 'print'],

        // Agenda
        'canSeeSchedule' => ['agenda', 'view'],
        'canCreateSchedule' => ['agenda', 'create'],
        'canEditSchedule' => ['agenda', 'edit'],
        'canDeleteSchedule' => ['agenda', 'delete'],

        // Globais genéricas (mapeadas para recursos core ou mantidas false se indefinido)
        'canCreate' => ['global', 'create'],
        'canEdit' => ['global', 'edit'],
        'canDelete' => ['global', 'delete'],
        'canPrint' => ['global', 'print'],
    ];

    foreach ($map as $flag => $rule) {
        $finalPermissions[$flag] = hasPermission2($role, $rule[0], $rule[1], $pdo);
    }

    // Regras Compostas / Exceptions de Compatibilidade

    // canManageLeads geralmente implica ver e editar. 
    // Se tiver 'leads.edit', seta canManageLeads = true
    if (hasPermission2($role, 'leads', 'edit', $pdo)) {
        $finalPermissions['canManageLeads'] = true;
    }

    // canEditOwnedItems (Vendedor) - Regra de negócio específica, talvez não mapeada 1:1 no DB ainda
    // Mantemos hardcoded para roles de venda por enquanto ou criamos resource 'owned_items'
    if (in_array($role, [ROLE_VENDEDOR, ROLE_ESPECIALISTA, 'Executivo de Vendas'])) {
        $finalPermissions['canEditOwnedItems'] = true;
    } else {
        $finalPermissions['canEditOwnedItems'] = false;
    }

    // Garante chaves legadas que o frontend espera, mesmo que false
    $legacyKeys = [
        'canSeeLeads',
        'canSeeSettings',
        'canSeeCatalog',
        'canCreate',
        'canEdit',
        'canDelete',
        'canPrint',
        'canCreateOpportunity',
        'canCreateClient',
        'canCreateProduct',
        'canDeleteProduct',
        'canEditOwnedItems',
        'canManageLeads',
        'canCreateSchedule',
        'canEditSchedule',
        'canSeeReports',
        'canMoveLeads'
    ];

    foreach ($legacyKeys as $key) {
        if (!isset($finalPermissions[$key])) {
            $finalPermissions[$key] = false;
        }
    }

    return $finalPermissions;
}
?>