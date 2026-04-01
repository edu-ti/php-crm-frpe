<?php
require 'config.php';
try {
    $pdo->exec('ALTER TABLE oportunidades ADD COLUMN documento_url VARCHAR(512) DEFAULT NULL');
    $pdo->exec('ALTER TABLE oportunidades ADD COLUMN documento_nome VARCHAR(255) DEFAULT NULL');
    echo "Columns added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
