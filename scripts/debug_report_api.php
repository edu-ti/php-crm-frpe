<?php
// scripts/debug_report_api.php
require_once __DIR__ . '/../api/handlers/report_handler.php';
require_once __DIR__ . '/../config.php';

$host = '127.0.0.1';
$port = '3306';
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = 'utf8';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

echo "Connecting to $dsn with user $user...\n";

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Connection successful!\n";

    // Mock params
    $_GET['start_date'] = '2025-01-01';
    $_GET['end_date'] = '2026-12-31';

    function test_report($pdo, $type)
    {
        echo "\n\n--- TESTING REPORT: $type ---\n";
        $_GET['type'] = $type;
        try {
            // buffer output to avoid huge JSON dumps if successful, or catch error
            ob_start();
            handle_get_report_data($pdo);
            $output = ob_get_clean();
            $json = json_decode($output, true);
            if (isset($json['success']) && $json['success']) {
                echo "SUCCESS: Retrieved " . count($json['data'] ?? $json['report_data'] ?? []) . " records.\n";
            } else {
                echo "FAILURE: " . ($json['error'] ?? $output) . "\n";
            }
        } catch (Exception $e) {
            echo "EXCEPTION: " . $e->getMessage() . "\n";
        }
    }

    test_report($pdo, 'products');
    test_report($pdo, 'forecast');
    test_report($pdo, 'lost_reasons');
    test_report($pdo, 'licitacoes');

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}
