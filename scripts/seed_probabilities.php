<?php
require_once __DIR__ . '/../api/core/Database.php';

echo "Seeding probabilities...\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Default probabilities suitable for a standard sales funnel
    // Map by ID matching the provided SQL dump
    $probabilities = [
        // Funil 1 (Vendas Padrão)
        1 => 10,  // Prospectando
        2 => 20,  // Contato
        3 => 30,  // Treinamentos
        4 => 50,  // Proposta
        5 => 80,  // Negociação
        6 => 100, // Fechado
        7 => 100, // Pós-venda
        8 => 0,   // Recusado

        // Funil 2 (Licitações)
        14 => 10, // Captação de Edital
        15 => 20, // Acolhimento de propostas
        17 => 40, // Em análise Técnica
        18 => 50, // Homologado
        19 => 90, // Ata/Carona
        20 => 95, // Empenhado
        21 => 100,// Contrato
        22 => 0,  // Desclassificado
        23 => 0,  // Revogado
        24 => 0,  // Fracassado
        25 => 0,  // Anulado
        26 => 0   // Suspenso
    ];

    foreach ($probabilities as $id => $prob) {
        $sql = "UPDATE etapas_funil SET probabilidade = :prob WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':prob' => $prob, ':id' => $id]);
    }

    echo "Probabilities seeded successfully.\n";

} catch (Exception $e) {
    echo "Error seeding probabilities: " . $e->getMessage() . "\n";
}
?>