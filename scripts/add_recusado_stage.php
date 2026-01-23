<?php
// add_recusado_stage.php
// Script para adicionar a etapa 'Recusado' ao funil de vendas e reordenar as etapas.

require_once 'config.php';
require_once 'api/core/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    echo "Iniciando atualizacao do banco de dados...\n";

    // 1. Verificar se a etapa 'Recusado' ja existe para evitar duplicata
    $stmtCheck = $pdo->prepare("SELECT id FROM etapas_funil WHERE nome = ? AND funil_id = 1");
    $stmtCheck->execute(['Recusado']);
    if ($stmtCheck->fetch()) {
        die("A etapa 'Recusado' ja existe no funil.\n");
    }

    // 2. Reordenar etapas existentes: Mover Fechado (6) e Pos-venda (7) para frente
    // Vamos mover tudo que tem ordem >= 6 para ordem + 1
    $pdo->beginTransaction();

    $stmtUpdate = $pdo->prepare("UPDATE etapas_funil SET ordem = ordem + 1 WHERE ordem >= 6 AND funil_id = 1");
    $stmtUpdate->execute();

    echo "Etapas existentes reordenadas.\n";

    // 3. Inserir 'Recusado' na ordem 6
    $stmtInsert = $pdo->prepare("INSERT INTO etapas_funil (funil_id, nome, ordem) VALUES (1, 'Recusado', 6)");
    $stmtInsert->execute();

    $pdo->commit();

    echo "Sucesso! Etapa 'Recusado' adicionada na posicao 6.\n";
    echo "Nova ordem:\n";

    $stmtList = $pdo->query("SELECT id, nome, ordem FROM etapas_funil WHERE funil_id = 1 ORDER BY ordem ASC");
    $stages = $stmtList->fetchAll(PDO::FETCH_ASSOC);
    foreach ($stages as $stage) {
        echo "{$stage['ordem']}. {$stage['nome']}\n";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Erro: " . $e->getMessage() . "\n";
}
?>