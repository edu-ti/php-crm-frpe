<?php
require 'api/core/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$start = '2026-01-01 00:00:00';
$end = '2026-12-31 23:59:59';
$sql = "SELECT COUNT(*) FROM vendas_fornecedores WHERE data_venda BETWEEN '$start' AND '$end'";
echo "Vendas no periodo: " . $pdo->query($sql)->fetchColumn() . "\n";

$sql = "SELECT * FROM vendas_fornecedores LIMIT 1";
echo "Exemplo: ";
print_r($pdo->query($sql)->fetch(PDO::FETCH_ASSOC));
