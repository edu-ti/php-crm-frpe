<?php
// api/migrate_price_kit_fix.php
// CORREÇÃO: recria as 4 tabelas com a estrutura master-detail correta
// Execute UMA VEZ: seusite.com/api/migrate_price_kit_fix.php

session_start();
require_once __DIR__ . '/../config.php';
if (!isset($pdo)) { die("Erro: Conexão com o banco de dados não estabelecida."); }

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$steps   = [];
$errors  = [];

function run($pdo, $sql, $label, &$steps, &$errors) {
    try { $pdo->exec($sql); $steps[] = "✅ $label"; }
    catch (PDOException $e) { $errors[] = "⚠️ $label: " . htmlspecialchars($e->getMessage()); }
}

// 1. Desabilita FK checks para poder dropar na ordem certa
run($pdo, "SET FOREIGN_KEY_CHECKS = 0", "FK checks OFF", $steps, $errors);

// 2. Dropa tabelas antigas (em ordem segura)
run($pdo, "DROP TABLE IF EXISTS `kit_itens`",         "DROP kit_itens",         $steps, $errors);
run($pdo, "DROP TABLE IF EXISTS `kits`",              "DROP kits",              $steps, $errors);
run($pdo, "DROP TABLE IF EXISTS `tabela_preco_itens`","DROP tabela_preco_itens",$steps, $errors);
run($pdo, "DROP TABLE IF EXISTS `tabela_preco`",      "DROP tabela_preco",      $steps, $errors);

// 3. Reabilita FK checks
run($pdo, "SET FOREIGN_KEY_CHECKS = 1", "FK checks ON", $steps, $errors);

// 4. Recria com estrutura master-detail correta
run($pdo, "CREATE TABLE `tabela_preco` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `codigo`      VARCHAR(50)  NOT NULL,
    `nome_tabela` VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE tabela_preco (master – cabeçalho)", $steps, $errors);

run($pdo, "CREATE TABLE `tabela_preco_itens` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `tabela_preco_id` INT           NOT NULL,
    `referencia`      VARCHAR(100)  DEFAULT NULL,
    `descricao`       VARCHAR(500)  NOT NULL,
    `valor_unitario`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `fabricante`      VARCHAR(255)  DEFAULT NULL,
    `observacoes`     TEXT          DEFAULT NULL,
    CONSTRAINT `fk_tpi_tabela` FOREIGN KEY (`tabela_preco_id`) REFERENCES `tabela_preco`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE tabela_preco_itens (detail – itens da tabela)", $steps, $errors);

run($pdo, "CREATE TABLE `kits` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `codigo`      VARCHAR(50)  DEFAULT NULL,
    `nome`        VARCHAR(255) NOT NULL,
    `descricao`   TEXT         DEFAULT NULL,
    `valor_total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE kits", $steps, $errors);

run($pdo, "CREATE TABLE `kit_itens` (
    `id`                      INT AUTO_INCREMENT PRIMARY KEY,
    `kit_id`                  INT           NOT NULL,
    `tabela_preco_item_id`    INT           NOT NULL,
    `quantidade`              INT           NOT NULL DEFAULT 1,
    `valor_unitario_snapshot` DECIMAL(15,2) NOT NULL,
    CONSTRAINT `fk_ki_kit`  FOREIGN KEY (`kit_id`)               REFERENCES `kits`(`id`)               ON DELETE CASCADE,
    CONSTRAINT `fk_ki_item` FOREIGN KEY (`tabela_preco_item_id`) REFERENCES `tabela_preco_itens`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
"CREATE kit_itens (com coluna tabela_preco_item_id correta)", $steps, $errors);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Migração Corretiva – Tabela de Preço v2</title>
    <style>
        body { font-family: sans-serif; max-width: 720px; margin: 40px auto; padding: 20px; background: #f4f4f4; }
        .card { background: #fff; border-radius: 8px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        h1 { color: #1e3a5f; margin-top:0; }
        p  { margin:5px 0; font-size:.95rem; }
        .ok  { color: #16a34a; }
        .err { color: #ca8a04; }
        code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:.85rem; }
        .btn { display:inline-block; margin-top:20px; padding:10px 22px; background:#1e3a5f; color:#fff; text-decoration:none; border-radius:6px; }
        .success-box { background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:16px; margin-top:12px; }
    </style>
</head>
<body>
<div class="card">
    <h1>🔧 Migração Corretiva – Tabela de Preço &amp; Kits (v2)</h1>
    <p style="color:#6b7280; margin-bottom:16px;">Dropa as tabelas com estrutura antiga e recria com a estrutura master-detail correta.</p>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin-bottom:16px;">
    <?php foreach ($steps  as $s): ?><p class="ok"><?= $s ?></p><?php endforeach; ?>
    <?php foreach ($errors as $e): ?><p class="err"><?= $e ?></p><?php endforeach; ?>
    <hr style="border:none;border-top:1px solid #e5e7eb;margin-top:16px;">
    <?php if (empty($errors)): ?>
        <div class="success-box">
            <p class="ok"><strong>✅ Migração concluída com sucesso!</strong></p>
            <p>As 4 tabelas foram recriadas com a estrutura correta:</p>
            <p>• <code>tabela_preco</code> – cabeçalho (Código + Nome)</p>
            <p>• <code>tabela_preco_itens</code> – itens da tabela</p>
            <p>• <code>kits</code> – conjunto de produtos</p>
            <p>• <code>kit_itens</code> com coluna <code>tabela_preco_item_id</code> ✓</p>
            <p style="margin-top:10px; color:#6b7280;">Pode apagar este arquivo agora.</p>
        </div>
    <?php else: ?>
        <p style="color:#dc2626;"><strong>⚠️ Concluído com avisos.</strong> Verifique os itens em laranja.</p>
    <?php endif; ?>
    <a class="btn" href="../index.php">← Voltar ao CRM</a>
</div>
</body>
</html>
