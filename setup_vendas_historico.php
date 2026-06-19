<?php
$pdo = new PDO('mysql:host=localhost;dbname=educ_crm;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("ALTER TABLE vendas_fornecedores ADD COLUMN etapa_id INT NULL");
    echo "Column etapa_id added to vendas_fornecedores.\n";
} catch (Exception $e) {
    echo "etapa_id probably already exists: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS historico_vendas_fornecedores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            venda_fornecedor_id INT NOT NULL,
            usuario_id INT NOT NULL,
            tipo ENUM('criacao', 'atualizacao', 'mudanca_etapa', 'nota') NOT NULL,
            descricao TEXT NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (venda_fornecedor_id) REFERENCES vendas_fornecedores(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table historico_vendas_fornecedores created or already exists.\n";
} catch (Exception $e) {
    echo "Error creating history table: " . $e->getMessage() . "\n";
}
?>
