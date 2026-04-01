<?php
// api/add_permissions.php
require_once __DIR__ . '/core/Database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}

$permissions_to_add = [
    // Relatórios
    ['resource' => 'reports', 'action' => 'create', 'label' => 'Criar Relatórios'],
    ['resource' => 'reports', 'action' => 'edit', 'label' => 'Editar Relatórios'],
    ['resource' => 'reports', 'action' => 'delete', 'label' => 'Excluir Relatórios'],
    ['resource' => 'reports', 'action' => 'import', 'label' => 'Importar Relatórios'],
    ['resource' => 'reports', 'action' => 'export', 'label' => 'Exportar Relatórios'],
    ['resource' => 'reports', 'action' => 'print', 'label' => 'Imprimir Relatórios'],

    // Agenda (Confirmação para garantir que o sidebar funcione)
    ['resource' => 'agenda', 'action' => 'view', 'label' => 'Ver Agenda'],
    ['resource' => 'agenda', 'action' => 'create', 'label' => 'Criar Agendamentos'],
    ['resource' => 'agenda', 'action' => 'edit', 'label' => 'Editar Agendamentos'],
    ['resource' => 'agenda', 'action' => 'delete', 'label' => 'Excluir Agendamentos'],
];

echo "Iniciando migração de permissões...\n";

foreach ($permissions_to_add as $perm) {
    try {
        // Tenta inserir ignorando erros de duplicata (ou lidando com exceção)
        $stmt = $pdo->prepare("INSERT INTO permissions (resource, action, label) VALUES (?, ?, ?)");
        $stmt->execute([$perm['resource'], $perm['action'], $perm['label']]);
        echo "Adicionado: {$perm['resource']} - {$perm['action']}\n";
    } catch (PDOException $e) {
        // Verifica se é erro de duplicata (Code 1062 para MySQL, 23000 SQLState)
        if ($e->getCode() == 23000) {
            echo "Já existe: {$perm['resource']} - {$perm['action']}\n";
        } else {
            echo "Erro ao adicionar {$perm['resource']} - {$perm['action']}: " . $e->getMessage() . "\n";
        }
    }
}

echo "DUMP da tabela permissions:\n";
$stmt = $pdo->query("SELECT * FROM permissions ORDER BY resource, action");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Resource: {$row['resource']} | Action: {$row['action']} | Label: {$row['label']}\n";
}

echo "Migração concluída.";
?>