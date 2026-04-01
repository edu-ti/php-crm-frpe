<?php
// api/migrate_price_kit.php  (v2 – SQL embutido, sem dependência de arquivo externo)
// Execute UMA VEZ para criar as tabelas de Tabela de Preço e Kits
// Acesse: seusite.com/api/migrate_price_kit.php

session_start();
require_once __DIR__ . '/../config.php';

if (!isset($pdo)) { die("Erro: Conexão com o banco de dados não estabelecida."); }

// SQL embutido – Master-Detail
$statements = [
    "CREATE TABLE IF NOT EXISTS `tabela_preco` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `codigo`      VARCHAR(50)  NOT NULL,
        `nome_tabela` VARCHAR(255) NOT NULL,
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `tabela_preco_itens` (
        `id`              INT AUTO_INCREMENT PRIMARY KEY,
        `tabela_preco_id` INT           NOT NULL,
        `referencia`      VARCHAR(100)  DEFAULT NULL,
        `descricao`       VARCHAR(500)  NOT NULL,
        `valor_unitario`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `fabricante`      VARCHAR(255)  DEFAULT NULL,
        `observacoes`     TEXT          DEFAULT NULL,
        CONSTRAINT `fk_tpi_tabela` FOREIGN KEY (`tabela_preco_id`) REFERENCES `tabela_preco`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `kits` (
        `id`          INT AUTO_INCREMENT PRIMARY KEY,
        `codigo`      VARCHAR(50)  DEFAULT NULL,
        `nome`        VARCHAR(255) NOT NULL,
        `descricao`   TEXT         DEFAULT NULL,
        `valor_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `kit_itens` (
        `id`                      INT AUTO_INCREMENT PRIMARY KEY,
        `kit_id`                  INT           NOT NULL,
        `tabela_preco_item_id`    INT           NOT NULL,
        `quantidade`              INT           NOT NULL DEFAULT 1,
        `valor_unitario_snapshot` DECIMAL(15,2) NOT NULL,
        CONSTRAINT `fk_ki_kit`  FOREIGN KEY (`kit_id`)               REFERENCES `kits`(`id`)               ON DELETE CASCADE,
        CONSTRAINT `fk_ki_item` FOREIGN KEY (`tabela_preco_item_id`) REFERENCES `tabela_preco_itens`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

$labels = ['tabela_preco', 'tabela_preco_itens', 'kits', 'kit_itens'];
$errors  = [];
$success = [];

foreach ($statements as $i => $sql) {
    try {
        $pdo->exec($sql);
        $success[] = "✅ Tabela <b>{$labels[$i]}</b> criada ou já existente.";
    } catch (PDOException $e) {
        $errors[] = "❌ <b>{$labels[$i]}</b>: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Migração – Tabela de Preço e Kits</title>
    <style>
        body { font-family: sans-serif; max-width: 720px; margin: 40px auto; padding: 20px; background: #f4f4f4; }
        .card { background: #fff; border-radius: 8px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        h1 { color: #1e3a5f; margin-top:0; }
        .ok  { color: #16a34a; margin: 6px 0; font-size:.95rem; }
        .err { color: #dc2626; margin: 6px 0; font-size:.95rem; }
        .btn { display:inline-block; margin-top:20px; padding:10px 22px; background:#1e3a5f; color:#fff; text-decoration:none; border-radius:6px; }
        code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:.85rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>🗃️ Migração – Tabela de Preço &amp; Kits</h1>
    <p>Criando 4 tabelas: <code>tabela_preco</code>, <code>tabela_preco_itens</code>, <code>kits</code>, <code>kit_itens</code></p>
    <hr style="margin:16px 0; border:none; border-top:1px solid #e5e7eb;">
    <?php foreach ($success as $msg): ?><p class="ok"><?= $msg ?></p><?php endforeach; ?>
    <?php foreach ($errors  as $msg): ?><p class="err"><?= $msg ?></p><?php endforeach; ?>
    <hr style="margin:16px 0; border:none; border-top:1px solid #e5e7eb;">
    <?php if (empty($errors)): ?>
        <p>✅ <strong>Migração concluída com sucesso!</strong> Pode apagar este arquivo agora.</p>
    <?php else: ?>
        <p>⚠️ <strong>Migração concluída com erros.</strong> Verifique as mensagens acima.</p>
    <?php endif; ?>
    <a class="btn" href="../index.php">← Voltar ao CRM</a>
</div>
</body>
</html>
