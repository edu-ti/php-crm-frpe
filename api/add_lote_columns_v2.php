<?php
require_once __DIR__ . '/../config.php';

try {
    $pdo->exec("ALTER TABLE oportunidade_itens ADD COLUMN lote VARCHAR(255) DEFAULT NULL;");
    echo "Coluna 'lote' adicionada. ";
} catch (Exception $e) {
    echo "Erro lote: " . $e->getMessage() . " ";
}

try {
    $pdo->exec("ALTER TABLE oportunidade_itens ADD COLUMN item_num VARCHAR(255) DEFAULT NULL;");
    echo "Coluna 'item_num' adicionada. ";
} catch (Exception $e) {
    echo "Erro item_num: " . $e->getMessage() . " ";
}

echo "Finalizado.";
