<?php
// add_licitacoes_funnel.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

try {
    $pdo->beginTransaction();

    // 1. Check if 'Funil Licitações' already exists
    $stmt = $pdo->prepare("SELECT id FROM funis WHERE nome = ?");
    $stmt->execute(['Funil Licitações']);
    $funil_id = $stmt->fetchColumn();

    if (!$funil_id) {
        echo "Creating 'Funil Licitações'...\n";
        $stmt_insert = $pdo->prepare("INSERT INTO funis (nome, descricao) VALUES (?, ?)");
        // Ignoring 'descricao' column existence check for now, assuming straight forward insert or minimal columns based on other tables. 
        // Actually, let's verify columns. standard funis usually have nome.
        // If descricao doesn't exist, this might fail. Let's try simple insert first or valid columns.
        // Looking at data_handler.php it selects *, but doesn't show insert. 
        // Let's assume just 'nome' for safety or check schema.
        // Re-reading schema viewing: 2112: ALTER TABLE `funis` ADD PRIMARY KEY (`id`);
        // I didn't see CREATE TABLE funis in the previous view, only indexes.

        // Let's safe bet: Insert into funis (nome) VALUES (?)
        $stmt_insert = $pdo->prepare("INSERT INTO funis (nome) VALUES (?)");
        $stmt_insert->execute(['Funil Licitações']);
        $funil_id = $pdo->lastInsertId();
        echo "Funnel created with ID: $funil_id\n";
    } else {
        echo "'Funil Licitações' already exists with ID: $funil_id\n";
    }

    // 2. Define Stages (Updated based on User Request)
    $stages = [
        ['nome' => 'CAPTAÇÃO', 'ordem' => 1, 'cor' => '#3498db'],       // Blue
        ['nome' => 'EM ANÁLISE', 'ordem' => 2, 'cor' => '#f1c40f'],     // Yellow
        ['nome' => 'DISPUTA', 'ordem' => 3, 'cor' => '#e67e22'],        // Orange
        ['nome' => 'ANÁLISE TÉCNICA', 'ordem' => 4, 'cor' => '#9b59b6'],// Purple
        ['nome' => 'HOMOLOGADO/ATA', 'ordem' => 5, 'cor' => '#27ae60'], // Green
        ['nome' => 'CARONA', 'ordem' => 6, 'cor' => '#1abc9c'],         // Teal
        ['nome' => 'EMPENHADO', 'ordem' => 7, 'cor' => '#2c3e50'],      // Dark Blue
        ['nome' => 'CONTRATO', 'ordem' => 8, 'cor' => '#c0392b']        // Red
    ];

    // 3. Clear existing stages for this funnel (to fix any previous run)
    $stmt_delete = $pdo->prepare("DELETE FROM etapas_funil WHERE funil_id = ?");
    $stmt_delete->execute([$funil_id]);
    echo "Cleared existing stages for Funnel ID $funil_id.\n";

    // 4. Insert Stages
    $stmt_insert_stage = $pdo->prepare("INSERT INTO etapas_funil (funil_id, nome, ordem, cor) VALUES (?, ?, ?, ?)");

    foreach ($stages as $stage) {
        // Check if 'cor' column exists. If not, we might need to omit it.
        try {
            $stmt_insert_stage->execute([$funil_id, $stage['nome'], $stage['ordem'], $stage['cor']]);
            echo "Added stage: {$stage['nome']}\n";
        } catch (PDOException $e) {
            // Retry without 'cor' if column doesn't exist
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                echo "Column 'cor' likely missing. Retrying without it.\n";
                $stmt_insert_stage_no_color = $pdo->prepare("INSERT INTO etapas_funil (funil_id, nome, ordem) VALUES (?, ?, ?)");
                $stmt_insert_stage_no_color->execute([$funil_id, $stage['nome'], $stage['ordem']]);
                echo "Added stage (no color): {$stage['nome']}\n";
            } else {
                throw $e;
            }
        }
    }

    $pdo->commit();
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
