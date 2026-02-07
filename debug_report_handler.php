<?php
// debug_report_handler.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/handlers/report_handler.php';

echo "Testing ReportHandler with Simulated GET parameters...\n";

echo "Testing direct DB connection from debug script...\n";
require_once __DIR__ . '/api/core/Database.php'; // Ensure class is loaded
try {
    $dbTest = new Database();
    $pdoTest = $dbTest->getConnection();
    echo "Direct DB Connection SUCCESS!\n";
} catch (Exception $e) {
    echo "Direct DB Connection FAILED: " . $e->getMessage() . "\n";
    exit;
}

$handler = new ReportHandler();

// Simulate GET request for DEFAULT (should fail if no data in Current Month)
echo "1. Testing Default (No GET params) - Expecting Current Month (Feb 2026?)\n";
$_GET = [];
$handler->handleRequest('GET', 'by_vendor');
echo "\n--------------------------------------------------\n";

// Simulate GET request for Correct Range
echo "2. Testing Custom Range (2025-01-01 to 2026-12-31)\n";
$_GET = ['start_date' => '2025-01-01', 'end_date' => '2026-12-31'];
$handler->handleRequest('GET', 'by_vendor');
echo "\n--------------------------------------------------\n";

echo "\nDone.\n";
