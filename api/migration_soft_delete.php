<?php
// api/migration_soft_delete.php

// Adjust path to find core/Database.php
// Assuming this script is in api/ folder
require_once __DIR__ . '/core/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Adicionar deleted_at em organizacoes
    echo "Verificando tabela 'organizacoes'...\n";
    $stm = $pdo->query("SHOW COLUMNS FROM organizacoes LIKE 'deleted_at'");
    if ($stm->rowCount() == 0) {
        $pdo->exec("ALTER TABLE organizacoes ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
        echo "Coluna 'deleted_at' adicionada em 'organizacoes'.\n";
    } else {
        echo "Coluna 'deleted_at' já existe em 'organizacoes'.\n";
    }

    // Adicionar deleted_at em clientes_pf
    echo "Verificando tabela 'clientes_pf'...\n";
    $stm = $pdo->query("SHOW COLUMNS FROM clientes_pf LIKE 'deleted_at'");
    if ($stm->rowCount() == 0) {
        $pdo->exec("ALTER TABLE clientes_pf ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
        echo "Coluna 'deleted_at' adicionada em 'clientes_pf'.\n";
    } else {
        echo "Coluna 'deleted_at' já existe em 'clientes_pf'.\n";
    }

    // Adicionar deleted_at em contatos
    echo "Verificando tabela 'contatos'...\n";
    $stm = $pdo->query("SHOW COLUMNS FROM contatos LIKE 'deleted_at'");
    if ($stm->rowCount() == 0) {
        $pdo->exec("ALTER TABLE contatos ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
        echo "Coluna 'deleted_at' adicionada em 'contatos'.\n";
    } else {
        echo "Coluna 'deleted_at' já existe em 'contatos'.\n";
    }

    echo "Migração concluída com sucesso.\n";

} catch (Exception $e) {
    echo "Erro fatal na migração: " . $e->getMessage() . "\n";
}
?>