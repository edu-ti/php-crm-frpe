<?php
// add_licitacao_columns.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=3307;dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

try {
    echo "Checking for 'numero_edital' column...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM oportunidades LIKE 'numero_edital'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE oportunidades ADD COLUMN numero_edital VARCHAR(255) DEFAULT NULL AFTER titulo");
        echo "Added 'numero_edital' column.\n";
    } else {
        echo "'numero_edital' already exists.\n";
    }

    echo "Checking for 'numero_processo' column...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM oportunidades LIKE 'numero_processo'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE oportunidades ADD COLUMN numero_processo VARCHAR(255) DEFAULT NULL AFTER numero_edital");
        echo "Added 'numero_processo' column.\n";
    } else {
        echo "'numero_processo' already exists.\n";
    }

    echo "Migration completed.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>