<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/core/Database.php';

echo "=== INICIANDO CORREÇÃO DO BANCO DE DADOS ===\n\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // 1. MIGRATION: Add 'probabilidade' column
    echo "1. Verificando coluna 'probabilidade' em 'etapas_funil'...\n";
    try {
        $pdo->query("SELECT probabilidade FROM etapas_funil LIMIT 1");
        echo "   [OK] Coluna já existe.\n";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE etapas_funil ADD COLUMN probabilidade INT DEFAULT 0");
        echo "   [SUCESSO] Coluna 'probabilidade' criada.\n";
    }

    // 2. SEEDING: Populate probabilities
    echo "\n2. Preenchendo probabilidades padrão...\n";
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
        $stmt = $pdo->prepare("UPDATE etapas_funil SET probabilidade = :prob WHERE id = :id");
        $stmt->execute([':prob' => $prob, ':id' => $id]);
    }
    echo "   [SUCESSO] Probabilidades atualizadas.\n";

    echo "\n=== CONCLUÍDO COM SUCESSO! AGORA VOCÊ PODE ABRIR O RELATÓRIO DE FORECAST. ===\n";

} catch (Exception $e) {
    echo "\n[ERRO CRÍTICO]: " . $e->getMessage() . "\n";
}
?>