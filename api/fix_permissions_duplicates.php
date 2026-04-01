<?php
// api/fix_permissions_duplicates.php
require_once __DIR__ . '/core/Database.php';

$db = new Database();
$pdo = $db->getConnection();

header('Content-Type: text/plain');
echo "Iniciando limpeza de duplicatas em 'permissions'...\n";

// 1. Encontrar duplicatas
$sql = "
    SELECT resource, action, COUNT(*) as cnt, GROUP_CONCAT(id) as ids
    FROM permissions
    GROUP BY resource, action
    HAVING cnt > 1
";
$duplicates = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if (empty($duplicates)) {
    echo "Nenhuma duplicata encontrada.\n";
} else {
    foreach ($duplicates as $dup) {
        echo "Duplicata encontrada: {$dup['resource']} | {$dup['action']} (IDs: {$dup['ids']})\n";

        $ids = explode(',', $dup['ids']);
        $keep_id = $ids[0]; // Mantém o primeiro (menor ID)
        $remove_ids = array_slice($ids, 1);

        foreach ($remove_ids as $rem_id) {
            echo "  Processando remoção do ID $rem_id (mantendo $keep_id)...\n";

            // 2. Atualizar role_permissions para apontar para o ID mantido
            // Primeiro, verifique se já existe uma entrada para o ID mantido para evitar conflito
            // Se existir para ambos, deletamos o da role duplicada. Se não, movemos.

            // Busca roles que usam o ID a ser removido
            $stmt = $pdo->prepare("SELECT role_id, allowed FROM role_permissions WHERE permission_id = ?");
            $stmt->execute([$rem_id]);
            $roles_using_rem = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($roles_using_rem as $rr) {
                // Verifica se essa role já tem permissão para o ID mantido
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM role_permissions WHERE role_id = ? AND permission_id = ?");
                $stmt_check->execute([$rr['role_id'], $keep_id]);
                if ($stmt_check->fetchColumn() > 0) {
                    // Já existe para o mantido. Podemos apenas deletar a referência antiga.
                    // (Opcional: fundir 'allowed'? Vamos assumir que o mantido prevalece ou o duplicado?
                    //  Se deletarmos o duplicado, perdemos a config dele.
                    //  Mas como são duplicatas de definição, o 'allowed' deveria ser o mesmo se a lógica fosse perfeita.
                    //  Vamos manter o que já está no ID mantido.)
                    echo "    Role {$rr['role_id']} já tem referência para $keep_id. Deletando referência para $rem_id.\n";
                    $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?")->execute([$rr['role_id'], $rem_id]);
                } else {
                    // Não existe. Atualiza o ID removido para o mantido.
                    echo "    Role {$rr['role_id']} migrando de $rem_id para $keep_id.\n";
                    $pdo->prepare("UPDATE role_permissions SET permission_id = ? WHERE role_id = ? AND permission_id = ?")->execute([$keep_id, $rr['role_id'], $rem_id]);
                }
            }

            // 3. Deletar a permissão duplicada
            echo "  Deletando permissão ID $rem_id...\n";
            $pdo->prepare("DELETE FROM permissions WHERE id = ?")->execute([$rem_id]);
        }
    }
    echo "Limpeza concluída.\n";
}

// 4. Verificar se a tabela tem UNIQUE constraint
try {
    // Tenta adicionar constraint se não existir (MySQL)
    // Isso vai falhar se ainda houver duplicatas, mas acabamos de limpar.
    echo "Tentando adicionar UNIQUE constraint na tabela permissions...\n";
    $pdo->query("ALTER TABLE permissions ADD UNIQUE INDEX unique_res_act (resource, action)");
    echo "UNIQUE constraint adicionada com sucesso.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "UNIQUE constraint já existe.\n";
    } else {
        echo "Erro ao adicionar constraint: " . $e->getMessage() . "\n";
    }
}

?>