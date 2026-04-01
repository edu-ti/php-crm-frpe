<?php
require 'api/core/Database.php';
$db = new Database();
$pdo = $db->getConnection();

echo "- FUNIS:\n";
$stmt = $pdo->query('SELECT * FROM funis');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n- ETAPAS_FUNIL:\n";
$stmt = $pdo->query('SELECT * FROM etapas_funil ORDER BY funil_id, ordem');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
