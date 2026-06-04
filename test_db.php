<?php
require 'config.php';
try {
    $stmt = $pdo->prepare("UPDATE propostas SET status = ?, data_aprovacao = CASE WHEN ? = 'Aprovada' AND status != 'Aprovada' THEN NOW() ELSE data_aprovacao END WHERE oportunidade_id = ?");
    $stmt->execute(['Aprovada', 'Aprovada', 1]);
    echo "OK\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
