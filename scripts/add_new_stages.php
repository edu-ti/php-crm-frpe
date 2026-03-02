<?php
require 'api/core/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$funil_id = 1; // Funil de Vendas

try {
    // 1. Controle de Entrega (ordem após as etapas intermediárias de venda, talvez 6 ou 7)
    // Vamos checar a ordem máxima atual para colocar no final
    $stmt = $pdo->query("SELECT MAX(ordem) FROM etapas_funil WHERE funil_id = 1");
    $max_ordem = $stmt->fetchColumn() ?: 0;

    // Inserir Controle de Entrega
    $stmt = $pdo->prepare("INSERT INTO etapas_funil (funil_id, nome, ordem, cor) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nome = VALUES(nome)");
    $stmt->execute([$funil_id, 'Controle de Entrega', clone $max_ordem + 1, '#8e44ad']);

    // Inserir Faturado
    $stmt->execute([$funil_id, 'Faturado', clone $max_ordem + 2, '#2c3e50']);

    echo "Etapas adicionadas com sucesso!\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
