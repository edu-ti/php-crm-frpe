<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/core/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->query("SELECT * FROM etapas ORDER BY ordem");
    $etapas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Etapas (Stages)</h1>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Ordem</th><th>Funil ID</th></tr>";
    foreach ($etapas as $etapa) {
        echo "<tr>";
        echo "<td>{$etapa['id']}</td>";
        echo "<td>{$etapa['nome']}</td>";
        echo "<td>{$etapa['ordem']}</td>";
        echo "<td>{$etapa['funil_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    $stmt_funnel = $pdo->query("SELECT * FROM funis");
    $funis = $stmt_funnel->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Funis (Funnels)</h1>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nome</th></tr>";
    foreach ($funis as $funil) {
        echo "<tr>";
        echo "<td>{$funil['id']}</td>";
        echo "<td>{$funil['nome']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
