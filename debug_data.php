<?php
// debug_data.php

echo "Current Dir: " . __DIR__ . "\n";
$configPath = __DIR__ . '/config.php';
echo "Config Path: " . $configPath . "\n";

if (file_exists($configPath)) {
    require_once $configPath;
    echo "Config loaded.\n";
} else {
    echo "Config NOT found.\n";
}

echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";

require_once __DIR__ . '/api/core/Database.php';

try {
    echo "Attempting connection via Database class...\n";
    $db = new Database();
    $pdo = $db->getConnection();
    echo "Connection successful!\n";

    echo "Checking 2026 data...\n";

    // Vendas Fornecedores
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM vendas_fornecedores WHERE YEAR(data_venda) = 2026");
        echo "vendas_fornecedores (2026): " . $stmt->fetchColumn() . "\n";
    } catch (Exception $e) {
        echo "vendas_fornecedores error: " . $e->getMessage() . "\n";
    }

    // Check oportunidade_itens
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM oportunidade_itens");
        echo "oportunidade_itens count: " . $stmt->fetchColumn() . "\n";
    } catch (Exception $e) {
        echo "oportunidade_itens error: " . $e->getMessage() . "\n";
    }

    // Check proposta_itens
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM proposta_itens");
        echo "proposta_itens count: " . $stmt->fetchColumn() . "\n";
    } catch (Exception $e) {
        echo "proposta_itens error: " . $e->getMessage() . "\n";
    }

    // Opportunities
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM oportunidades WHERE YEAR(data_criacao) = 2026");
        echo "oportunidades (2026): " . $stmt->fetchColumn() . "\n";
    } catch (Exception $e) {
        echo "oportunidades error: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Connection Failed: " . $e->getMessage() . "\n";
    // Try manual connection to verify credentials
    if (defined('DB_HOST')) {
        try {
            echo "Attempting manual PDO connection...\n";
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            echo "Manual connection successful!\n";
        } catch (Exception $ex) {
            echo "Manual connection failed: " . $ex->getMessage() . "\n";
        }
    }
}
