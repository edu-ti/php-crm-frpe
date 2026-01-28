<?php
require_once __DIR__ . '/core/Database.php';

echo "Iniciando verificação de schema...\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // 1. User Targets Enabled
    try {
        $pdo->query("SELECT user_targets_enabled FROM fornecedor_metas LIMIT 1");
        echo "[OK] Coluna 'user_targets_enabled' já existe.\n";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE fornecedor_metas ADD COLUMN user_targets_enabled TINYINT(1) DEFAULT 1");
        echo "[UPDATED] Coluna 'user_targets_enabled' criada.\n";
    }

    // 2. Meta Mensal JSON (Sazonalidade)
    try {
        $pdo->query("SELECT meta_mensal_json FROM fornecedor_metas LIMIT 1");
        echo "[OK] Coluna 'meta_mensal_json' já existe.\n";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE fornecedor_metas ADD COLUMN meta_mensal_json TEXT DEFAULT NULL");
        echo "[UPDATED] Coluna 'meta_mensal_json' criada.\n";
    }

    // 3. Motivo Perda (Oportunidades)
    try {
        $pdo->query("SELECT motivo_perda FROM oportunidades LIMIT 1");
        echo "[OK] Coluna 'motivo_perda' já existe.\n";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE oportunidades ADD COLUMN motivo_perda VARCHAR(255) DEFAULT NULL");
        echo "[UPDATED] Coluna 'motivo_perda' criada.\n";
    }

    // 4. Probabilidade (Etapas do Funil)
    try {
        $pdo->query("SELECT probabilidade FROM etapas_funil LIMIT 1");
        echo "[OK] Coluna 'probabilidade' já existe.\n";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE etapas_funil ADD COLUMN probabilidade INT DEFAULT 0");
        echo "[UPDATED] Coluna 'probabilidade' criada.\n";

        // Update default probabilities based on common stages if possible, or just leave 0
        // Example: Prospecção 10%, Qualificação 30%, Proposta 50%, Negociação 80%, Fechado 100%
        // Using generic update based on order/ids would be safer manually or via UI.
    }

    // 5. Origem (Oportunidades)
    try {
        $pdo->query("SELECT origem FROM oportunidades LIMIT 1");
        echo "[OK] Coluna 'origem' já existe.\n";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE oportunidades ADD COLUMN origem VARCHAR(100) DEFAULT NULL");
        echo "[UPDATED] Coluna 'origem' criada.\n";

        // Backfill from Leads
        // Assumindo que o título da oportunidade 'Oportunidade de [Nome Lead]' permite linkar ou usar contato_id se disponivel.
        // Mas a conversão atual seta 'oportunidade_id' no LEAD. Então podemos fazer o join inverso.
        // UPDATE oportunidades o JOIN leads l ON l.oportunidade_id = o.id SET o.origem = l.origem

        $pdo->exec("UPDATE oportunidades o JOIN leads l ON l.oportunidade_id = o.id SET o.origem = l.origem WHERE o.origem IS NULL");
        echo "[MIGRATED] Dados de origem migrados de Leads para Oportunidades.\n";
    }

    // 5. Motivo Perda (Oportunidades)
    try {
        $pdo->query("SELECT motivo_perda FROM oportunidades LIMIT 1");
        echo "[OK] Coluna 'motivo_perda' já existe.\n";
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE oportunidades ADD COLUMN motivo_perda VARCHAR(255) DEFAULT NULL");
        echo "[UPDATED] Coluna 'motivo_perda' criada.\n";
    }

    echo "Migração concluída com sucesso.\n";

} catch (Exception $e) {
    echo "Erro fatal na migração: " . $e->getMessage() . "\n";
}
?>