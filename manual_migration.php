<?php
$host = '127.0.0.1';
$port = '3307';
$db = 'u540193243_frpe_crm_db';
$user = 'u540193243_frpe';
$pass = 'g3st@03Du4rd0';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

echo "Connecting to $host:$port DB=$db User=$user...\n";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connected successfully.\n";

    // 1. Check motivo_perda
    try {
        $pdo->query("SELECT motivo_perda FROM oportunidades LIMIT 1");
        echo "Column 'motivo_perda' exists.\n";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE oportunidades ADD COLUMN motivo_perda VARCHAR(255) DEFAULT NULL");
        echo "Column 'motivo_perda' created.\n";
    }

} catch (\PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";

    // Fallback to crmFR / 3306 just in case
    echo "Retrying with crmFR / 3306 (Fallack)...\n";
    try {
        $dsn2 = "mysql:host=127.0.0.1;dbname=u540193243_crmfr_db;charset=utf8mb4";
        $pdo2 = new PDO($dsn2, 'u540193243_crmFR', 'g3st@0crmFR', $options);
        echo "Connected to Fallback.\n";
        try {
            $pdo2->query("SELECT motivo_perda FROM oportunidades LIMIT 1");
            echo "Column 'motivo_perda' exists (Fallback).\n";
        } catch (Exception $e) {
            $pdo2->exec("ALTER TABLE oportunidades ADD COLUMN motivo_perda VARCHAR(255) DEFAULT NULL");
            echo "Column 'motivo_perda' created (Fallback).\n";
        }
    } catch (\PDOException $ex) {
        echo "Fallback failed: " . $ex->getMessage() . "\n";
    }
}
?>