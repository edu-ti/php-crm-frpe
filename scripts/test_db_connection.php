<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/core/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    echo "Connection successful!\n";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "Table: " . $row[0] . "\n";
    }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
