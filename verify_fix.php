<?php
// Explicit credentials
$host = '127.0.0.1';
$dbname = 'u540193243_crmfr_db';
$user = 'u540193243_crmFR';
$pass = 'g3st@0crmFR';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Verification</h1>";

    // Logic copied from the fix
    $logic_check = (function ($provided_etapa_id) use ($pdo) {
        if (!empty($provided_etapa_id))
            return $provided_etapa_id;

        echo "No stage provided. Searching for 'Funil de Vendas%'.<br>";

        $stmt_stage = $pdo->prepare("
            SELECT ef.id, ef.nome, f.nome as funil_nome
            FROM etapas_funil ef 
            JOIN funis f ON ef.funil_id = f.id 
            WHERE f.nome LIKE '%Vendas%' 
            ORDER BY ef.ordem ASC, ef.id ASC 
            LIMIT 1
        ");
        $stmt_stage->execute();
        $stage = $stmt_stage->fetch(PDO::FETCH_ASSOC);

        if ($stage) {
            echo "Found Stage: ID {$stage['id']} - {$stage['nome']} (Funil: {$stage['funil_nome']})<br>";
            return $stage['id'];
        }

        echo "Funil de Vendas not found. Fallback.<br>";

        $stmt_fallback = $pdo->query("SELECT id FROM etapas_funil ORDER BY id ASC LIMIT 1");
        return $stmt_fallback->fetchColumn() ?: 1;
    })(null);

    echo "<h3>Resulting Default Stage ID: $logic_check</h3>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
