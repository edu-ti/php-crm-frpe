<?php
// debug_env.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$dbname = 'u540193243_crmfr_db';
$user = 'u540193243_crmFR';
$pass = 'g3st@0crmFR';

echo "Testing connection with HARDCODED credentials:\n";
echo "Host: $host\n";
echo "User: $user\n";
echo "Db: $dbname\n";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    echo "SUCCESS: Connected to database!\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
?>