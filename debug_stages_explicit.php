<?php
// Explicit credentials for debugging
$host = '127.0.0.1';
$dbname = 'u540193243_crmfr_db';
$user = 'u540193243_crmFR';
$pass = 'g3st@0crmFR';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT s.id, s.nome, s.ordem, s.funil_id, f.nome as funil_nome FROM etapas s JOIN funis f ON s.funil_id = f.id ORDER BY f.id, s.ordem");
    $etapas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h1>Etapas (Stages)</h1>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Ordem</th><th>Funil ID</th><th>Funil Nome</th></tr>";
    foreach ($etapas as $etapa) {
        echo "<tr>";
        echo "<td>{$etapa['id']}</td>";
        echo "<td>{$etapa['nome']}</td>";
        echo "<td>{$etapa['ordem']}</td>";
        echo "<td>{$etapa['funil_id']}</td>";
        echo "<td>{$etapa['funil_nome']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
