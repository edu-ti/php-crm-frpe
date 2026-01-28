<?php
// scripts/check_schema.php
require_once __DIR__ . '/../config.php';

$host = '127.0.0.1';
$port = '3307';
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = 'utf8';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

try {
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    $pdo = new PDO($dsn, $user, $pass, $options);

    echo "--- PRODUTOS Columns ---\n";
    $stmt = $pdo->query("DESCRIBE produtos");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c)
        echo $c['Field'] . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
