<?php
// check_stages_usage.php
ini_set('display_errors', 1);
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=3307;dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking stage usage for 'Funil Licitações'...\n";

    // Get Funnel ID
    $stmt = $pdo->prepare("SELECT id FROM funis WHERE nome = ?");
    $stmt->execute(['Funil Licitações']);
    $funil_id = $stmt->fetchColumn();

    if (!$funil_id) {
        die("Funnel 'Funil Licitações' not found.\n");
    }

    // Get Stages
    $stmt = $pdo->prepare("SELECT * FROM etapas_funil WHERE funil_id = ? ORDER BY ordem");
    $stmt->execute([$funil_id]);
    $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stages as $stage) {
        // Count opportunities
        // Assuming table 'oportunidades' has 'etapa_id'
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM oportunidades WHERE etapa_id = ?");
        $stmt_count->execute([$stage['id']]);
        $count = $stmt_count->fetchColumn();

        echo "Stage: {$stage['nome']} (ID: {$stage['id']}) - Opportunities: $count\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
