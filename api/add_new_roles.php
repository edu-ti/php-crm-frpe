<?php
// api/add_new_roles.php

require_once __DIR__ . '/core/Database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    echo "Conectado ao banco de dados.\n";

    // Adicionando os novos perfis ao ENUM 'role' da tabela 'usuarios'
    // Perfis existentes: 'Gestor','Comercial','Vendedor','Especialista','Analista','Representante','Marketing'
    // Novos perfis: 'CEO','Executivo de Vendas','Gestor Comercial','Comercial/Vendas'

    $sql = "ALTER TABLE usuarios MODIFY COLUMN role ENUM(
        'Gestor',
        'Comercial',
        'Vendedor',
        'Especialista',
        'Analista',
        'Representante',
        'Marketing',
        'CEO',
        'Executivo de Vendas',
        'Gestor Comercial',
        'Comercial/Vendas'
    ) NOT NULL";

    $pdo->exec($sql);

    echo "Sucesso: Coluna 'role' atualizada com os novos perfis.\n";

} catch (PDOException $e) {
    echo "Erro PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erro Geral: " . $e->getMessage() . "\n";
}
?>