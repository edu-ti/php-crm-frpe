<?php
// api/migration_cargo.php
require_once __DIR__ . '/core/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Adiciona coluna cargo
    $sql = "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cargo VARCHAR(100) DEFAULT NULL AFTER role";
    $pdo->exec($sql);

    echo "<h1>Sucesso!</h1>";
    echo "<p>Coluna 'cargo' adicionada com sucesso na tabela 'usuarios'.</p>";
    echo "<p>Você pode fechar esta aba e recarregar o CRM.</p>";

} catch (PDOException $e) {
    echo "<h1>Erro</h1>";
    echo "<p>Erro ao atualizar banco de dados: " . $e->getMessage() . "</p>";
}
?>