<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/core/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Check if column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM proposta_itens LIKE 'meses_locacao'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "Column 'meses_locacao' already exists.\n";
    } else {
        $pdo->exec("ALTER TABLE proposta_itens ADD COLUMN meses_locacao INT DEFAULT 12");
        echo "Column 'meses_locacao' added successfully.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
