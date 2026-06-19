<?php
require_once __DIR__ . '/config.php';
try {
    $pdo->exec("ALTER TABLE vendas_fornecedores ADD COLUMN etapa_id INT NULL");
    echo "Sucesso: coluna criada.";
} catch (Exception $e) {
    echo "Erro ao criar coluna: " . $e->getMessage() . "<br>";
}
try {
    $stmt = $pdo->query("DESCRIBE vendas_fornecedores");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo "Erro no describe: " . $e->getMessage();
}
?>
