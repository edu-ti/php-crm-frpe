<?php
// api/add_freight_cols.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/core/Database.php';

echo "<h1>Adding Freight Columns</h1>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<p>Connected.</p>";

    // frete_tipo
    $stmt = $pdo->prepare("SHOW COLUMNS FROM propostas LIKE 'frete_tipo'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<p>'frete_tipo' already exists.</p>";
    } else {
        // Add after valor_total or appropriate field. Let's put it after 'status'.
        $pdo->exec("ALTER TABLE propostas ADD COLUMN frete_tipo ENUM('CIF', 'FOB') DEFAULT 'CIF' AFTER status");
        echo "<p>'frete_tipo' added.</p>";
    }

    // frete_valor
    $stmt = $pdo->prepare("SHOW COLUMNS FROM propostas LIKE 'frete_valor'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<p>'frete_valor' already exists.</p>";
    } else {
        $pdo->exec("ALTER TABLE propostas ADD COLUMN frete_valor DECIMAL(10,2) DEFAULT 0.00 AFTER frete_tipo");
        echo "<p>'frete_valor' added.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
