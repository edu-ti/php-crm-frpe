<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Manual credentials (assuming localhost/root or using the ones that worked/failed before, 
// but since I can't connect from CLI easily, I'll rely on api.php include method if possible, 
// OR just user 'api.php' context via browser? No, I need to create the table.
// I will try to use the credentials I saw in config.php again but run this file via browser if CLI fails?
// Actually, earlier CLI failed. I should create a file that the USER can run or I can run.
// I'll create 'install_state_table.php' and rely on the existing config.php include which WORKS for the web server.
// Then I'll ask the user to run it? Or I can try to run it via CLI with correct include.

require_once 'config.php';
require_once 'api/core/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS fornecedor_metas_estados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fornecedor_id INT NOT NULL,
            ano INT NOT NULL,
            estado VARCHAR(2) NOT NULL,
            meta_anual DECIMAL(15,2) DEFAULT 0,
            meta_mensal_json TEXT, -- JSON {1: val, 2: val...}
            UNIQUE KEY uq_forn_ano_est (fornecedor_id, ano, estado)
        )";

    $pdo->exec($sql);
    echo "Table 'fornecedor_metas_estados' created successfully.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>