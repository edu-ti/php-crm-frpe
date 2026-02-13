<?php
// api/add_cargo_column.php

require_once 'config.php';
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN cargo VARCHAR(255) DEFAULT NULL AFTER role");
    echo "Coluna 'cargo' adicionada com sucesso na tabela 'usuarios'.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "A coluna 'cargo' já existe na tabela 'usuarios'.\n";
    } else {
        die("Erro ao adicionar coluna: " . $e->getMessage() . "\n");
    }
}
?>
