<?php
// api/add_discount_column.php
require_once '../config.php';
require_once 'core/helpers.php';

header('Content-Type: application/json');

try {
    $pdo->query("ALTER TABLE proposta_itens ADD COLUMN desconto_percent DECIMAL(5,2) DEFAULT 0.00 AFTER valor_unitario");
    echo json_encode(['success' => true, 'message' => 'Coluna desconto_percent adicionada com sucesso.']);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo json_encode(['success' => true, 'message' => 'Coluna desconto_percent já existe.']);
    } else {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>