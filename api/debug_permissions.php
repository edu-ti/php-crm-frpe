<?php
// api/debug_permissions.php
require_once __DIR__ . '/core/Database.php';

$db = new Database();
$pdo = $db->getConnection();

header('Content-Type: text/plain');

echo "=== PERMISSIONS TABLE ===\n";
$stmt = $pdo->query("SELECT * FROM permissions ORDER BY resource, action");
$perms = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($perms as $p) {
    echo "ID: {$p['id']} | Res: {$p['resource']} | Act: {$p['action']} | Label: {$p['label']}\n";
}

echo "\n=== ROLES TABLE ===\n";
$stmt = $pdo->query("SELECT * FROM roles");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $r) {
    echo "ID: {$r['id']} | Name: {$r['name']}\n";
}

echo "\n=== ROLE_PERMISSIONS (Comercial / Commercial) ===\n";
$target_roles = ['Comercial', 'Commercial', 'COMERCIAL'];
foreach ($target_roles as $tr) {
    echo "Checking role: $tr\n";
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$tr]);
    $rid = $stmt->fetchColumn();

    if ($rid) {
        echo "Found ID: $rid\n";
        $sql = "
            SELECT p.resource, p.action, rp.allowed, rp.permission_id
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ?
            ORDER BY p.resource, p.action
        ";
        $stmt_rp = $pdo->prepare($sql);
        $stmt_rp->execute([$rid]);
        $rows = $stmt_rp->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            echo "  [{$row['resource']}][{$row['action']}] = " . ($row['allowed'] ? 'TRUE' : 'FALSE') . " (PermID: {$row['permission_id']})\n";
        }
    } else {
        echo "Role not found in DB.\n";
    }
}
?>