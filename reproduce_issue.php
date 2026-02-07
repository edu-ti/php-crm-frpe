<?php
// reproduce_issue.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

echo "1. Creating test organization...\n";
$stmt = $pdo->prepare("INSERT INTO organizacoes (nome_fantasia, cnpj) VALUES (?, ?)");
$testName = "TEST_DELETE_" . time();
$testCnpj = "00000000000" . rand(100,999);
$stmt->execute([$testName, $testCnpj]);
$id = $pdo->lastInsertId();
echo "Organization created with ID: $id\n";

echo "2. Verifying existence...\n";
$stmt = $pdo->prepare("SELECT id FROM organizacoes WHERE id = ?");
$stmt->execute([$id]);
if ($stmt->fetch()) {
    echo "Organization exists.\n";
} else {
    echo "Error: Organization not found immediately after creation.\n";
    exit;
}

echo "3. Deleting organization (simulating api handler)...\n";
// Simulating handle_delete_organization logic
try {
    $stmt = $pdo->prepare("DELETE FROM organizacoes WHERE id = ?");
    if ($stmt->execute([(int) $id])) {
        echo "Delete statement executed successfully.\n";
    } else {
        echo "Delete statement returned false.\n";
        print_r($stmt->errorInfo());
    }
} catch (PDOException $e) {
    echo "PDOException during delete: " . $e->getMessage() . "\n";
}

echo "4. Verifying deletion...\n";
$stmt = $pdo->prepare("SELECT id FROM organizacoes WHERE id = ?");
$stmt->execute([$id]);
if ($result = $stmt->fetch()) {
    echo "FAILURE: Organization still exists in DB! ID: " . $result['id'] . "\n";
} else {
    echo "SUCCESS: Organization was deleted from DB.\n";
}
?>
