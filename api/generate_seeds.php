<?php
// api/generate_seeds.php

// Inclui o arquivo de auth para pegar as definições
require_once __DIR__ . '/core/auth.php';

echo "=== SEEDS DE ROLES (PHP/SQL) ===\n\n";

// 1. Definição das Roles
$roles = [
    ROLE_SUPER_ADMIN,
    ROLE_DIRETOR,
    ROLE_GESTOR,
    ROLE_COMERCIAL,
    ROLE_VENDEDOR,
    ROLE_MARKETING,
    ROLE_ANALISTA,
    ROLE_FINANCEIRO,
    ROLE_TECNICO
];

echo "-- Se o campo 'role' na tabela 'usuarios' for VARCHAR(50), não é necessário criar tabela de roles se não existir.\n";
echo "-- Mas aqui está um SQL para garantir que, se houver tabela de roles, elas existam:\n\n";

echo "/* \n";
echo "INSERT INTO roles (nome) VALUES \n";
$values = array_map(function ($r) {
    return "('" . $r . "')"; }, $roles);
echo implode(",\n", $values) . ";\n";
echo "*/\n\n";

echo "-- Atualização de roles existentes (Exemplo):\n";
echo "/*\n";
echo "UPDATE usuarios SET role = 'MARKETING' WHERE email = 'marketing@empresa.com';\n";
echo "*/\n\n";

echo "=== MATRIZ DE PERMISSÕES (PHP Array) ===\n";
echo "O sistema utiliza um arquivo PHP centralizado (api/core/auth.php) para permissões.\n";
echo "Abaixo, o dump da matriz atual para conferência do cliente:\n\n";

echo "[\n";
global $roles_permissions;
foreach ($roles_permissions as $role => $perms) {
    echo "    '$role' => [\n";
    foreach ($perms as $k => $v) {
        $val = $v ? 'true' : 'false';
        echo "        '$k' => $val,\n";
    }
    echo "    ],\n";
}
echo "];\n";
?>