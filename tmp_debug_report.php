<?php
require_once __DIR__ . '/../api/core/Database.php';

$db = new Database();
$pdo = $db->getConnection();

$start_date = '2026-01-01';
$end_date = '2026-12-31';
$supplier_ids = [7]; // Assuming LIVANOVA is 7, I need to check this
$cliente_ids = ['org-246']; // Assuming HC-UFPE is 246, I need to check this

function debug_get_sales_report($pdo, $start_date, $end_date, $supplier_ids, $cliente_ids)
{
    // 1. Get supplier name for ID
    $stmt = $pdo->prepare("SELECT nome FROM fornecedores WHERE id IN (" . implode(',', $supplier_ids) . ")");
    $stmt->execute();
    echo "Suppliers: " . json_encode($stmt->fetchAll()) . "\n";

    // 2. Get client info
    echo "Clients Filter: " . json_encode($cliente_ids) . "\n";

    // 3. Test NF Query
    $sql_nf = "SELECT o.fornecedor_id, o.organizacao_id, nf.valor, nf.data_faturamento 
               FROM notas_fiscais nf 
               JOIN oportunidades o ON nf.oportunidade_id = o.id 
               WHERE nf.data_faturamento BETWEEN ? AND ?";
    $params_nf = [$start_date, $end_date];

    // Manual apply filter for debug
    $sql_nf .= " AND o.fornecedor_id IN (" . implode(',', $supplier_ids) . ")";

    $org_ids = [246]; // Assuming
    $sql_nf .= " AND o.organizacao_id IN (" . implode(',', $org_ids) . ")";

    echo "SQL NF: $sql_nf\n";
    echo "Params: " . json_encode($params_nf) . "\n";

    $stmt_nf = $pdo->prepare($sql_nf);
    $stmt_nf->execute($params_nf);
    echo "NF Results: " . json_encode($stmt_nf->fetchAll()) . "\n";
}

// Find LIVANOVA ID
$stmt = $pdo->query("SELECT id, nome FROM fornecedores WHERE nome LIKE '%LIVANOVA%'");
$fornecedores = $stmt->fetchAll();
echo "LIVANOVA search: " . json_encode($fornecedores) . "\n";

// Find HC-UFPE ID
$stmt = $pdo->query("SELECT id, nome_fantasia FROM organizacoes WHERE nome_fantasia LIKE '%HC-UFPE%' OR nome_fantasia LIKE '%HOSPITAL DAS CLINICAS%'");
$clientes = $stmt->fetchAll();
echo "HC-UFPE search: " . json_encode($clientes) . "\n";

if (!empty($fornecedores) && !empty($clientes)) {
    debug_get_sales_report($pdo, $start_date, $end_date, [$fornecedores[0]['id']], ['org-' . $clientes[0]['id']]);
}
