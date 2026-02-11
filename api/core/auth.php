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
 * Verifica se um usuário tem permissão para realizar uma ação.
 *
 * @param array|string $user Pode ser o array do usuário (com 'role') ou a string da role diretamente.
 * @param string $permissionKey A chave da permissão (ex: 'canEdit', 'canSeeLeads').
 * @return bool
 */
function hasPermission($user, $permissionKey)
{
    global $roles_permissions;

    $role = is_array($user) ? ($user['role'] ?? '') : $user;

    // 1. Bypass SUPER_ADMIN
    if ($role === ROLE_SUPER_ADMIN) {
        return true;
    }

    // Normaliza role (caso venha do banco com casing diferente, embora tenha definido constant)
    // O ideal é que no banco esteja salvo exatamente como a string da constante.
    // Vamos assumir case-insensitive check por segurança.
    $roleUpper = strtoupper($role);

    // Mapeia roles legados se necessário (ex: 'Gestor Comercial' -> ROLE_GESTOR)
    // Para simplificar, vamos assumir que o banco já usa os valores corretos ou faremos um map simples
    // Ajuste conforme seu banco de dados real.

    // Matriz de permissões da role
    $permissions = $roles_permissions[$roleUpper] ?? [];

    // 2. Verifica existência da permissão base
    $allowed = $permissions[$permissionKey] ?? false;

    // 3. Regra Obrigatória: MOVER depende de EDITAR
    // Se a permissão solicitada for 'canMoveLeads' (ou similar de mover), verifica se tem 'canEdit' (ou equivalente)
    if ($permissionKey === 'canMoveLeads') {
        $canEdit = $permissions['canManageLeads'] ?? ($permissions['canEdit'] ?? false);
        if (!$canEdit) {
            return false; // Desabilita MOVER se não puder EDITAR
        }
    }

    return $allowed;
}

/**
 * Retorna todas as permissões calculadas para uma role, útil para enviar ao frontend.
 *
 * @param string $role
 * @return array
 */
function get_user_permissions($role)
{
    global $roles_permissions;

    $roleUpper = strtoupper($role);

    // Lista de todas as chaves de permissão possíveis para garantir retorno consistente
    // Coleta todas as chaves usadas em todas as roles
    $allKeys = [];
    foreach ($roles_permissions as $p) {
        $allKeys = array_merge($allKeys, array_keys($p));
    }
    $allKeys = array_unique($allKeys);

    $finalPermissions = [];

    foreach ($allKeys as $key) {
        $finalPermissions[$key] = hasPermission($roleUpper, $key);
    }

    // Permissões implícitas/Legacy que o frontend pode esperar
    // O frontend antigo esperava chaves específicas, garantimos que elas existam
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
            $finalPermissions[$key] = hasPermission($roleUpper, $key);
        }
    }

    return $finalPermissions;
}
?>