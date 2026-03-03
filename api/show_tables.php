<?php
require_once __DIR__ . '/../config.php';
$stmt = $pdo->query('SHOW TABLES');
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
