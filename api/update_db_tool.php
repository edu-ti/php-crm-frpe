<?php
// api/update_db_tool.php

// Define error reporting to show everything
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/core/Database.php';

echo "<h1>Atualização de Banco de Dados</h1>";

try {
    $database = new Database();
    $pdo = $database->getConnection();

    echo "<p>Conexão bem sucedida.</p>";

    // Check if column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM propostas LIKE 'motivo_status'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "<p style='color: blue;'>A coluna 'motivo_status' já existe na tabela 'propostas'. Nenhuma ação necessária.</p>";
    } else {
        $sql = "ALTER TABLE propostas ADD COLUMN motivo_status TEXT DEFAULT NULL AFTER status";
        $pdo->exec($sql);
        echo "<p style='color: green;'>Sucesso! A coluna 'motivo_status' foi adicionada à tabela 'propostas'.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro PDO: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro Geral: " . $e->getMessage() . "</p>";
}
?>